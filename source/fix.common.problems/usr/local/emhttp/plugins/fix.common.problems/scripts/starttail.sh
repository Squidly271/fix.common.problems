#!/bin/bash

mkdir -p /boot/logs

echo "tail -f /var/log/syslog > /boot/logs/syslog.txt & " | at NOW -M > /dev/null 2>&1

