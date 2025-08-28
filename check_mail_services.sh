#!/bin/bash
# check_mail_services.sh

# Check if Docker containers are running
if ! docker ps | grep -q mailserver; then
    echo "Mailserver container is down!" | mail -s "Mailserver Alert" admin@jayprasad.com.np
fi

if ! docker ps | grep -q webmail; then
    echo "Webmail container is down!" | mail -s "Webmail Alert" admin@jayprasad.com.np
fi

# Check disk space
DISK_USAGE=$(df / | awk 'END{print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    echo "Disk usage is at $DISK_USAGE%" | mail -s "Disk Space Alert" admin@jayprasad.com.np
fi

# Check if services are responding
if ! nc -z localhost 25; then
    echo "SMTP service is not responding!" | mail -s "SMTP Alert" admin@jayprasad.com.np
fi

if ! curl -s -o /dev/null -w "%{http_code}" https://mail.jayprasad.com.np | grep -q "200"; then
    echo "Webmail is not responding!" | mail -s "Webmail Alert" admin@jayprasad.com.np
fi
