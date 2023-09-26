# Use Cent
FROM centos:stream8

# Update the system
RUN yum update -y

# Install PHP CLI
RUN yum install -y php-cli curl git php-xml php-mbstring php-zip sqlite php-pdo sudo unzip php-curl php-json php-process

RUN useradd -m cpinst -s /bin/bash

RUN echo 'cpinst ALL=(ALL) NOPASSWD: ALL' > /etc/sudoers.d/cpinst

RUN mkdir /etc/crow-protect/
RUN mkdir /storage
RUN mkdir /storage/basic01
RUN mkdir /storage/basic02

RUN chown cpinst.cpinst  /home/cpinst/ -R
RUN chown cpinst.cpinst /storage -R

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
