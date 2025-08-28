#!/bin/bash
# setup_initial_users.sh

# Create admin user
docker exec mailserver setup email add admin@jayprasad.com.np

# Create system aliases
docker exec mailserver setup alias add postmaster@jayprasad.com.np admin@jayprasad.com.np
docker exec mailserver setup alias add abuse@jayprasad.com.np admin@jayprasad.com.np
docker exec mailserver setup alias add hostmaster@jayprasad.com.np admin@jayprasad.com.np

# List all users
echo "Current users:"
docker exec mailserver setup email list
