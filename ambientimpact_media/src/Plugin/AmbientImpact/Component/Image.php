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
use Drupal\file\FileStorageInterface;
use Drupal\image\ImageStyleStorageInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;
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
   * The Drupal file entity storage.
   *
   * @var \Drupal\file\FileStorageInterface
   */
  protected $fileStorage;

  /**
   * The Drupal image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * The Drupal image style configuration entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * An array of image style instances that have been loaded.
   *
   * @var \Drupal\image\ImageStyleInterface|null[]
   */
  protected $imageStyleInstances = [];

  /**
   * Component constructor; saves dependencies.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginId
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
   * @param \Drupal\file\FileStorageInterface $fileStorage
   *   The Drupal file entity storage.
   *
   * @param \Drupal\Core\Image\ImageFactory $imageFactory
   *   The Drupal image factory service.
   *
   * @param \Drupal\image\ImageStyleStorageInterface $imageStyleStorage
   *   The Drupal image style configuration entity storage.
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    ModuleHandlerInterface      $moduleHandler,
    LanguageManagerInterface    $languageManager,
    RendererInterface           $renderer,
    SerializationInterface      $yamlSerialization,
    TranslationInterface        $stringTranslation,
    CacheBackendInterface       $htmlCacheService,
    FileStorageInterface        $fileStorage,
    ImageFactory                $imageFactory,
    ImageStyleStorageInterface  $imageStyleStorage
  ) {

    parent::__construct(
      $configuration, $pluginId, $pluginDefinition,
      $moduleHandler,
      $languageManager,
      $renderer,
      $yamlSerialization,
      $stringTranslation,
      $htmlCacheService
    );

    $this->fileStorage        = $fileStorage;
    $this->imageFactory       = $imageFactory;
    $this->imageStyleStorage  = $imageStyleStorage;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginId, $pluginDefinition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('serialization.yaml'),
      $container->get('string_translation'),
      $container->get('cache.ambientimpact_component_html'),
      $container->get('entity_type.manager')->getStorage('file'),
      $container->get('image.factory'),
      $container->get('entity_type.manager')->getStorage('image_style')
    );
  }

  /**
   * Get image style derivative dimensions from an image item and image style.
   *
   * @param \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
   *   A Drupal image item to get dimensions of.
   *
   * @param string $imageStyleName
   *   The image style name to attempt to load, or an empty string to return the
   *   original image's dimensions.
   *
   * @return string[]
   *   Either an associative array containing 'width' and 'height' keys, or an
   *   empty array if the file entity could not be loaded. If the file entity
   *   can be loaded but $imageStyleName does not correspond to an existing
   *   image style, the dimensions will be of the original image. If
   *   $imageStyleName does correspond to an existing image style, the
   *   dimensions will be that if the image style derivative.
   */
  public function getImageStyleDerivativeDimensions(
    ImageItem $imageItem,
    string    $imageStyleName = ''
  ): array {

    /** @var \Drupal\file\FileInterface */
    $file = $this->fileStorage->load($imageItem->target_id);

    // If we couldn't load a valid file entity, skip this item.
    if (empty($file)) {
      return [];
    }

    /** @var string */
    $fileURI = $file->getFileUri();

    /** @var string[] */
    $dimensions = [
      'width'   => $imageItem->width,
      'height'  => $imageItem->height,
    ];

    // If there's no image style to be used for this item, return the original
    // image dimensions.
    if (empty($imageStyleName)) {
      return $dimensions;
    }

    // If we've already tried to load this image style and gotten null, return
    // the original image dimensions.
    if (
      isset($this->imageStyleInstances[$imageStyleName]) &&
      $this->imageStyleInstances[$imageStyleName] === null
    ) {
      return $dimensions;
    }

    // Attempt to load the image style if it hasn't been attempted yet.
    if (!isset($this->imageStyleInstances[$imageStyleName])) {

      $this->imageStyleInstances[$imageStyleName] =
        $this->imageStyleStorage->load($imageStyleName);

    }

    /** @var \Drupal\image\ImageStyleInterface|null */
    $imageStyle = $this->imageStyleInstances[$imageStyleName];

    // If we weren't able to load an image style, set it to null and return the
    // original image dimensions.
    if ($imageStyle === null) {

      $this->imageStyleInstances[$imageStyleName] = null;

      return $dimensions;

    }

    // If we got a valid image style object, have it transform the original
    // dimensions to that of the derivative image, whether or not it has been
    // generated yet.
    $imageStyle->transformDimensions($dimensions, $fileURI);

    return $dimensions;

  }

  /**
   * Set an inline max-width on image field items based on their image width.
   *
   * This is useful to avoid linked fields having phantom space on the sides if
   * the link is display: block but the image doesn't span the full width.
   *
   * @param array &$variables
   *   An array of variables from \template_preprocess_field().
   */
  public function preprocessFieldSetImageFieldMaxWidth(
    array &$variables
  ): void {

    foreach ($variables['items'] as $delta => &$item) {

      /** @var string[] */
      $dimensions = $this->getImageStyleDerivativeDimensions(
        $item['content']['#item'],
        isset($item['content']['#image_style']) ?
          $item['content']['#image_style'] : ''
      );

      if (empty($dimensions)) {
        continue;
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
    // attributes if constrain_width is set to true. If there's only one field
    // item and no label (i.e. the label is set to "- Hidden -" rather than "-
    // Visually Hidden -"), Drupal will make the field wrapper the field item
    // container, merging in the classes but not other attributes
    if (
      $variables['multiple'] === false &&
      $variables['label_hidden'] === true &&
      $variables['items'][0]['content']['#constrain_width'] === true
    ) {
      $variables['attributes'] = NestedArray::mergeDeep(
        $variables['attributes'],
        [
          'style' => $maxWidth . ';',
        ]
      );
    }

  }

}
