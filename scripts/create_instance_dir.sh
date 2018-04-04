#!/bin/bash

if [ -z "$SOLR_USER" ]; then
  SOLR_USER=solr
fi

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
  ln -s ../vufind/log4j.properties $DIR/
  if [[ `id -u $SOLR_USER 2>/dev/null || echo -1` -ge 0 ]];
  then
    chown -R solr $DIR
  else
    echo "WARNING: User '$SOLR_USER' not found, skipping permissions setup"
  fi

  echo "$DIR created"
done

