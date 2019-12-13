<?php

namespace Drupal\ambientimpact_media\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Component\Utility\NestedArray;
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
   * @param array &$variables
   *   An array of variables from template_preprocess_field().
   */
  public function preprocessFieldSetImageFieldMaxWidth(array &$variables) {
    foreach ($variables['items'] as $delta => &$item) {
      // First, we need to get the URI to the file and the original file's
      // dimensions, if available.

      $file = File::load($item['content']['#item']->target_id);

      // If we couldn't load a valid Drupal file entity, skip this item.
      if (empty($file)) {
        continue;
      }

      $fileURI = $file->getFileUri();

      $dimensions = [
        'width'   => $item['content']['#item']->width,
        'height'  => $item['content']['#item']->height,
      ];

      // If the item uses an image style, we have to try to load it to get the
      // URI to the derivative image.
      if (!empty($item['content']['#image_style'])) {
        $imageStyleName = $item['content']['#image_style'];
      }

      if (isset($imageStyleName)) {
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

        // If we weren't able to load an image style, set it to null and skip to
        // the next field item.
        if ($imageStyle === null) {
          $this->imageStyleInstances[$imageStyleName] = null;

          continue;
        }
      }

      // If we got a valid image style object, have it transform the original
      // dimensions to that of the derivative image, whether or not it has been
      // generated yet.
      if (
        isset($imageStyle) &&
        \method_exists($imageStyle, 'transformDimensions')
      ) {
        $imageStyle->transformDimensions($dimensions, $fileURI);
      }

      // If a 'style' attribute already exists, try to explode so that we can
      // remove any existing max-width for the sake of cleanliness.
      if ($item['attributes']->offsetExists('style')) {
        $styleArray = \explode(';', $item['attributes']->offsetGet('style'));
      } else {
        $styleArray = [];
      }

      foreach ($styleArray as $delta => $value) {
        // Remove any empty values and values that contain a max-width.
        if (
          empty($value) ||
          \mb_strpos($value, 'max-width:') !== false
        ) {
          unset($styleArray[$delta]);
        }
      }

      // Re-number the array indices so they're sequential again, in case we
      // removed any.
      $styleArray = \array_values($styleArray);

      // Save the max-width to a variable in case we need this for the
      // 'wrapper_attributes' at the end.
      $maxWidth = 'max-width: ' . $dimensions['width'] . 'px';

      $styleArray[] = $maxWidth;

      // Glue it all together into a string.
      $item['attributes']->setAttribute(
        'style', \implode(';', $styleArray) . ';'
      );
    }

    // If there's only one item, save the max-width to the field wrapper
    // attributes. If there's only one field item and no label (i.e. the label
    // is set to "- Hidden -" rather than "- Visually Hidden -"), Drupal will
    // make the field wrapper the field item container, merging in the classes
    // but not other attributes
    if ($variables['multiple'] === false) {
      $variables['attributes'] = NestedArray::mergeDeep(
        $variables['attributes'],
        [
          'style' => $maxWidth . ';',
        ]
      );
    }
  }
}
