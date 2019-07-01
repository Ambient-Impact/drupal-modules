<?php

namespace Drupal\ambientimpact_media\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Image component.
 *
 * @Component(
 *   id = "image",
 *   title = @Translation("Image"),
 *   description = @Translation("Provides functionality for working with images, image styles, and image fields.")
 * )
 */
class Image extends ComponentBase {
  /**
   * The image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * An array of image style instances that have been loaded.
   *
   * @var array
   */
  protected $imageStyleInstances = [];

  /**
   * Constructor; saves dependencies.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Drupal language manager service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $yamlSerialization
   *   The Drupal YAML serialization class.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $htmlCacheService
   *   The Component HTML cache service.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The Drupal image factory service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    LanguageManagerInterface $languageManager,
    RendererInterface $renderer,
    SerializationInterface $yamlSerialization,
    TranslationInterface $stringTranslation,
    CacheBackendInterface $htmlCacheService,
    ImageFactory $imageFactory
  ) {
    // Save dependencies before calling parent::__construct() so that they're
    // available in the configuration methods as ComponentBase::__construct()
    // will call them.
    $this->imageFactory = $imageFactory;

    parent::__construct(
      $configuration, $pluginID, $pluginDefinition,
      $moduleHandler,
      $languageManager,
      $renderer,
      $yamlSerialization,
      $stringTranslation,
      $htmlCacheService
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('serialization.yaml'),
      $container->get('string_translation'),
      $container->get('cache.ambientimpact_component_html'),
      $container->get('image.factory')
    );
  }

  /**
   * Set an inline max-width on image field items based on their image width.
   *
   * This is useful to avoid linked fields having phantom space on the sides if
   * the link is display: block but the image doesn't span the full width.
   *
   * @param array &$items
   *   An array of field items from $variables['items'] via
   *   template_preprocess_field().
   */
  public function preprocessFieldSetImageFieldMaxWidth(array &$items) {
    foreach ($items as $delta => &$item) {
      $file = File::load($item['content']['#item']->target_id);

      if (empty($file)) {
        continue;
      }

      $fileURI = $file->getFileUri();

      // If the item uses an image style, we have to try and load it to get the
      // URI to the derivative image.
      if (!empty($item['content']['#image_style'])) {
        $imageStyleName = $item['content']['#image_style'];

        // If we've already tried to load this image style and gotten null, skip
        // it.
        if (
          isset($this->imageStyleInstances[$imageStyleName]) &&
          $this->imageStyleInstances[$imageStyleName] === null
        ) {
          continue;
        }

        // Attempt to load the image style if it hasn't been attempted yet.
        if (!isset($this->imageStyleInstances[$imageStyleName])) {
          $this->imageStyleInstances[$imageStyleName] =
            ImageStyle::load($imageStyleName);
        }

        $imageStyle = $this->imageStyleInstances[$imageStyleName];

        // We got a bad image style.
        if ($imageStyle === null) {
          $this->imageStyleInstances[$imageStyleName] = null;

          continue;
        }

        $fileURI = $imageStyle->buildUri($fileURI);
      }

      // Create an Image instance.
      $imageInstance = $this->imageFactory->get($fileURI);

      // If we get null for the width, skip this image as it likely means the
      // derivative hasn't been created yet. While getting the original image
      // width would technically work, there's no guarantee that it's the same
      // width and may cause more problems.
      if ($imageInstance->getWidth() === null) {
        continue;
      }

      // If a 'style' attribute already exists, try to explode so that we can
      // remove any existing max-width for the sake of cleanness.
      if ($item['attributes']->offsetExists('style')) {
        $styleArray = explode(';', $item['attributes']->offsetGet('style'));
      } else {
        $styleArray = [];
      }

      foreach ($styleArray as $delta => $value) {
        // Remove any empty values and values that contain a max-width.
        if (
          empty($value) ||
          mb_strpos($value, 'max-width:') !== false
        ) {
          unset($styleArray[$delta]);
        }
      }

      // Re-number the array indices so they're sequential again, in case we
      // removed any.
      $styleArray = \array_values($styleArray);

      $styleArray[] = 'max-width: ' . $imageInstance->getWidth() . 'px';

      // Glue it all back together into a string.
      $item['attributes']->setAttribute(
        'style', implode(';', $styleArray) . ';'
      );
    }
  }
}
