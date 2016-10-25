#!/usr/bin/env bash

# Clean up database
echo "drop database __DB_DATABASE__" | mysql -u __DB_USER__ -p__DB_PASSWORD__

# Remove config file
rm ./__SHOPWARE_ROOT__config_"__ENV__".php
