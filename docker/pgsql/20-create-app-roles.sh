#!/bin/sh
# Creates the application roles and prepares the development and testing databases.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
    -v migrator_password="$APP_DB_MIGRATOR_PASSWORD" \
    -v runtime_password="$APP_DB_RUNTIME_PASSWORD" \
    -v reporting_password="$APP_DB_REPORTING_PASSWORD" \
    -f /opt/app-sql/roles/create_roles.sql

for database in "$POSTGRES_DB" testing; do
    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$database" \
        -f /opt/app-sql/roles/prepare_database.sql
done
