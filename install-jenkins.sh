#!/bin/bash

# Install Docker CE.
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add -
add-apt-repository \
 "deb [arch=amd64] https://download.docker.com/linux/ubuntu \
 $(lsb_release -cs) \
 stable"
apt-get -y install docker-ce docker-ce-cli containerd.io

# ping's gid is the docker group id on Ubuntu, and this saves us from
# from having to configure user namespaces.
# Mount in /vagrant for the repository and SSH keys.
# Allow access to the Docker socket to run containers for jobs.
docker run -d \
  --restart always \
  --name jenkins \
  --group-add ping \
  -p 8080:80 \
  -v /vagrant:/vagrant \
  -v /var/run/docker.sock:/var/run/docker.sock \
  jenkinsci/blueocean

echo "Waiting for Jenkins admin password:"
sleep 30 && docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword
