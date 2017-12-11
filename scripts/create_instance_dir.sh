#!/bin/bash

if [[ $# -eq 0 ]]
then
  echo "Usage: $0 instancedir [instancedir...]"
  exit 1
fi

for DIR in "$@"
do
  if [[ -e $DIR ]]; then
    echo "$DIR already exists, skipping"
    continue
  fi

  mkdir $DIR
  cp vufind/solr.xml $DIR/
  mkdir $DIR/logs
  ln -s ../vufind/lib $DIR/lib
  chown -R solr $DIR
  
  echo "$DIR created"
done

