#!/bin/sh
set -e
COOKIE=/tmp/cj4.txt
echo '--- 1. GET /forgot-password ---'
curl -s -c "$COOKIE" http://app/forgot-password > /tmp/p1.html
ls -la "$COOKIE"
echo '--- cookie content ---'
cat "$COOKIE"
echo
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/p1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
echo "CSRF=$TOKEN"
echo '--- 2. POST /forgot-password ---'
curl -s -b "$COOKIE" -c "$COOKIE" -X POST http://app/forgot-password \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=admin@kutu.test" \
  -o /tmp/p2.html -w 'STATUS:%{http_code} REDIR:%{redirect_url}\n'
echo '--- 3. follow redirect ---'
curl -s -L -b "$COOKIE" -c "$COOKIE" http://app/forgot-password -o /tmp/p3.html -w 'STATUS:%{http_code}\n'
echo '--- flash on third page ---'
grep -oE 'flash-(success|info|error)[^<]*<div class="flash-body">[^<]+' /tmp/p3.html | head -n 5
