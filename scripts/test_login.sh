#!/bin/sh
set -e
COOKIE=/tmp/login_admin.txt
rm -f "$COOKIE"
curl -s -c "$COOKIE" http://app/login > /tmp/login1.html
TOKEN=$(grep -oE 'name="csrf_token" value="[a-f0-9]+"' /tmp/login1.html | head -1 | sed 's/.*value="\([a-f0-9]*\)".*/\1/')
echo "CSRF=$TOKEN"
echo '--- POST /login ---'
curl -s -b "$COOKIE" -c "$COOKIE" -X POST http://app/login \
  --data-urlencode "csrf_token=$TOKEN" \
  --data-urlencode "email=admin@mainkutu.local" \
  --data-urlencode "password=Admin@12345" \
  -o /tmp/login2.html -w 'STATUS:%{http_code} REDIR:%{redirect_url}\n'
echo '--- session cookie after login ---'
grep mainkutu_session "$COOKIE" | awk '{print $7}'
echo '--- GET /dashboard (follow) ---'
curl -s -L -b "$COOKIE" -c "$COOKIE" http://app/dashboard -o /tmp/dash.html -w 'STATUS:%{http_code} URL:%{url_effective}\n'
echo '--- dashboard markers ---'
grep -oE 'Dashboard Pentadbir|Dashboard Ahli|Buka Dashboard Pentadbir|Statistik|<title>[^<]+</title>' /tmp/dash.html | head -n 8
