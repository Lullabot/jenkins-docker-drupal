<?php

use Symfony\Component\Process\Process;

/**
 * Console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks
{

  /**
   * Generate an archive of the current project.
   */
  public function archiveBuild() {
    $e = new Process('git rev-parse --short HEAD');
    $e->run();
    $short = trim($e->getOutput());
    $date = (new \DateTime())->format('Y-m-d');
    $name = "drupal-$date-$short.tar.gz";

    $this->taskFileSystemStack()
      ->mkdir('artifacts')
      ->run();

    $this->taskPack('artifacts/' . $name)
      ->add('RoboFile.php')
      ->add('config')
      ->add('composer.json')
      ->add('composer.lock')
      ->add('drush')
      ->add('load.environment.php')
      ->add('scripts')
      ->add('vendor')
      ->add('web')
      ->run();

    // Store the file name of the last build, since rebuilds may make this not
    // chronological.
    file_put_contents('artifacts/last_build.txt', $name);
  }

  /**
   * Deploy the last-built archive to production.
   */
  public function archiveDeploy() {
    $this->taskExecStack()
      ->exec('vendor/bin/drush @prod ssh --cd /home/vagrant \'mkdir -p code\'')
      ->exec('vendor/bin/drush @prod ssh --cd /home/vagrant "rm -rf update && mkdir update"')
      ->exec('vendor/bin/drush @prod ssh --cd /home/vagrant "tar -C update -xzvf $(cat artifacts/last_build.txt)"')
      ->exec('vendor/bin/drush @prod ssh --cd /home/vagrant "rsync -av --delete-before --exclude web/sites/default/files update/ code/"')
      ->run();
  }

  /**
   * Run database updates.
   */
  public function updateDatabase() {
    $this->taskExecStack()
      ->exec('vendor/bin/drush @prod updatedb -y')
      ->exec('vendor/bin/drush @prod config:import -y')
      ->exec('vendor/bin/drush @prod cache:rebuild -y')
      ->run();
  }

}
