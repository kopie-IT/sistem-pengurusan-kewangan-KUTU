#!/bin/bash
set -e

# Ensure storage directories are writable
mkdir -p /var/www/html/storage/logs /var/www/html/storage/uploads
chown -R www-data:www-data /var/www/html/storage
chmod -R 775 /var/www/html/storage

# Execute the main container command
exec "$@"
