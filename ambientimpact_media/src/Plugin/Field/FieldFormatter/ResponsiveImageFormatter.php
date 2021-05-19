<?php

namespace Drupal\ambientimpact_media\Plugin\Field\FieldFormatter;


use Drupal\ambientimpact_core\ComponentPluginManagerInterface;
use Drupal\ambientimpact_core\Config\Entity\ThirdPartySettingsDefaultsTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\file\FileInterface;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\responsive_image\Plugin\Field\FieldFormatter\ResponsiveImageFormatter as CoreResponsiveImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plug-in override of the core 'responsive_image' formatter.
 *
 * This extends the core formatter to apply various components' alterations.
 *
 * @see \Drupal\ambientimpact_media\EventSubscriber\Field\FieldFormatterInfoAlterResponsiveImageEventSubscriber::fieldFormatterInfoAlter()
 *   Core formatter is replaced in this hook.
 *
 * @todo Rework the calls to individual components as an event that they can
 *   subscribe to.
 *
 * @todo Support the 'animated_gif_toggle' component?
 *
 * @todo Support the 'remote_video' component?
 *
 * @todo Can the
 */
class ResponsiveImageFormatter extends CoreResponsiveImageFormatter {

  use ThirdPartySettingsDefaultsTrait;

  /**
   * The Ambient.Impact Component manager service.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * Constructs this formatter object; saves dependencies.
   *
   * @param string $pluginId
   *   The plugin_id for the formatter.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @param string $label
   *   The formatter label display setting.
   *
   * @param string $viewMode
   *   The view mode.
   *
   * @param array $thirdPartySettings
   *   Any third party settings.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $responsiveImageStyleStorage
   *   The responsive image style storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $imageStyleStorage
   *   The image style storage.
   *
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $linkGenerator
   *   The Drupal link generator service.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component manager service.
   */
  public function __construct(
    $pluginId, array $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings, $label, $viewMode, array $thirdPartySettings,
    EntityStorageInterface  $responsiveImageStyleStorage,
    EntityStorageInterface  $imageStyleStorage,
    LinkGeneratorInterface  $linkGenerator,
    AccountInterface        $currentUser,
    ComponentPluginManagerInterface $componentManager
  ) {

    parent::__construct(
      $pluginId, $pluginDefinition,
      $fieldDefinition, $settings, $label, $viewMode, $thirdPartySettings,
      $responsiveImageStyleStorage, $imageStyleStorage, $linkGenerator,
      $currentUser
    );

    $this->componentManager = $componentManager;

    // Set our default third-party settings.
    $this->componentManager->getComponentInstance('photoswipe')
      ->setImageFormatterDefaults($this);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container, array $configuration,
    $pluginId, $pluginDefinition
  ) {
    return new static(
      $pluginId,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('responsive_image_style'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('link_generator'),
      $container->get('current_user'),
      $container->get('plugin.manager.ambientimpact_component')
    );
  }

  /**
   * Builds a render array for a field value.
   *
   * This extends parent::viewElements() to set a correct max-width based on the
   * fallback image style and to add support for the PhotoSwipe component.
   *
   * In order to set a correct max-width on the field container and/or link,
   * we need to provide the intrinsic size of the <img> element. The easiest
   * way to do this is to get the fallback image style ID and pass that on;
   * attempting to loop through the various image image style mappings is not
   * practical as breakpoints and other criteria can only be determined by the
   * browser. As @link
   * https://developer.mozilla.org/en-US/docs/Web/HTML/Element/picture MDN
   * states for the <picture> element @endlink:
   *
   * > The browser will consider each child <source> element and choose the
   * > best match among them. If no matches are found—or the browser doesn't
   * > support the <picture> element—the URL of the <img> element's src
   * > attribute is selected. The selected image is then presented in the
   * > space occupied by the <img> element.
   *
   * Note the last sentence. Down the page they state that the <img> element
   * "describes the size and other attributes of the image and its
   * presentation", which indicates that the fallback image can be considered
   * the canonical size of the responsive image.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   *
   * @param string $langCode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A render array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\Image::preprocessFieldSetImageFieldMaxWidth()
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\Image::getImageStyleDerivativeDimensions()
   *   Gets image derivative dimensions for setting on the <img> element, so
   *   that browsers can know the intrinsic size of the responsive image.
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\PhotoSwipe::alterImageFormatterElements()
   *   Elements are passed to this PhotoSwipe component method to be altered.
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {

    /** @var array */
    $elements = parent::viewElements($items, $langCode);

    /** @var array */
    $thirdPartySettings = $this->getThirdPartySettings('ambientimpact_media');

    /** @var \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\Image */
    $imageComponent = $this->componentManager->getComponentInstance('image');

    foreach ($elements as &$element) {

      if (!isset($element['#responsive_image_style_id'])) {
        continue;
      }

      /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface|null */
      $responsiveImageStyle = $this->responsiveImageStyleStorage->load(
        $element['#responsive_image_style_id']
      );

      if (!\is_object($responsiveImageStyle)) {
        continue;
      }

      // \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\Image::preprocessFieldSetImageFieldMaxWidth()
      // uses '#image_style' if available to derive the intrinsic size from.
      /** @var string */
      $element['#image_style'] = $responsiveImageStyle->getFallbackImageStyle();

      // Attempt to get the image style derivative dimensions, or the original
      // image dimensions if the derivative cannot be loaded.
      /** @var string[] */
      $dimensions = $imageComponent->getImageStyleDerivativeDimensions(
        $element['#item'], $element['#image_style']
      );

      if (empty($dimensions)) {
        continue;
      }

      // Set the inline 'width' and 'height' attributes on the <img> element,
      // so that browsers can know the intrinsic size of the responsive image.
      //
      // @see \template_preprocess_responsive_image_formatter()
      $element['#item_attributes']['width']   = $dimensions['width'];
      $element['#item_attributes']['height']  = $dimensions['height'];

    }

    if (empty($elements) || $thirdPartySettings['use_photoswipe'] !== true) {
      return $elements;
    }

    $this->componentManager->getComponentInstance('photoswipe')
      ->alterImageFormatterElements($elements, $items, $thirdPartySettings);

    return $elements;

  }

}
