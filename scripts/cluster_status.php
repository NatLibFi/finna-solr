#!/usr/bin/php
<?php

$port = 8983;
$quiet = false;
$normalStates = ['active', 'inactive', 'construction'];
$flagFile = sys_get_temp_dir() . '/cluster_status_bad';
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
    echo "PROBLEM: Could not get Solr status, port $port\n";
    // Flag error status so that we know to report when things are back to normal:
    file_put_contents($flagFile, '1');
    exit(1);
}

$errors = false;
$results = '';
$state = json_decode($state, true);

if (!isset($state['cluster']['collections'])) {
    echo "PROBLEM: Could not find collections in the cluster status response:\n";
    var_export($state);
    // Flag error status so that we know to report when things are back to normal:
    file_put_contents($flagFile, '1');
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

if (!$quiet || $errors || file_exists($flagFile)) {
    if ($errors) {
        echo "WARNING: Solr cluster degraded:\n\n";
        // Flag error status so that we know to report when things are back to normal:
        file_put_contents($flagFile, '1');
    } elseif (file_exists($flagFile)) {
        echo "RECOVERY: Solr cluster status:\n\n";
        // Reset flag:
        unlink($flagFile);
    }
    echo $results;
}

