<?php

namespace Drupal\Tests\protected_download\Kernel;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\KernelTests\Core\File\FileTestBase;
use Drupal\protected_download\StreamWrapper\ProtectedStream;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests stream wrapper functions.
 *
 * @group File
 */
class StreamWrapperTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['protected_download'];

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'protected';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\protected_download\StreamWrapper\ProtectedStream';

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Add file_private_path setting.
    $request = Request::create('/');
    $site_path = DrupalKernel::findSitePath($request);
    mkdir($site_path . '/protected');
    $this->setSetting('file_protected_path', $site_path . '/protected');
  }

  /**
   * Test the getClassName() function.
   */
  public function testGetClassName() {
    // Check the dummy scheme.
    $this->assertEqual($this->classname, \Drupal::service('stream_wrapper_manager')
      ->getClass($this->scheme), 'Got correct class name for protected scheme.');
  }

  /**
   * Test the getViaScheme() method.
   */
  public function testGetInstanceByScheme() {
    $instance = \Drupal::service('stream_wrapper_manager')
      ->getViaScheme($this->scheme);
    $this->assertEqual($this->classname, get_class($instance), 'Got correct class type for protected scheme.');
  }

  /**
   * Test the getViaUri() and getViaScheme() methods and target functions.
   */
  public function testUriFunctions() {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    $instance = $stream_wrapper_manager->getViaUri($this->scheme . '://foo');
    $this->assertEqual($this->classname, get_class($instance), 'Got correct class type for protected URI.');

    // Test file_uri_target().
    $this->assertEqual($stream_wrapper_manager::getTarget('protected://foo/bar.txt'), 'foo/bar.txt', 'Got a valid stream target from protected://foo/bar.txt.');
    $this->assertFalse($stream_wrapper_manager::getTarget('foo/bar.txt'), 'foo/bar.txt is not a valid stream.');
    $this->assertSame($stream_wrapper_manager::getTarget('protected://'), '');

    // Test file_build_uri().
    $this->assertNotEqual(file_build_uri('foo/bar.txt'), 'protected://foo/bar.txt', 'Expected scheme was added.');
    $this->assertEqual($stream_wrapper_manager->getViaScheme('protected')->getDirectoryPath(), ProtectedStream::basePath(), 'Expected directory path was returned.');

    // Test file_create_url()
    // TemporaryStream::getExternalUrl() uses Url::fromRoute(), which needs
    // route information to work.
    $this->container->get('router.builder')->rebuild();
    $this->assertStringContainsString('protected/files/test.txt', file_create_url('protected://test.txt'), 'Protected external URL correctly built.');
  }

  /**
   * Test some file handle functions.
   */
  public function testFileFunctions() {
    $filename = 'protected://' . $this->randomMachineName();
    file_put_contents($filename, str_repeat('d', 1000));

    // Open for rw and place pointer at beginning of file so select will return.
    $handle = fopen($filename, 'c+');
    $this->assertNotFalse($handle, 'Able to open a file for appending, reading and writing.');

    // Attempt to change options on the file stream: should all fail.
    $this->assertFalse(@stream_set_blocking($handle, 0), 'Unable to set to non blocking using a local stream wrapper.');
    $this->assertFalse(@stream_set_blocking($handle, 1), 'Unable to set to blocking using a local stream wrapper.');
    $this->assertFalse(@stream_set_timeout($handle, 1), 'Unable to set read time out using a local stream wrapper.');
    $this->assertEqual(-1 /*EOF*/, @stream_set_write_buffer($handle, 512), 'Unable to set write buffer using a local stream wrapper.');

    // This will test stream_cast().
    $read = [$handle];
    $write = NULL;
    $except = NULL;
    $this->assertEqual(1, stream_select($read, $write, $except, 0), 'Able to cast a stream via stream_select.');

    // This will test stream_truncate().
    $this->assertEqual(1, ftruncate($handle, 0), 'Able to truncate a stream via ftruncate().');
    fclose($handle);
    $this->assertEqual(0, filesize($filename), 'Able to truncate a stream.');

    // Cleanup.
    unlink($filename);
  }

  /**
   * Test the scheme functions.
   */
  public function testGetValidStreamScheme() {

    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    $this->assertTrue($stream_wrapper_manager->isValidScheme($stream_wrapper_manager::getScheme('protected://asdf')), 'Got a valid stream scheme from protected://asdf');
    $this->assertFalse($stream_wrapper_manager->isValidScheme($stream_wrapper_manager::getScheme('foo://asdf')), 'Did not get a valid stream scheme from foo://asdf');
  }

  /**
   * Tests that phar stream wrapper is registered as expected.
   *
   * @see \Drupal\Core\StreamWrapper\StreamWrapperManager::register()
   */
  public function testPharStreamWrapperRegistration() {
    if (!in_array('phar', stream_get_wrappers(), TRUE)) {
      $this->markTestSkipped('There is no phar stream wrapper registered. PHP is probably compiled without phar support.');
    }
    // Ensure that phar is not treated as a valid scheme.
    $stream_wrapper_manager = $this->container->get('stream_wrapper_manager');
    $this->assertFalse($stream_wrapper_manager->getViaScheme('phar'));

    // Ensure that calling register again and unregister do not create errors
    // due to the PharStreamWrapperManager singleton.
    $stream_wrapper_manager->register();
    $this->assertContains('protected', stream_get_wrappers());
    $this->assertContains('phar', stream_get_wrappers());
    $stream_wrapper_manager->unregister();
    $this->assertNotContains('protected', stream_get_wrappers());
    // This will have reverted to the builtin phar stream wrapper.
    $this->assertContains('phar', stream_get_wrappers());
    $stream_wrapper_manager->register();
    $this->assertContains('protected', stream_get_wrappers());
    $this->assertContains('phar', stream_get_wrappers());
  }

}
