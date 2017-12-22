#!/usr/bin/php
<?php

$port = 8983;
$quiet = false;
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
foreach ($state['cluster']['collections'] as $collectionName => $collection) {
    $results .= "Collection $collectionName (Solr port $port):\n";
    foreach ($collection['shards'] as $shardName => $shard) {
        if ('active' !== $shard['state']) {
            $errors = true;
        }
        $results .= "  Shard $shardName (" . $shard['state'] . "):\n";
        foreach ($shard['replicas'] as $coreNodeName => $replica) {
            if ('active' !== $replica['state']) {
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

