#!/usr/bin/php
<?php

$errors = false;
$state = `curl -s "http://localhost:8983/solr/admin/collections?action=clusterstatus&wt=json"`;

if (empty($state)) {
    echo "Could not get Solr status";
    exit(1);
}

$state = json_decode($state, true);
foreach ($state['cluster']['collections'] as $collectionName => $collection) {
    echo "Collection $collectionName:\n";
    foreach ($collection['shards'] as $shardName => $shard) {
        echo "  Shard $shardName " . $shard['state'] . ":\n";
        foreach ($shard['replicas'] as $coreNodeName => $replica) {
            echo '    ' . substr($replica['state'] . '         ', 0, 10) . ' ' . $replica['core'] . " ($coreNodeName) at " . $replica['node_name'] . "\n";
        }
    } 
}

