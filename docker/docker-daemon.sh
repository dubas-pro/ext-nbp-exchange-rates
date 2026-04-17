#!/usr/bin/env bash

set -e
set -u
set -o pipefail

if [ -f /var/www/default/site/daemon.php ]; then
    /usr/local/bin/php /var/www/default/site/daemon.php
fi
