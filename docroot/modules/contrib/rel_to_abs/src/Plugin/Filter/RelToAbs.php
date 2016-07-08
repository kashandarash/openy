<?php

namespace Drupal\rel_to_abs\Plugin\Filter;

use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to convert relative paths to absolute URLs.
 *
 * @Filter(
 *   id = "rel_to_abs",
 *   title = @Translation("Convert relative paths to absolute URLs"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class RelToAbs extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * An Url generator.
   *
   * @var UrlGeneratorInterface $requestStack
   */
  protected $urlGenerator;

  /**
   * Constructs a \Drupal\editor\Plugin\Filter\EditorFileReference object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $urlGenerator
   *   An entity manager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UrlGeneratorInterface $urlGenerator) {
    $this->urlGenerator = $urlGenerator;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  static public function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $resultText = preg_replace_callback('/(href|background|src)=["\']([\/#][^"\']*)["\']/', function($matches) {
      $baseUrl = $this->urlGenerator->getContext()->getBaseUrl();
      $relativeUrl = $rawUrl = urldecode($matches[2]);
      if (!empty($baseUrl) && strpos($rawUrl, $baseUrl) === 0) {
        $relativeUrl = '/' . substr($rawUrl, strlen($baseUrl));
      }
      $relativeUrl = preg_replace('/\/{2,}/', '/', $relativeUrl);
      try {
        $url = Url::fromUserInput($relativeUrl)->setAbsolute(true)->toString();
      }
      catch(\InvalidArgumentException $e) {
        drupal_set_message($e->getMessage(), 'error');
      }
      return $matches[1] . '="' . $url . '"';
    }, $text);

    return new FilterProcessResult($resultText);
  }

}
