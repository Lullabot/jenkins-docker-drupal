pipeline {
  agent {
    // For Docker, an agent can be 'docker' or 'dockerfile'. Dockerfile allows
    // building of a custom container without requiring it to be published
    // externally.
    dockerfile {
      // This is named 'php' to separate it from a possible 'node' container.
      filename 'Dockerfile.php'

      // Any additional arguments to pass to 'docker run'. In this case, we
      // mount a shared data container to speed up composer operations, and
      // the generated vagrant SSH key for deployments to the Drupal VM.
      args '''
--mount "type=volume,src=dot-composer,dst=/home/jenkins/.composer" \
--mount "type=bind,src=/vagrant/.vagrant/machines/drupal/virtualbox/private_key,dst=/home/jenkins/.ssh/id_rsa"'
'''
    }
  }
  options {
    // We want to be sure that only one deployment job happens at a time. This
    // also has the nice side effect of pausing cron during deployments.
    disableConcurrentBuilds()
  }
  triggers {
    // This re-triggers the job every minute with a 'TimerTrigger'. We do
    // every minute for demo purposes, but '*/5' or '*/15' is fine too.
    cron('* * * * *')

    // This tells Jenkins to poll for changes in version control instead of
    // waiting for webhooks. Use this when trying this out locally, or if your
    // production Jenkins environment is blocked from receiving webhooks by a
    // firewall.
    pollSCM('* * * * *')
  }

  // While a branch can only one pipeline, it can have multiple stages with
  // filters that determine if they should run or not.
  stages {
    // Always run composer install, as we need it for builds, deployments, and
    // cron.
    stage('composer install') {
      steps {
        sh 'composer install --optimize-autoloader --no-dev'
      }
    }

    // If a new commit has triggered a build, create the build archive and save
    // it.
    stage('create artifact') {
      when {
        triggeredBy 'SCMTrigger'
      }
      steps {
        sh 'vendor/bin/robo archive:build'
        archiveArtifacts(artifacts: 'artifacts/*', fingerprint: true, onlyIfSuccessful: true)
      }
    }

    // Push the archive to production.
    stage('Publish master to production') {
      // Only trigger if we are building master, and not a feature branch.
      // Note this will trigger for every master commit, which is great for
      // demos but may not match what you want in production. An alternative is
      // to filter on tags for deployments instead.
      when {
        allOf {
          triggeredBy 'SCMTrigger'
          branch 'master'
        }
      }
      steps {
        // Pause for confirmation to deploy. As is, this stops cron until the
        // deployment is approved or cancelled.
        input 'Deploy master to production?'
        sshPublisher(
          publishers: [
            sshPublisherDesc(
              // The actual SSH configuration has to be managed in the "Publish
              // to SSH" plugin settings in the Global Jenkins configuration.
              // Use the config name you put there here.
              configName: 'drupal',
              transfers: [
                sshTransfer(
                  sourceFiles: 'artifacts/**',
                  // Deploy straight to the home directory.
                  removePrefix: 'artifacts/',
                )
              ]
            )
          ]
        )
      }
    }

    // Calls a robo task that rsync's the last build to the live code directory.
    stage('Deploy master code to production') {
      when {
        allOf {
          triggeredBy 'SCMTrigger'
          branch 'master'
        }
      }
      steps {
        sh 'vendor/bin/robo archive:deploy'
      }
    }

    // Calls a robo task to run updates and config imports.
    stage('Update database') {
      when {
        allOf {
          triggeredBy 'SCMTrigger'
          branch 'master'
        }
      }
      steps {
        sh 'vendor/bin/robo update:database'
      }
    }

    // Make sure the home page can still load. Normally, the host won't need to
    // be manually specified, but unfortunately mDNS resolution within Docker
    // is painfully complex.
    stage('Request home page') {
      when {
        allOf {
          triggeredBy 'SCMTrigger'
          branch 'master'
        }
      }
      steps {
        sh 'curl -v -H "Host: drupal8.local" 192.168.101.10 > /dev/null'
      }
    }

    // Run cron, but only when our time trigger occurs.
    stage('cron') {
      when {
        allOf {
          triggeredBy 'TimerTrigger'
          branch 'master'
        }
      }
      steps {
        sh 'vendor/bin/drush @prod --debug --verbose cron'
      }
    }
  }
}
