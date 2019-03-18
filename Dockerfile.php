# Use this file for any commands that must run as root.

# Build from Drupal instead of PHP as it already has the various PHP extensions
# we require. Note this is just for a "build" container and has nothing to do
# with the server Drupal is deployed to.
FROM drupal:8.6

# Ensures any questions during apt are answered with defaults instead of
# failing.
ENV DEBIAN_FRONTEND=noninteractive

# composer needs git and unzip
RUN apt-get update \
  && apt-get install -y git unzip

# Install composer globally.
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

# Add a Jenkins user with the same UID and GID as the Jenkins container itself.
# Otherwise, permissions will be wrong in the Jenkins workspace (which exists
# outside of the per-job containers). An alternative would be to use namespace
# mappings: https://docs.docker.com/engine/security/userns-remap/
RUN groupadd -g 1000 jenkins
RUN useradd -u 1000 -g jenkins jenkins

# Create the directory that will be shared between jobs as a Docker named
# volume.
RUN mkdir -p /home/jenkins/.composer
RUN chown -Rv jenkins:jenkins /home/jenkins

# Install prestissimo to speed up builds with parallel downloads.
RUN su -l -c 'composer require hirak/prestissimo' jenkins

# Mark the composer directory as a volume so the above changes are copied into
# the named data volume when it's created. Note all changes to this directory
# after VOLUME will be ignored.
VOLUME /home/jenkins/.composer

# Run as the Jenkins user instead of the www user.
USER jenkins
