#!/bin/bash

echo '/usr/local/emhttp/plugins/community.applications/scripts/moveToCache.php "$1" & > /dev/null  | at -M NOW >/dev/null 2>&1

