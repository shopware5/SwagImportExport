#!/usr/bin/env bash

# Create database
echo "create database __DB_DATABASE__" | mysql -u __DB_USER__ -p__DB_PASSWORD__

# Move to shopware root and setup database
SHOPWARE_ENV=__ENV__ ant -f ./__SHOPWARE_ROOT__build/build.xml build-unit

# Install SwagImportExport
php __SHOPWARE_ROOT__bin/console sw:plugin:refresh --env="__ENV__"
php __SHOPWARE_ROOT__bin/console sw:plugin:install --activate "__PLUGIN__" --env="__ENV__"

php __SHOPWARE_ROOT__bin/console sw:cache:clear --env="__ENV__"