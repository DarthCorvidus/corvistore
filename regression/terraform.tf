provider "libvirt" {
  uri = "qemu:///system"
}

resource "libvirt_volume" "centos8" {
  name   = "centos8.qcow2"
  pool   = "default"
  source = "/var/lib/libvirt/images/CentOS-Stream-GenericCloud-8-20210603.0.x86_64.qcow2"
  format = "qcow2"
}

resource "libvirt_domain" "domain-centos" {
  name   = "<hostname>"
  memory = "8192"
  vcpu   = 2

  network_interface {
    network_name = "default"
    hostname     = "<hostname>"
    addresses    = ["<ip_address>/24"]
  }

  disk {
    volume_id = libvirt_volume.centos8.id
  }

  cloudinit = libvirt_cloudinit_disk.cloudinit.id
}

resource "libvirt_cloudinit_disk" "cloudinit" {
  name           = "instance_cloudinit.iso"
  user_data      = data.template_file.user_data.rendered
}

data "template_file" "user_data" {
  template = file("${path.module}/user_data")
}

