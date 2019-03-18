# Jenkins + Docker + Drupal Demo

This repository contains an example of managing Drupal deployments with
Jenkins.

## Goals

* Build configuration is stored in version control, not in UI-driven jobs.
  * All jobs, including periodic tasks, are also in version control.
* Build environments are managed with Docker containers, so no build
  dependencies need to be installed directly on the Jenkins server.
* Jenkins is on it's own server, separate from the Drupal server.

## Demo Requirements

* VirtualBox
* Vagrant
* git
* At least 3GB of RAM.

## Getting Started

1. Clone this repository.
1. Inside the repository, run `vagrant up`.
1. The first boot may take 5-10 minutes. Note the Jenkins admin password shown
   at the very end of the build.
     * If you miss the password, run `./jenkins-password.sh` from your host to
       view it again.
1. On macOS and Linux, Jenkins is available at http://jenkins.local, and Drupal
   is available at http://drupal.local. If you are on Windows and don't have
   Apple's Bonjour drivers installed, you can instead use http://localhost:8080
   and http://localhost:8081.
     * Note that no code is deployed until the first job runs.
1. Run through the Jenkins setup wizard. There is no need to create a separate
   admin user.
     * Install the default selection of plugins when prompted.
1. Install the `Publish over SSH` plugin.
1. Configure a server for the publish plugin:
     * config: `drupal`
     * host: `drupal.local`
     * ssh key path: `/vagrant/.vagrant/machines/drupal/virtualbox/private_key`
1. Open the "Blue Ocean" UI to initialize the jobs.
1. Add a regular "git" repository setting the path to `file:///vagrant`. No
   credentials are required.
1. Jenkins will scan branches and start building.
1. Approve the deployment job, and after it's complete http://drupal.local
   should load a default Drupal installation.
1. To test deployments, commit to the master branch on your host. For example:
   ```
   # Use gsed on macOS, on Linux use sed.
   $ gsed -i 's/Drush Site-Install/My Site/' config/sync/system.site.yml
   $ git add -p config
   $ git commit -m 'Change the site name'
   ```
   Within a minute, Jenkins will detect the new commit and start a deployment
   job.

## Improvements and Limitations

1. Jenkins doesn't appear to allow multiple "pipelines" per project. Instead,
   everything has to be managed with stage filters. It seems like there must be
   a better way to do this.
1. Having multiple cron jobs in a single pipeline seems like it would be
   tricky to maintain.
1. If cron takes longer to run than the Jenkins cron timer, jobs will queue up.
1. Old artifacts are not purged or archived elsewhere, so eventually disk space
   will run out.
1. The "Blue Ocean" UI for editing jobs has many limitations. For example, few
   arguments for the Publish over SSH plugin are exposed. Editing jobs is best
   done by hand in the Jenkinsfile.
