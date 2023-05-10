<?php

namespace Drush\Commands\acquia_global_commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * A drush command file.
 *
 * @package Drupal\acquia_global_commands\Commands
 */
class MultiSiteCommands extends DrushCommands {

  /**
   * Execute code before site:install command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The information about the current request.
   *
   * @hook pre-command site-install
   */
  public function preSiteInstallCommand(CommandData $commandData): void {
    $options = $commandData->options();
    $uri = $options['uri'] ?? "";
    $dbUrl = $options['db-url'] ?? "";
    $existingConfig = $options['existing-config'] ?? FALSE;
    if ($uri && !$dbUrl && !$existingConfig) {
      $question = new ConfirmationQuestion("Would you like to configure the local database credentials?", TRUE);
      $answer = $this->io()->askQuestion($question);
      if ($answer) {
        $question = new Question("Local database name", $uri);
        $dbName = $this->io()->askQuestion($question);

        $question = new Question("Local database user", $uri);
        $dbUser = $this->io()->askQuestion($question);

        $question = new Question("Local database password", $uri);
        $dbPassword = $this->io()->askQuestion($question);

        $question = new Question("Local database host", "localhost");
        $dbHost = $this->io()->askQuestion($question);

        $question = new Question("Local database port", "3306");
        // @todo add validation for port number.
        $dbPort = $this->io()->askQuestion($question);
        // @todo generate settings.php code.
        $commandData->input()->setOption("db-url", "mysql://$dbUser:$dbPassword@$dbHost:$dbPort/$dbName");
      }
    }

  }

}
