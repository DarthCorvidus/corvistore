# Use Debian 11 as the base image
FROM debian:11

# Update the system
RUN apt-get update

# Install PHP CLI
RUN apt-get -y install php-cli curl git php-xml php-mbstring php-zip sqlite3 php-pdo-sqlite sudo unzip php-curl

RUN useradd -m cpinst -s /bin/bash

RUN mkdir /etc/crow-protect/
RUN mkdir /storage
RUN mkdir /storage/basic01
RUN mkdir /storage/basic02

RUN chown cpinst.cpinst  /home/cpinst/ -R
RUN chown cpinst.cpinst /storage -R



# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
