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
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\image\ImageStyleInterface;
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
   * {@inheritdoc}
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
   * Load and return the file entity referenced by an image field item.
   *
   * @param \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
   *   A Drupal image field item.
   *
   * @return \Drupal\file\FileInterface|null
   *   A file entity or null if it could not be loaded.
   */
  public function getImageItemFile(ImageItem $imageItem): ?FileInterface {
    return $this->fileStorage->load($imageItem->target_id);
  }

  /**
   * Get an image style style entity.
   *
   * @param string $imageStyleName
   *   The image style machine name.
   *
   * @return \Drupal\image\ImageStyleInterface|null
   *   An image style entity or null if it could not be loaded.
   */
  public function getImageStyle(string $imageStyleName): ?ImageStyleInterface {

    /** @var \Drupal\image\ImageStyleInterface|null */
    $imageStyle = $this->imageStyleStorage->load($imageStyleName);

    if (\is_object($imageStyle)) {
      return $imageStyle;
    }

    /** @var string|null */
    $replacementImageStyleName = $this->imageStyleStorage->getReplacementId(
      $imageStyleName
    );

    if (!\is_string($replacementImageStyleName)) {
      return null;
    }

    return $this->imageStyleStorage->load($replacementImageStyleName);

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
    $file = $this->getImageItemFile($imageItem);

    // If we couldn't load a valid file entity, skip this item.
    if (!\is_object($file)) {
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

    /** @var \Drupal\image\ImageStyleInterface|null */
    $imageStyle = $this->getImageStyle($imageStyleName);

    // If we weren't able to load an image style, return the original image
    // dimensions.
    if (!\is_object($imageStyle)) {
      return $dimensions;
    }

    // If we got a valid image style object, have it transform the original
    // dimensions to that of the derivative image, whether or not it has been
    // generated yet.
    $imageStyle->transformDimensions($dimensions, $fileURI);

    return $dimensions;

  }

  /**
   * Get an image style derivative URI given an image item and image style name.
   *
   * @param \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
   *   A Drupal image field item.
   *
   * @param string $imageStyleName
   *   The image style name.
   *
   * @return string
   *   If the image item's file entity and the image could both be loaded, this
   *   will be the URI to the derivative image. If the file entity could be
   *   loaded but the image style could not be, this will be the URI to the
   *   full image. If the file entity could not be loaded, this will be an empty
   *   string.
   */
  public function getImageStyleDerivativeUri(
    ImageItem $imageItem,
    string    $imageStyleName = ''
  ): string {

    /** @var \Drupal\file\FileInterface|null */
    $file = $this->getImageItemFile($imageItem);

    // If we couldn't load a valid file entity, skip this item.
    if (!\is_object($file)) {
      return '';
    }

    /** @var string */
    $fileURI = $file->getFileUri();

    /** @var \Drupal\image\ImageStyleInterface|null */
    $imageStyle = $this->getImageStyle($imageStyleName);

    // If we weren't able to load an image style, return the file URI.
    if (!\is_object($imageStyle)) {
      return $fileURI;
    }

    return $imageStyle->buildUri($fileURI);

  }

  /**
   * Get an image style derivative URL given an image item and image style name.
   *
   * @param \Drupal\image\Plugin\Field\FieldType\ImageItem $imageItem
   *   A Drupal image field item.
   *
   * @param string $imageStyleName
   *   The image style name.
   *
   * @return string
   *   If the image item's file entity and the image could both be loaded, this
   *   will be the URL to the derivative image. If the file entity could be
   *   loaded but the image style could not be, this will be the URI to the
   *   full image. If the file entity could not be loaded, this will be an empty
   *   string.
   */
  public function getImageStyleDerivativeUrl(
    ImageItem $imageItem,
    string    $imageStyleName = ''
  ): string {

    /** @var \Drupal\file\FileInterface|null */
    $file = $this->getImageItemFile($imageItem);

    // If we couldn't load a valid file entity, skip this item.
    if (!\is_object($file)) {
      return '';
    }

    /** @var string */
    $fileURI = $file->getFileUri();

    /** @var \Drupal\image\ImageStyleInterface|null */
    $imageStyle = $this->getImageStyle($imageStyleName);

    // If we weren't able to load an image style, return the file URL.
    if (!\is_object($imageStyle)) {
      return $file->createFileUrl(false);
    }

    return $imageStyle->buildUrl($fileURI);

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
