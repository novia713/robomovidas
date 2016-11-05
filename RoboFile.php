<?php
/**
 * @file RoboFile.php
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 *
 * @author leandro713 <leandro@leandro.org>
 *
 * @usage
 *    - vendor/codegyre/robo/robo wd secundario → para sitios Drupal
 * 		- vendor/codegyre/robo/robo wp zl-feeds-supervisor → para sitios PHP
 *
 * @TODO:
 *    - icon in notifications
 *
 */

require "vendor/autoload.php";

use Symfony\Component\Yaml\Parser;
use Robo\Tasks;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;

/**
 *
 */
class RoboFile extends Tasks {

  protected  $yaml;
  protected  $sites;
  protected  $notifier;
  protected  $notif_title;

  /**
   *
   */
  public function __construct() {

    $this->notifier = NotifierFactory::create();
    $notif_title = "Robomovidas";
    $this->yaml = new Parser();
    $this->sites = $this->yaml->parse(file_get_contents('./sites.yml'));
  }

  /**
   * Clears the cache.
   */
  public function cc($site) {

    $this->say("Clearing cache in " . $this->sites[$site]['path']);
    $this->taskExec('../vendor/bin/drupal')
						->dir($this->sites[$site]['path'] . "/web/")
            ->args(['cache:rebuild', 'all'])
            ->run();
    $this->send_notif(
				$this->notif_title,
				"La caché ha sido eliminada",
				__DIR__.'/img/druplicon.png'
		);
  }

  /**
   * Phpcbf a file.
   */
  public function phpcbf($file) {

    $this->say("Executing phpcbf over " . $file);
    $this->taskExec('vendor/bin/phpcbf')
						->dir(__DIR__)
            ->option("--standard=PEAR")
            ->option("--no-patch")
            ->option("--colors")
            ->arg($file)
            ->run();

    $this->send_notif(
				$this->notif_title,
				"PHPCBF ejecutado",
				__DIR__.'/img/php-logo.png'
		);

		return;
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
   * in the $code_dir directory [DRUPAL SITE]
   */
  public function wd($site) {

    $this->taskWatch()
            ->monitor(
              $this->sites[$site]['code_dir'], function ($event) use ($site) {
                if ($event->getTypeString() !== 'modify') {
                    return;
                }
                else {

                    $this->phpcbf((string) $event->getResource());

                    $this->cc($site);

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
   * Performs determined actions on each file modification
   * in the $code_dir directory. [VANILLA PHP]
   */
  public function wp($site) {

    $this->taskWatch()
            ->monitor(
              $this->sites[$site]['code_dir'], function ($event) use ($site) {
                if ($event->getTypeString() !== 'modify') {
                    return;
                }
                else {
										if ( strpos( $event->getResource(), ".php") >= 1 ) {
											$this->phpcbf((string) $event->getResource());
										}

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

  /**
   *
   */
  private function send_notif($title, $body, $icon_file) {

    $notification =
      (new Notification())
      ->setTitle($title)
      ->setBody($body)
      ->setIcon($icon_file);

    $this->notifier->send($notification);
  }

}
