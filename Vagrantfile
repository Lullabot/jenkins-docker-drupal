# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/bionic64"

  # Common configuration for both VMs.
  config.vm.provision "shell", inline: <<-SHELL
      export DEBIAN_FRONTEND=noninteractive
      apt-get update

      apt-get -y install \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg-agent \
        software-properties-common

      apt-get install -y avahi-daemon
  SHELL

  # This section defines a single Drupal server. In this case, we put all
  # services on a single server. In a production environment, this would
  # be the server you use to run drush commands on.
  config.vm.define "drupal" do |drupal|
    drupal.vm.hostname = "drupal"

    # We need a static IP address as Docker doesn't easily support mDNS.
    drupal.vm.network "private_network", ip: "192.168.101.10"
    drupal.vm.network "forwarded_port", guest: 80, host: 8081, host_ip: "127.0.0.1"

    drupal.vm.provider "virtualbox" do |vb|
      vb.memory = "1024"
      vb.linked_clone = true
    end

    # Install PHP, Apache, MariaDB, and a pre-existing Drupal database.
    drupal.vm.provision "shell", path: "install-drupal.sh"
  end

  # This defines a separate Jenkins server. We run Jenkins itself with Docker,
  # but it's also possible to install Jenkins with apt and only use Docker for
  # job containers.
  config.vm.define "jenkins" do |jenkins|
    jenkins.vm.hostname = "jenkins"
    jenkins.vm.network "private_network", ip: "192.168.101.11"
    jenkins.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"

    jenkins.vm.provider "virtualbox" do |vb|
      vb.memory = "2048"
      vb.linked_clone = true
    end

    jenkins.vm.provision "shell", path: "install-jenkins.sh"
  end

end
