#!/bin/sh
cookie=/tmp/cj_member.txt
rm -f "$cookie"
curl -s -c "$cookie" http://app/login > /tmp/login1.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/login1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
curl -s -b "$cookie" -c "$cookie" -X POST http://app/login \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=ahmad@mainkutu.local" \
  --data-urlencode "password=Ahmad@12345" \
  -o /dev/null -w 'member login: %{http_code} -> %{redirect_url}\n'
curl -s -b "$cookie" http://app/admin/credit-scores -o /tmp/cs_member.html -w 'member /admin/credit-scores: %{http_code} (url=%{url_effective})\n'
