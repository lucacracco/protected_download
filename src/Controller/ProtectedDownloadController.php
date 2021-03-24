<?php

namespace Drupal\protected_download\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ProtectedDownloadController.
 *
 * @package Drupal\protected_download\Controller
 */
class ProtectedDownloadController extends ControllerBase {

  /**
   * File Storage service.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Token generator.
   *
   * @var \Drupal\protected_download\Service\TokenGeneratorInterface
   */
  protected $tokenGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->streamWrapperManager = $container->get('stream_wrapper_manager');
    $instance->tokenGenerator = $container->get('protected_download.token_generator');
    $instance->fileStorage = $container->get('entity_type.manager')
      ->getStorage('file');
    return $instance;
  }

  /**
   * Handles protected file transfers.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $scheme
   *   The file scheme, defaults to 'protected'.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   The transferred file as response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Thrown when the requested file does not exist.
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user does not have access to the file.
   *
   * @see \Drupal\system\FileDownloadController::download()
   */
  public function download(Request $request, $scheme = 'protected') {
    $target = $request->query->get('file');
    $security_token = $request->query->get('token', FALSE);
    // Merge remaining path arguments into relative file path.
    $uri = $scheme . '://' . $target;

    if ($security_token && $this->streamWrapperManager->isValidScheme($scheme) && is_file($uri)) {

      // Check if current user has access to invoice.
      if (!$this->grantAccess($target, $security_token)) {
        throw new AccessDeniedHttpException();
      }

      // Load the file entity for retrieve the correct header.
      /** @var \Drupal\file\FileInterface[] $files */
      $files = $this->fileStorage->loadByProperties(['uri' => $uri]);

      if (count($files)) {
        foreach ($files as $item) {
          // Since some database servers sometimes use a case-insensitive comparison
          // by default, double check that the filename is an exact match.
          if ($item->getFileUri() === $uri) {
            $file = $item;
            break;
          }
        }
      }
      if (!isset($file)) {
        throw new AccessDeniedHttpException();
      }

      // Access is granted.
      $headers = file_get_content_headers($file);

      // \Drupal\Core\EventSubscriber\FinishResponseSubscriber::onRespond()
      // sets response as not cacheable if the Cache-Control header is not
      // already modified. We pass in FALSE for non-private schemes for the
      // $public parameter to make sure we don't change the headers.
      return new BinaryFileResponse($uri, 200, $headers, FALSE);
    }

    throw new NotFoundHttpException();
  }

  /**
   * Check if access is grant.
   *
   * @param string $file_uri
   *   The URI to a file
   *
   * @return bool
   *   The result of checks.
   */
  protected function grantAccess(string $file_uri, string $token) {
    return $this->tokenGenerator->validate($token, $file_uri);
  }

}
