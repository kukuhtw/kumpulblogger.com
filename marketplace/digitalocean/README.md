# DigitalOcean Marketplace build files

Jalankan dari root repository setelah seluruh perubahan di-commit:

```bash
packer init marketplace/digitalocean
PKR_VAR_do_api_token="$DIGITALOCEAN_API_TOKEN" \
  packer validate marketplace/digitalocean
PKR_VAR_do_api_token="$DIGITALOCEAN_API_TOKEN" \
  packer build marketplace/digitalocean
```

Panduan lengkap: `docs/operations/DIGITALOCEAN_MARKETPLACE.md`.

