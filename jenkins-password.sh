#!/bin/bash

echo "Jenkins is available at http://localhost:8080/"
echo "The Jenkins admin password is:"
vagrant ssh jenkins -c 'sudo docker exec jenkins cat /var/jenkins_home/secrets/initialAdminPassword'
