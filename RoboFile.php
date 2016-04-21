<?php
/**
 * @file RoboFile.php
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 *
 * @author leandro713 <leandro@leandro.org>
 */

require "vendor/autoload.php";
/**
 *
 */
class RoboFile extends \Robo\Tasks {

  protected  $yaml;
  protected  $sites;


  /**
   *
   */
  public function __construct() {

    $this->yaml = new Symfony\Component\Yaml\Parser();
    $this->sites = $this->yaml->parse(file_get_contents('./sites.yml'));
  }

  /**
   * Clears the cache.
   */
  public function cc($site) {

    $this->say("Clearing cache in " . $this->sites[$site]['path']);
    $this->taskExec('drupal')
            ->arg('cr')
            ->arg('all')
            ->option("root", $this->sites[$site]['path'])
            ->run();
  }

  /**
   * Phpcbf a file.
   */
  public function phpcbf($file) {

    $this->say("Executing phpcbf over " . $file);
    $this->taskExec('phpcbf')
            ->option("standard=", "Drupal,DrupalPractice")
            ->arg($file)
            ->run();
  }

  /**
   * Https://github.com/jmolivas/phpqa.
   */
  public function qa($file) {

    $this->taskExec('phpqa')
            ->arg("analyze")
            ->option("project=", "drupal")
            ->option("files=", $file)
            ->run();
  }

  /**
   * Performs determined actions on each file modification
   * in the $code_dir directory.
   */
  public function watch($site) {

    $this->taskWatch()
            ->monitor(
              $this->sites[$site]['code_dir'], function ($event) use ($site) {
                  if ($event->getTypeString() !== 'modify') {
                      return;
                  }
                  else {

                      $this->cc($site);

                      $this->phpcbf((string) $event->getResource());

                      /*
                      if (file_exists((string) $event->getResource())) {
                      $this->qa((string) $event->getResource());
                      }
                      */

                  }
              }
            )
        ->run();
  }

  /**
   * Shows configuration in the YML file for a given site.
   */
  public function debug($site) {

    if ($site == "all") {
      foreach ($this->sites as $k => $v) {
        echo $k . "\n\r";
        $this->say(implode("\n\r", $v));
      }
    }
    else {
      $this->say(implode("\n\r", $this->sites[$site]));
    }
  }

  /**
   * Manages the CHANGELOG for this program.
   */
  public function changelog() {

    $version = "0.1.0";
    $this->taskChangelog()
            ->version($version)
            ->change("released to github")
            ->run();
  }

}
