<?php

namespace Drupal\protected_download\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor protected to rewrite file URLs.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request. This is similar to what
 * Core does for the system/files/* URLs.
 *
 * @package Drupal\protected_download\PathProcessor
 */
class PathProcessorProtectedFiles implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/protected/files/') === 0 && !$request->query->has('file')) {
      $file_path = preg_replace('|^\/protected\/files\/|', '', $path);
      $request->query->set('file', $file_path);
      return '/system/protected-files';
    }
    return $path;
  }

}
