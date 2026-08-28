#!/bin/sh
set -e
# Apply the migration via the app container which has access to the SQL file.
docker compose exec -T app sh -c "cat database/migrations/003_settings.sql" \
  | docker compose exec -T db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" mainkutu
echo "---"
docker compose exec -T db mysql -uroot -p"$MYSQL_ROOT_PASSWORD" mainkutu -e "SELECT \`key\`, IFNULL(value,'<null>') AS value FROM app_settings"
