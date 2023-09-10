#!/bin/bash

mkdir -p "/tmp/GitHub/fix.common.problems/source/fix.common.problems/usr/local/emhttp/plugins/fix.common.problems/"

cp /usr/local/emhttp/plugins/fix.common.problems/* /tmp/GitHub/fix.common.problems/source/fix.common.problems/usr/local/emhttp/plugins/fix.common.problems -R -v -p
find . -maxdepth 9999 -noleaf -type f -name "._*" -exec rm -v "{}" \;

