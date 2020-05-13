#!/usr/bin/php
<?php

$port = 8983;
$quiet = false;
$normalStates = ['active', 'inactive', 'construction'];
array_shift($argv);
foreach ($argv as $arg) {
    if ('-q' === $arg) {
        $quiet = true;
    } else {
        $port = $arg;
    }
}

$state = `curl -s "http://localhost:$port/solr/admin/collections?action=clusterstatus&wt=json"`;

if (empty($state)) {
    echo "Could not get Solr status, port $port\n";
    exit(1);
}

$errors = false;
$results = '';
$state = json_decode($state, true);

if (!isset($state['cluster']['collections'])) {
    echo "Could not find collections in the cluster status response:\n";
    var_export($state);
    exit(1);
}

foreach ($state['cluster']['collections'] as $collectionName => $collection) {
    $aliases = !empty($collection['aliases'])
        ? ('; alias ' . implode(', ', $collection['aliases'])) : '';
    $config = !empty($collection['configName'])
        ? ('; config ' . $collection['configName']) : '';
    $results .= "Collection $collectionName (Solr port $port$aliases$config):\n";
    foreach ($collection['shards'] as $shardName => $shard) {
        if (!in_array($shard['state'], $normalStates)) {
            $errors = true;
        }
        $results .= "  Shard $shardName (" . $shard['state'] . "):\n";
        foreach ($shard['replicas'] as $coreNodeName => $replica) {
            if (!in_array($replica['state'], $normalStates)) {
                $errors = true;
            }
            if (!isset($replica['type'])) {
                $replica['type'] = '???';
            }
            $info = "$coreNodeName, {$replica['type']}";
            if (!empty($replica['leader'])) {
                $info .= ', leader';
            }
            $results .= '    ' . substr($replica['state'] . '         ', 0, 10) . ' ' . $replica['core'] . " ($info) at " . $replica['node_name'] . "\n";
        }
    }
    $results .= "\n";
}

if (!$quiet || $errors) {
    if ($errors) {
        echo "WARNING: Solr cluster degraded:\n\n";
    }
    echo $results;
}

