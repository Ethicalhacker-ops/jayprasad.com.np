#!/bin/bash
# setup_dkim.sh

# Generate DKIM keys
docker exec mailserver generate-dkim-config

# Extract the public key
SELECTOR=$(docker exec mailserver cat /tmp/docker-mailserver/opendkim/keys/jayprasad.com.np/mail.txt | grep -oP 'dns_txt.*?"\K[^"]+')
echo "DKIM TXT record for selector 'mail':"
echo "Name: mail._domainkey.jayprasad.com.np"
echo "Value: $SELECTOR"

# Instructions for DNS
echo ""
echo "Add this TXT record to your DNS:"
echo "Name: mail._domainkey"
echo "Type: TXT"
echo "Value: $SELECTOR"
echo "TTL: 3600"
