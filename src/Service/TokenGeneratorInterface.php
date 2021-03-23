<?php

namespace Drupal\protected_download\Service;


/**
 * Interface TokenGeneratorInterface.
 *
 * @package Drupal\protected_download\Service
 */
interface TokenGeneratorInterface {

  /**
   * Generates a token based on file uri.
   *
   * @param string $uri
   *   Protected file/image uri which by default starts with "/system/files/*".
   *   In case of images, uri already contains image style path part. For
   *   example
   *   "/system/files/styles/thumbnail/private/test.png".
   *
   * @return string
   *   A 43-character URL-safe token for validation, based on the hash salt
   *   provided by Settings::getHashSalt(), and the 'drupal_private_key'
   *   configuration variable.
   */
  public function get(string $uri): string;

  /**
   * Validates a token.
   *
   * @param string $token
   *   The token to be validated.
   * @param string $uri
   *   Uri used for validation.
   *
   * @return bool
   *   TRUE for a valid token, FALSE for an invalid token.
   */
  public function validate(string $token, string $uri): bool;

}
