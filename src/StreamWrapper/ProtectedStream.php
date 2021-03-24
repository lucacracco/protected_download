<?php

namespace Drupal\protected_download\StreamWrapper;

use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Drupal protected (protected://) stream wrapper class.
 *
 * Provides support for storing protectively accessible files with the
 * Drupal file interface.
 *
 * @package Drupal\protected_download\StreamWrapper
 */
class ProtectedStream extends PrivateStream {

  use StringTranslationTrait;

  /**
   * {@inheritDoc}
   */
  public static function basePath() {
    return Settings::get('file_protected_path');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Protected files served by Drupal.');
  }

  /**
   * {@inheritdoc}
   *
   * We have set up a helper function and menu entry to provide access to this
   * key via HTTP; normally it would be accessible some other way.
   */
  public function getExternalUrl() {
    $path = str_replace('\\', '/', $this->getTarget());
    $token = \Drupal::service('protected_download.token_generator')
      ->get($path);
    return Url::fromRoute('protected_download.protected_files_download', [
      'filepath' => $path,
      'scheme' => 'protected',
    ], [
      'absolute' => TRUE,
      'path_processing' => FALSE,
      'query' => ['token' => $token],
    ])
      ->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Protected files');
  }

}
