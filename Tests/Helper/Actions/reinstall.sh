#!/usr/bin/env bash

php __SHOPWARE_ROOT__bin/console sw:plugin:uninstall "__PLUGIN__" --env="__ENV__"

# Install SwagImportExport
php __SHOPWARE_ROOT__bin/console sw:plugin:refresh --env="__ENV__"
php __SHOPWARE_ROOT__bin/console sw:plugin:install --activate "__PLUGIN__" --env="__ENV__"

php __SHOPWARE_ROOT__bin/console sw:cache:clear --env="__ENV__"