packer {
  required_version = ">= 1.10.0"

  required_plugins {
    digitalocean = {
      source  = "github.com/digitalocean/digitalocean"
      version = ">= 1.4.1"
    }
  }
}

