#!/bin/bash

# online site
url=https://mawaqit.net/fr/mosquee-essunna-houilles
if [ -f ~/Desktop/online_site.txt ]; then
    url=`cat ~/Desktop/online_site.txt`
fi

i=0
while ! wget -q --timeout=2 --spider $url; do
    sleep 1
    i=$(( $i + 1 ))
    if (( $i == 10 )); then
       url=http://mawaqit.local/mosquee
       break
    fi
done

chromium-browser --app=$url --start-fullscreen --start-maximized
