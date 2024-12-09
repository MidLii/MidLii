#!/bin/sh
echo "Starting converter job..."

CJ_USERNAME=$1
CJ_PASSWORD=$2

echo "$CJ_USERNAME:$CJ_PASSWORD"

while true
do
    curl -u "$CJ_USERNAME:$CJ_PASSWORD" http://web/_crons/converter.php & curl -u "$CJ_USERNAME:$CJ_PASSWORD" http://web/_crons/converter.php & curl -u "$CJ_USERNAME:$CJ_PASSWORD" http://web/_crons/converter.php 
    echo -e "\nSleeping for 30s..."
    sleep 30
done
