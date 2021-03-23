<?php

namespace Drupal\protected_download;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\Site\Settings;

/**
 * Class ProtectedDownloadServiceProvider.
 *
 * @package Drupal\protected_download
 */
class ProtectedDownloadServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Only register the protected file stream wrapper if a file path
    // has been set.
    if (Settings::get('file_protected_path')) {
      $container->register('protected_download.protected', 'Drupal\protected_download\StreamWrapper\ProtectedStream')
        ->addTag('stream_wrapper', ['scheme' => 'protected']);
    }
  }

}
