<?php

namespace Drupal\ambientimpact_media\Plugin\AmbientImpact\Component;

use Drupal\ambientimpact_core\ComponentBase;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Animated GIF toggle component.
 *
 * @Component(
 *   id = "animated_gif_toggle",
 *   title = @Translation("Animated GIF toggle"),
 *   description = @Translation("Allows fields containing animated GIFs to toggle between a static image and the animated GIF on user interaction.")
 * )
 */
class AnimatedGIFToggle extends ComponentBase {
  /**
   * The Drupal image factory service.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

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
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fieldAttributes' => [
        // This attribute indicates to the front-end that a given field item is
        // to use the animated GIF toggle.
        'enabled' => 'data-animated-gif-toggle-field-enabled',

        // This attribute provides the URL to the original animated GIF file so
        // that it can toggle to it even if the field is linked to something
        // other than "File".
        'url'     => 'data-animated-gif-toggle-field-url',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getJSSettings(): array {
    return [
      'fieldAttributes' => $this->configuration['fieldAttributes'],
    ];
  }

  /**
   * Set default image formatter third-party settings.
   *
   * These are defined here in one place rather than in multiple image
   * formatters to make maintaining simple.
   *
   * @param mixed $formatterInstance
   *   An image field formatter instance to apply the settings to.
   */
  public function setImageFormatterDefaults($formatterInstance) {
    // Set default to true (using the toggle).
    $formatterInstance->setThirdPartySettingDefault(
      'ambientimpact_media', 'use_animated_gif_toggle', true
    );
  }

  /**
   * Alter an image formatter elements array to for the animated GIF toggle.
   *
   * @param mixed $formatterInstance
   *   An image field formatter instance to apply the settings to.
   *
   * @param array &$elements
   *   The elements array from a field formatter's viewElements() method.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items from a field formatter's viewElements() method.
   *
   * @param array $settings
   *   Our third-party settings for the field.
   *
   * @see \Drupal\ambientimpact_media\EventSubscriber\Preprocess\PreprocessFieldAnimatedGIFToggleEventSubscriber
   *   Attaches the attributes and library to fields.
   */
  public function alterImageFormatterElements(
    array &$elements,
    FieldItemListInterface $items,
    $files,
    array $settings = []
  ) {
    if (
      empty($elements) ||
      !isset($settings['use_animated_gif_toggle']) ||
      $settings['use_animated_gif_toggle'] !== true
    ) {
      return;
    }

    $toggleUsed = false;

    foreach ($files as $delta => $file) {
      // Create an Image instance.
      $imageInstance = $this->imageFactory->get($file->getFileUri());

      if ($imageInstance->getMimeType() === 'image/gif') {
        $elements[$delta]['#use_animated_gif_toggle'] = true;

        // Pass on the URL to the original GIF file so that the front-end knows
        // what to use when toggling. This allows us to toggle to the the
        // animated GIF even when the field is linked to the media entity page
        // or to an image style.
        $elements[$delta]['#animated_gif_toggle_url'] = $file->createFileUrl();

        $toggleUsed = true;
      }
    }

    // Pass this flag to PreprocessFieldAnimatedGIFToggleEventSubscriber so that
    // it knows whether to add attributes to the field as a whole and to attach
    // the library.
    $elements[0]['#animated_gif_toggle_used_in_array'] = $toggleUsed;
  }

  /**
   * Preprocess image formatter variables.
   *
   * This wraps the 'image' key in $variables with a media play overlay.
   *
   * @param array &$variables
   *
   * @see ambientimpact_media_preprocess_image_formatter()
   *   Called from this.
   *
   * @see ambientimpact_media_preprocess_image_formatter_link_to_image_style_formatter()
   *   Called from this.
   */
  public function preprocessImageFormatter(array &$variables) {
    // Don't do anything if the toggle is not enabled for this.
    if (
      !isset($variables['use_animated_gif_toggle']) ||
      $variables['use_animated_gif_toggle'] !== true
    ) {
      return;
    }

    $variables['image'] = [
      '#type'       => 'media_play_overlay',
      '#text'       => t(
        '<span class="visually-hidden">Play this animated </span>GIF'
      ),
      '#iconName'   => 'twistie-right',
      '#iconBundle' => 'libricons',
      '#preview'    => $variables['image'],
    ];
  }
}
