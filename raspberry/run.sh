#!/bin/bash

if [[ -f ~/mawaqit/docker/data/online_url.txt ]]; then
    url=`cat ~/mawaqit/docker/data/online_url.txt`
fi

i=0
while ! wget -q --spider --timeout=2 $url; do
  sleep 2
  ((i+=1))
  if (( $i == 20 )); then
    # warmup cache and set local url
    docker-compose exec mawaqit_php sh -c "bin/console c:w -e raspberry"
    docker-compose exec mawaqit_php chmod 777 -R var/cache

    url=`cat ~/mawaqit/docker/data/offline_url.txt`
    if [ -z "$url" ]; then
        url=http://mawaqit.local/en/id/1
    fi

    break;
  fi 
done

chromium-browser --app=$url --start-fullscreen --start-maximized
