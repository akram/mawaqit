#!/bin/bash

action=touch

if [[ "$1" == "remove" ]]; then
    action="rm -f"
fi

ssh -p 1983 -t mawaqit@$MAWAQIT_PROD_IP "docker exec mawaqit_nginx sh -c \"$action docker/data/maintenance && nginx -s reload\""