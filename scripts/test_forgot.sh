#!/bin/sh
set -e
COOKIE=/tmp/cj.txt
curl -s -c "$COOKIE" http://app/forgot-password > /tmp/p1.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/p1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
echo "CSRF=$TOKEN"
curl -s -b "$COOKIE" -X POST http://app/forgot-password \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=admin@kutu.test" \
  -o /tmp/p2.html -w 'STATUS:%{http_code} REDIR:%{redirect_url}\n'
echo '--- body ---'
head -c 1500 /tmp/p2.html
