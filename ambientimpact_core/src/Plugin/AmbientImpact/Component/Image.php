<?php

namespace Drupal\ambientimpact_core\Plugin\AmbientImpact\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\ambientimpact_core\ComponentBase;

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
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ContainerInterface $container
  ) {
    $this->imageFactory     = $container->get('image.factory');

    parent::__construct(
      $configuration, $pluginID, $pluginDefinition, $container
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
