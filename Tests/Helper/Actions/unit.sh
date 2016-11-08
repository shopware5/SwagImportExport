#!/usr/bin/env bash

# Setup test environment
INCLUDE: ./Tests/Helper/Actions/setup.sh

# Execute unit tests
SHOPWARE_ENV=__ENV__ phpunit

# Cleanup test environment
INCLUDE: ./Tests/Helper/Actions/cleanup.sh
