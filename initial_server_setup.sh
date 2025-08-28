#!/bin/bash
# initial_server_setup.sh

# Update system
sudo apt update && sudo apt upgrade -y

# Create deployer user
sudo adduser deployer
sudo usermod -aG sudo deployer

# Copy SSH keys from root to deployer (if needed)
sudo mkdir -p /home/deployer/.ssh
sudo cp /root/.ssh/authorized_keys /home/deployer/.ssh/
sudo chown -R deployer:deployer /home/deployer/.ssh
sudo chmod 700 /home/deployer/.ssh
sudo chmod 600 /home/deployer/.ssh/authorized_keys

# Configure SSH (disable root login and password authentication)
sudo sed -i 's/^PermitRootLogin.*/PermitRootLogin no/' /etc/ssh/sshd_config
sudo sed -i 's/^#PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config
sudo sed -i 's/^PasswordAuthentication.*/PasswordAuthentication no/' /etc/ssh/sshd_config

# Enable automatic security updates
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades

# Configure firewall
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 25/tcp
sudo ufw allow 587/tcp
sudo ufw allow 993/tcp
sudo ufw enable

# Install Certbot for TLS certificates
sudo apt install certbot -y

# Set hostname
sudo hostnamectl set-hostname mail.jayprasad.com.np

echo "Initial server setup complete. Please restart SSH service and reconnect as deployer user."
