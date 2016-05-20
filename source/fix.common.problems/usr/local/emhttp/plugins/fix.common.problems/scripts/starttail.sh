#!/bin/bash

mkdir -p /boot/logs

echo "tail -f /var/log/syslog > /boot/logs/syslog.txt & " | at NOW -M > /dev/null 2>&1
echo "/usr/local/emhttp/plugins/fix.common.problems/scripts/startDiagnostics.php & " | at NOW -M > /dev/null 2>&1
logger "Fix Common Problems: Troubleshooting mode activated"


