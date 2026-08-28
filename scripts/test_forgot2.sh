#!/bin/sh
set -e
COOKIE=/tmp/cj2.txt
curl -s -c "$COOKIE" http://app/forgot-password > /tmp/p1.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/p1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
curl -s -b "$COOKIE" -X POST http://app/forgot-password \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=admin@kutu.test" -o /dev/null
echo '--- GET after POST ---'
curl -s -b "$COOKIE" http://app/forgot-password | grep -E 'flash|alert|pautan|Sila|reset-password\?token' | head -n 10
