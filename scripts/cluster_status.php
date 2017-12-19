#!/usr/bin/php
<?php

$port = isset($argv[1]) ? $argv[1] : '8983';

$errors = false;
$state = `curl -s "http://localhost:$port/solr/admin/collections?action=clusterstatus&wt=json"`;

if (empty($state)) {
    echo "Could not get Solr status\n";
    exit(1);
}

$state = json_decode($state, true);
foreach ($state['cluster']['collections'] as $collectionName => $collection) {
    echo "Collection $collectionName:\n";
    foreach ($collection['shards'] as $shardName => $shard) {
        echo "  Shard $shardName (" . $shard['state'] . "):\n";
        foreach ($shard['replicas'] as $coreNodeName => $replica) {
            $info = $coreNodeName;
            if (!empty($replica['leader'])) {
                $info .= ', leader';
            }
            echo '    ' . substr($replica['state'] . '         ', 0, 10) . ' ' . $replica['core'] . " ($info) at " . $replica['node_name'] . "\n";
        }
    }
    echo "\n";
}

