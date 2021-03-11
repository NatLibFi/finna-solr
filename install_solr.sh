#!/bin/bash -e

DIR=`dirname $0`

if [ -z "$SOLR_USER" ]; then
  SOLR_USER=solr
fi
JTS_VERSION="1.15.0"
JTS_URL="https://repo1.maven.org/maven2/org/locationtech/jts/jts-core/$JTS_VERSION/jts-core-$JTS_VERSION.jar"

# Check if the correct version is already installed
read -r REQUIRED_VERSION<"$DIR/required_solr_version"

INSTALLED_VERSION=''
if [[ -e "$DIR/vendor/installed_solr_version" ]];
then
    read -r INSTALLED_VERSION<"$DIR/vendor/installed_solr_version"
fi

if [[ "$INSTALLED_VERSION" == "$REQUIRED_VERSION" ]];
then
    echo Solr version $INSTALLED_VERSION already installed. Remove $DIR/vendor/installed_solr_version to force reinstallation.
    exit 0
fi

if [[ "$INSTALLED_VERSION" != "" ]];
then
    echo Upgrading Solr from version $INSTALLED_VERSION to $REQUIRED_VERSION...
fi

# Download package if necessary
if [[ ! -e "$DIR/downloads" ]];
then
    mkdir $DIR/downloads
fi



if [[ ! -e "$DIR/downloads/solr-$REQUIRED_VERSION.tgz" ]];
then
    echo "Downloading Solr $REQUIRED_VERSION from Funet mirror..."
    curl "http://www.nic.funet.fi/pub/mirrors/apache.org/lucene/solr/$REQUIRED_VERSION/solr-$REQUIRED_VERSION.tgz" > $DIR/downloads/solr-$REQUIRED_VERSION.tgz

    size=$(du -m "$DIR/downloads/solr-$REQUIRED_VERSION.tgz" | cut -f 1)
    if [[ $size -lt 100 ]];
    then
        # File too small, try Solr archives
        echo "Downloading Solr $REQUIRED_VERSION from Apache Solr archives..."
        curl "http://archive.apache.org/dist/solr/$REQUIRED_VERSION/solr-$REQUIRED_VERSION.tgz" > $DIR/downloads/solr-$REQUIRED_VERSION.tgz
    fi

    size=$(du -m "$DIR/downloads/solr-$REQUIRED_VERSION.tgz" | cut -f 1)
    if [[ $size -lt 100 ]];
    then
        # File still too small, try Lucene archives
        echo "Downloading Solr $REQUIRED_VERSION from Apache Lucene archives..."
        curl "http://archive.apache.org/dist/lucene/solr/$REQUIRED_VERSION/solr-$REQUIRED_VERSION.tgz" > $DIR/downloads/solr-$REQUIRED_VERSION.tgz
    fi

    size=$(du -m "$DIR/downloads/solr-$REQUIRED_VERSION.tgz" | cut -f 1)
    if [[ $size -lt 100 ]];
    then
        # File still too small
        echo "Downloaded file too small"
        rm $DIR/downloads/solr-$REQUIRED_VERSION.tgz
        exit 1
    fi
fi

# Download JTS
if [[ ! -e "$DIR/downloads/jts-core-$JTS_VERSION.jar" ]];
then
    echo "Downloading JTS Core..."
    curl "$JTS_URL" > $DIR/downloads/jts-core-$JTS_VERSION.jar
fi

# Remove any previous Solr version
if [[ -e $DIR/vendor ]];
then
    echo "Removing previous version..."
    rm -r $DIR/vendor
fi

# Extract Solr
echo "Extracting Solr..."
mkdir $DIR/vendor
tar xzf $DIR/downloads/solr-$REQUIRED_VERSION.tgz --strip-components=1 -C vendor

# Copy JTS jars
echo "Copying JTS..."
cp $DIR/downloads/jts-core-$JTS_VERSION.jar $DIR/vendor/server/solr-webapp/webapp/WEB-INF/lib/

# Set permissions
if [[ `id -u $SOLR_USER 2>/dev/null || echo -1` -ge 0 ]];
then
    echo "Updating permissions..."
    chown -R $SOLR_USER $DIR/vendor
    chown -R $SOLR_USER $DIR/vufind
else
    echo "WARNING: User '$SOLR_USER' not found, skipping permissions setup"
fi

# Copy libs
echo "Copying ICU libraries..."

if ls $DIR/vufind/lib/icu4j-*.jar > /dev/null 2>&1;
then
    echo "Removing old icu4j jar from $DIR/vufind/lib..."
    rm $DIR/vufind/lib/icu4j-*.jar
fi
if ls $DIR/vufind/lib/lucene-analyzers-icu-*.jar > /dev/null 2>&1;
then
    echo "Removing old lucene-analyzers-icu jar from $DIR/vufind/lib..."
    rm $DIR/vufind/lib/lucene-analyzers-icu-*.jar
fi
cp $DIR/vendor/contrib/analysis-extras/lib/icu4j-*.jar $DIR/vufind/lib/
cp $DIR/vendor/contrib/analysis-extras/lucene-libs/lucene-analyzers-icu-*.jar $DIR/vufind/lib/

echo $REQUIRED_VERSION > $DIR/vendor/installed_solr_version

echo Done.
