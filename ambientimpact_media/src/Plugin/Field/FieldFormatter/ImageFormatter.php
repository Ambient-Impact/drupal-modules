<?php

namespace Drupal\ambientimpact_media\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter as CoreImageFormatter;
use Drupal\media\OEmbed\UrlResolverInterface;
use Drupal\ambientimpact_core\Config\Entity\ThirdPartySettingsDefaultsTrait;
use Drupal\ambientimpact_core\ComponentPluginManagerInterface;

/**
 * Plugin override of the core 'image' formatter.
 *
 * This extends the core formatter to add PhotoSwipe data.
 *
 * @see ambientimpact_media_field_formatter_info_alter()
 *   Core formatter is replaced in this hook.
 */
class ImageFormatter extends CoreImageFormatter {
  use ThirdPartySettingsDefaultsTrait;

  /**
   * The Component plug-in manager instance.
   *
   * @var \Drupal\ambientimpact_core\ComponentPluginManagerInterface
   */
  protected $componentManager;

  /**
   * The Drupal media oEmbed URL resolver service.
   *
   * @var \Drupal\media\OEmbed\UrlResolverInterface
   */
  protected $mediaoEmbedURLResolver;

  /**
   * Constructor; saves dependencies and sets default third-party settings.
   *
   * @param string $pluginID
   *   The plugin_id for the formatter.
   *
   * @param mixed $pluginDefinition
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
   *   Any third party settings settings.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $imageStyleStorage
   *   The image style storage.
   *
   * @param \Drupal\ambientimpact_core\ComponentPluginManagerInterface $componentManager
   *   The Ambient.Impact Component manager service.
   *
   * @param \Drupal\media\OEmbed\UrlResolverInterface $mediaoEmbedURLResolver
   *   The Drupal media oEmbed URL resolver service.
   */
  public function __construct(
    $pluginID,
    $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings,
    $label,
    $viewMode,
    array $thirdPartySettings,
    AccountInterface $currentUser,
    EntityStorageInterface $imageStyleStorage,
    ComponentPluginManagerInterface $componentManager,
    UrlResolverInterface $mediaoEmbedURLResolver
  ) {
    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings, $currentUser, $imageStyleStorage
    );

    $this->componentManager       = $componentManager;
    $this->mediaoEmbedURLResolver = $mediaoEmbedURLResolver;

    // Set our default third-party settings.
    $this->componentManager->getComponentInstance('photoswipe')
      ->setImageFormatterDefaults($this);
    $this->componentManager->getComponentInstance('animated_gif_toggle')
      ->setImageFormatterDefaults($this);
    $this->componentManager->getComponentInstance('remote_video')
      ->setImageFormatterDefaults($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginID,
    $pluginDefinition
  ) {
    return new static(
      $pluginID,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity.manager')->getStorage('image_style'),
      $container->get('plugin.manager.ambientimpact_component'),
      $container->get('media.oembed.url_resolver')
    );
  }

  /**
   * {@inheritdoc}
   *
   * This adds the 'remote' image link option if the entity is a media entity of
   * the 'remote_video' bundle.
   */
  public function settingsForm(array $form, FormStateInterface $formState) {
    $element = parent::settingsForm($form, $formState);

    if (
      $form['#entity_type'] === 'media' &&
      $form['#bundle'] === 'remote_video'
    ) {
      $element['image_link']['#options']['remote'] =
        $this->t('Remote video page');
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * This adds the 'remote' image link summary.
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($this->getSetting('image_link') === 'remote') {
      $summary[] = $this->t('Linked to remote video page');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * This extends the parent::viewElements() to alter the element render arrays
   * for the PhotoSwipe and AnimatedGIFToggle components.
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\PhotoSwipe::alterImageFormatterElements()
   *   Elements are passed to this PhotoSwipe component method to be altered.
   *
   * @see \Drupal\ambientimpact_media\Plugin\AmbientImpact\Component\AnimatedGIFToggle::alterImageFormatterElements()
   *   Elements are passed to this AnimatedGIFToggle component method to be
   *   altered.
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $elements = parent::viewElements($items, $langCode);

    $settings = $this->getThirdPartySettings('ambientimpact_media');

    $imageLinkSetting = $this->getSetting('image_link');

    // Use the image caption formatter template. See this module's readme.md for
    // details and requirements.
    foreach ($elements as $delta => $element) {
      $elements[$delta]['#theme'] = 'image_caption_formatter';
    }

    // Link to remote media if set to do so. Note that this setting is currently
    // only available on the 'remote_video' media entity.
    if ($imageLinkSetting === 'remote') {
      $entity = $items->getEntity();

      // The standard install profile config for the 'remote_video' media entity
      // type limits this field to a single value, so we assume there is only
      // the one value.
      $elements[0]['#url'] = $entity->get('field_media_oembed_video')
        ->getValue()[0]['value'];

      // Attempt to get the oEmbed provider from the URL. Note that the media
      // oEmbed URL resolver will throw an exception if it can't identify the
      // provider, so we wrap this in a try {} catch {} block.
      try {
        $provider = $this->mediaoEmbedURLResolver
          ->getProviderByUrl($elements[0]['#url']);

        $providerName = $provider->getName();
      } catch (Exception $exception) {
      }

      if (\method_exists($entity, 'getName')) {
        $mediaName = $entity->getName();
      }

      if (
        isset($providerName) &&
        isset($mediaName) &&
        !isset($elements[0]['#link_attributes']['title'])
      ) {
        $elements[0]['#link_attributes']['title'] = $this->t(
          'Watch @videoTitle on @providerTitle',
          [
            '@videoTitle'     => $mediaName,
            '@providerTitle'  => $providerName
          ]
        );
      }

      if (
        isset($providerName) &&
        isset($mediaName)
      ) {
        $elements[0]['#remote_video_provider_name']  = $providerName;
        $elements[0]['#remote_video_media_name']     = $mediaName;
      }

      if (isset($settings['play_icon'])) {
        $elements[0]['#use_remote_video_play_icon'] = $settings['play_icon'];
      }
    }

    // Allow the Animated GIF toggle component to alter $elements if set to
    // display an image style and linked to either the file or media entity.
    if (
      !empty($elements) &&
      $this->getSetting('image_style') !== '' &&
      (
        $imageLinkSetting === 'file' ||
        $imageLinkSetting === 'content'
      )
    ) {
      $this->componentManager->getComponentInstance('animated_gif_toggle')
        ->alterImageFormatterElements(
          $elements, $items, $this->getEntitiesToView($items, $langCode),
          $settings
        );
    }

    // Don't do any work if the field is empty, the field is not linked to the
    // image file, or PhotoSwipe is not to be used.
    if (
      empty($elements) ||
      $imageLinkSetting !== 'file' ||
      $settings['use_photoswipe'] !== true
    ) {
      return $elements;
    }

    $this->componentManager->getComponentInstance('photoswipe')
      ->alterImageFormatterElements($elements, $items, $settings);

    return $elements;
  }
}
