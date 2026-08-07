#!/bin/sh
set -eu

rm -f /tmp/myadnetwork.tar.gz
apt-get -qqy autoremove
apt-get -qqy clean
rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Jangan membawa identity, log, atau cloud-init state build Droplet ke snapshot.
cloud-init clean --logs --machine-id
find /var/log -type f -exec truncate -s 0 {} \;
rm -f /root/.bash_history
rm -f /etc/ssh/ssh_host_*

# Validator resmi harus selalu diambil dari canonical repository saat build.
validator=/usr/local/sbin/marketplace-image-check
curl -fsSL \
  https://raw.githubusercontent.com/digitalocean/marketplace-partners/master/scripts/99-img-check.sh \
  -o "$validator" \
  || curl -fsSL \
  https://raw.githubusercontent.com/digitalocean/marketplace-partners/master/scripts/999-img_check.sh \
  -o "$validator"
chmod 0755 "$validator"
"$validator"
rm -f "$validator"
