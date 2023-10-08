#!/bin/bash
sudo virsh destroy docker01
sudo virsh undefine docker01 --remove-all-storage
virt-builder centosstream-8 \
	--size 20G \
	--output ~/docker01.qcow2 \
	--format qcow2 \
	--hostname docker.telton.de \
	--root-password password:talrasha \
	--ssh-inject root
#--firstboot centos8-script.sh

sudo /home/hm/moveVM.sh ~/docker01.qcow2 /virtual/docker01.qcow2
sudo virt-install \
	--import \
	--name docker01 \
	--ram=8192 \
	--disk path=/virtual/docker01.qcow2,format=qcow2,size=20 \
	--os-variant=centos7.0 \
	--noautoconsole \
	--network bridge=br0,model=virtio,mac=52:53:00:a1:a6:23
ssh-keygen -f /home/hm/.ssh/known_hosts -R docker01.telton.de
