#!/bin/bash
set -e
COOKIE_JAR=/tmp/mk_cookies.txt
rm -f "$COOKIE_JAR"

echo "=== 1. GET /login to obtain CSRF token ==="
LOGIN_HTML=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" http://localhost:8090/login)
echo "HTTP body length: ${#LOGIN_HTML}"
CSRF_TOKEN=$(echo "$LOGIN_HTML" | grep -oP 'name="_token"\s+value="\K[^"]+' | head -1)
echo "CSRF token: ${CSRF_TOKEN:0:20}..."

echo ""
echo "=== 2. POST /login with admin credentials ==="
LOGIN_RESP=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -i \
    -X POST http://localhost:8090/login \
    -d "_token=$CSRF_TOKEN" \
    -d "email=admin@mainkutu.local" \
    -d "password=Admin@12345")
echo "$LOGIN_RESP" | head -20
echo ""
echo "$LOGIN_RESP" | grep -E "^(HTTP|Location|Set-Cookie)" | head -10

echo ""
echo "=== 3. Follow redirect to see where we end up ==="
FINAL=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -L -o /tmp/mk_final.html -w "%{http_code}|%{url_effective}" http://localhost:8090/login \
    -X POST \
    -d "_token=$CSRF_TOKEN" \
    -d "email=admin@mainkutu.local" \
    -d "password=Admin@12345")
echo "Final response: $FINAL"
echo "--- Body preview ---"
head -c 800 /tmp/mk_final.html
echo ""
