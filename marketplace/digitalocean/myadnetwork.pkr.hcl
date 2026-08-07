variable "do_api_token" {
  type      = string
  sensitive = true
}

variable "application_version" {
  type    = string
  default = "1.0.0"
}

variable "region" {
  type    = string
  default = "sgp1"
}

variable "size" {
  type    = string
  default = "s-2vcpu-4gb"
}

variable "snapshot_name" {
  type    = string
  default = "myadnetwork-ubuntu-24-04"
}

source "digitalocean" "myadnetwork" {
  api_token     = var.do_api_token
  image         = "ubuntu-24-04-x64"
  region        = var.region
  size          = var.size
  ssh_username  = "root"
  snapshot_name = "${var.snapshot_name}-${formatdate("YYYYMMDD-hhmm", timestamp())}"
  tags          = ["marketplace", "myadnetwork", "packer"]
}

build {
  name    = "myadnetwork-marketplace"
  sources = ["source.digitalocean.myadnetwork"]

  provisioner "shell-local" {
    command = "sh '${path.root}/scripts/package.sh' '${path.root}/build/myadnetwork.tar.gz'"
  }

  provisioner "shell" {
    inline = ["cloud-init status --wait"]
  }

  provisioner "file" {
    source      = "${path.root}/build/myadnetwork.tar.gz"
    destination = "/tmp/myadnetwork.tar.gz"
  }

  provisioner "shell" {
    environment_vars = [
      "APPLICATION_NAME=MyAdNetwork",
      "APPLICATION_VERSION=${var.application_version}",
      "DEBIAN_FRONTEND=noninteractive"
    ]
    scripts = [
      "${path.root}/scripts/install-image.sh",
      "${path.root}/scripts/cleanup-image.sh"
    ]
  }
}

