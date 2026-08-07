#!/bin/sh
set -eu

apt-get -qq update
apt-get -qqy -o Dpkg::Options::=--force-confdef -o Dpkg::Options::=--force-confold full-upgrade
apt-get -qqy install ca-certificates cron curl git openssl ufw

install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
chmod a+r /etc/apt/keyrings/docker.asc
. /etc/os-release
printf '%s\n' \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable" \
  > /etc/apt/sources.list.d/docker.list
apt-get -qq update
apt-get -qqy install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

install -m 0755 -d /opt/myadnetwork
tar -xzf /tmp/myadnetwork.tar.gz -C /opt/myadnetwork
chmod 0755 /opt/myadnetwork/install/vps-install.sh
chmod 0755 /opt/myadnetwork/marketplace/digitalocean/first-boot.sh

install -m 0644 /opt/myadnetwork/marketplace/digitalocean/myadnetwork-first-boot.service \
  /etc/systemd/system/myadnetwork-first-boot.service
systemctl daemon-reload
systemctl enable cron.service docker.service myadnetwork-first-boot.service

install -m 0644 /opt/myadnetwork/marketplace/digitalocean/myadnetwork.cron \
  /etc/cron.d/myadnetwork

mkdir -p /var/lib/digitalocean
printf 'application_name=%s\napplication_version=%s\n' \
  "${APPLICATION_NAME}" "${APPLICATION_VERSION}" \
  > /var/lib/digitalocean/application.info

cd /opt/myadnetwork
docker compose build web

ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw --force enable

cat > /etc/update-motd.d/99-myadnetwork <<'EOF'
#!/bin/sh
if [ -f /root/.myadnetwork_credentials ]; then
    echo "MyAdNetwork siap. Kredensial: sudo cat /root/.myadnetwork_credentials"
elif systemctl is-failed --quiet myadnetwork-first-boot.service; then
    echo "Setup MyAdNetwork gagal. Periksa: journalctl -u myadnetwork-first-boot"
else
    echo "Setup pertama MyAdNetwork sedang berjalan."
fi
EOF
chmod 0755 /etc/update-motd.d/99-myadnetwork
