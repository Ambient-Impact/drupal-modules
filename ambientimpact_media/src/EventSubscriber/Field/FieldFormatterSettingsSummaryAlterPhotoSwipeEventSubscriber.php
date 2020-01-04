<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\field_event_dispatcher\Event\Field\FieldFormatterSettingsSummaryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * PhotoSwipe hook_field_formatter_settings_summary_alter() event subscriber.
 */
class FieldFormatterSettingsSummaryAlterPhotoSwipeEventSubscriber implements
EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * The Drupal string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    TranslationInterface $stringTranslation
  ) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::FIELD_FORMATTER_SETTINGS_SUMMARY_ALTER =>
        'fieldFormatterSettingsSummaryAlter',
    ];
  }

  /**
   * Add PhotoSwipe image field formatter summary.
   *
   * This displays a message on image formatters linked an image file if
   * PhotoSwipe is set to be used.
   *
   * @param \Drupal\media_event_dispatcher\Event\Field\FieldFormatterSettingsSummaryAlterEvent $event
   *   The event object.
   *
   * @see https://www.drupal.org/node/2130757
   *   Change record describing third party settings usage.
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21Entity%21ThirdPartySettingsInterface.php/interface/ThirdPartySettingsInterface
   *   API documentation for third party settings on entities.
   */
  public function fieldFormatterSettingsSummaryAlter(
    FieldFormatterSettingsSummaryAlterEvent $event
  ) {
    /** @var array */
    $context = $event->getContext();
    /** @var string */
    $pluginID = $context['formatter']->getPluginId();

    if (
      (
        // Always show the summary for the 'image_formatter_link_to_image_style'
        // because it's always linked to an image.
        $pluginID === 'image_formatter_link_to_image_style' ||
        // Only show the summary for the 'image' formatter if it's linked to the
        // image file.
        $pluginID === 'image' &&
        $context['formatter']->getSetting('image_link') === 'file'
      ) &&
      // This is passed to us as a string ('1' or '0'), despite the schema
      // specifying it as boolean, likely because this is at the Form API stage
      // where checkbox values are treated as strings.
      (bool) $context['formatter']->getThirdPartySetting(
        'ambientimpact_media', 'use_photoswipe'
      ) === true
    ) {
      if ((bool) $context['formatter']->getThirdPartySetting(
        'ambientimpact_media', 'use_photoswipe_gallery'
      ) === true) {
        $event->getSummary()[] =
          $this->t('Uses PhotoSwipe; grouped as gallery');
      } else {
        $event->getSummary()[] = $this->t('Uses PhotoSwipe');
      }
    }
  }
}
