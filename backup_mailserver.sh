#!/bin/bash
# backup_mailserver.sh

# Configuration
BACKUP_DIR="/backup/mailserver"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory
mkdir -p $BACKUP_DIR/$DATE

# Backup mail data
docker exec mailserver tar -czf /tmp/maildata.tar.gz /var/mail
docker cp mailserver:/tmp/maildata.tar.gz $BACKUP_DIR/$DATE/maildata.tar.gz
docker exec mailserver rm /tmp/maildata.tar.gz

# Backup mail state (Dovecot, ClamAV, etc.)
docker exec mailserver tar -czf /tmp/mailstate.tar.gz /var/mail-state
docker cp mailserver:/tmp/mailstate.tar.gz $BACKUP_DIR/$DATE/mailstate.tar.gz
docker exec mailserver rm /tmp/mailstate.tar.gz

# Backup configuration
tar -czf $BACKUP_DIR/$DATE/config.tar.gz ./config/ ./docker-compose.yml ./nginx/

# Backup Let's Encrypt certificates
tar -czf $BACKUP_DIR/$DATE/letsencrypt.tar.gz /etc/letsencrypt/

# Create a single archive
tar -czf $BACKUP_DIR/mailserver_backup_$DATE.tar.gz -C $BACKUP_DIR/$DATE .

# Upload to remote storage (example with AWS S3)
# aws s3 cp $BACKUP_DIR/mailserver_backup_$DATE.tar.gz s3://your-bucket/mailserver-backups/

# Clean up old backups
find $BACKUP_DIR -name "mailserver_backup_*.tar.gz" -mtime +$RETENTION_DAYS -delete
rm -rf $BACKUP_DIR/$DATE

echo "Backup completed: $BACKUP_DIR/mailserver_backup_$DATE.tar.gz"
