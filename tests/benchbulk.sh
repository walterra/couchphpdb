#!/bin/bash

# via https://gist.github.com/jchris/79279

# usage: time benchbulk.sh dbname
# it takes about 30 seconds to run on my old MacBook

BULKSIZE=1000
DOCSIZE=100
INSERTS=10
ROUNDS=10
DBURL="http://localhost:5984/$1"
POSTURL="$DBURL/_bulk_docs"
 
function make_bulk_docs() {
  ROW=0
  SIZE=$(($1-1))
  START=$2
  BODYSIZE=$3  
  
  BODY=$(printf "%0${BODYSIZE}d")
 
  echo '{"docs":['
  while [ $ROW -lt $SIZE ]; do
    printf '{"_id":"%020d", "body":"'$BODY'"},' $(($ROW + $START))
    let ROW=ROW+1
  done
  printf '{"_id":"%020d", "body":"'$BODY'"}' $(($ROW + $START))
  echo ']}'
}
 
echo "Making $INSERTS bulk inserts of $BULKSIZE docs each"
 
echo "Attempt to delete db at $DBURL"
curl -X DELETE $DBURL -w\\n
 
echo "Attempt to create db at $DBURL"
curl -X PUT $DBURL -w\\n
 
echo "Running $ROUNDS rounds of $INSERTS inserts to $POSTURL"
RUN=0
while [ $RUN -lt $ROUNDS ]; do
 
  POSTS=0
  while [ $POSTS -lt $INSERTS ]; do
    STARTKEY=$[ POSTS * BULKSIZE + RUN * BULKSIZE * INSERTS ]
    echo "startkey $STARTKEY bulksize $BULKSIZE"
    echo $(make_bulk_docs $BULKSIZE $STARTKEY $DOCSIZE) | curl -T - -X POST $POSTURL -w%{http_code}\ %{time_total}\ sec\\n -o out.file 2> /dev/null &
    let POSTS=POSTS+1
  done
 
  wait
  let RUN=RUN+1
done
 
curl $DBURL -w\\n
