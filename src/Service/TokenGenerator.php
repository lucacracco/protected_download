<?php

namespace Drupal\protected_download\Service;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\PrivateKey;
use Drupal\Core\Site\Settings;

/**
 * Generates and validates token.
 *
 * @package Drupal\protected_download\Service
 */
class TokenGenerator implements TokenGeneratorInterface {

  /**
   * The private key service.
   *
   * @var \Drupal\Core\PrivateKey
   */
  protected $privateKey;

  /**
   * Constructs the protected token generator.
   *
   * @param \Drupal\Core\PrivateKey $private_key
   *   The private key service.
   */
  public function __construct(PrivateKey $private_key) {
    $this->privateKey = $private_key;
  }

  /**
   * {@inheritdoc}
   */
  public function get(string $uri): string {
    return Crypt::hmacBase64($uri, $this->privateKey->get() . Settings::getHashSalt());
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $token, string $uri): bool {
    return hash_equals($this->get($uri), $token);
  }

}
