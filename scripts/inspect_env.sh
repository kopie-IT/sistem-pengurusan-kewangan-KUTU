#!/bin/sh
echo '--- .env exists? ---'
ls -la .env 2>/dev/null || echo "no .env"
echo '--- .env.docker exists? ---'
ls -la .env.docker 2>/dev/null || echo "no .env.docker"
echo '--- APP_DEBUG in .env ---'
grep '^APP_DEBUG' .env 2>/dev/null || echo "(no APP_DEBUG line in .env)"
echo '--- getenv APP_DEBUG ---'
php -r 'echo (getenv("APP_DEBUG") ?: "<unset>") . PHP_EOL;'
