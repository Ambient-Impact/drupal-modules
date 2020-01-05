<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\field_event_dispatcher\Event\Field\FieldFormatterSettingsSummaryAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Remote video hook_field_formatter_settings_summary_alter() event subscriber.
 */
class FieldFormatterSettingsSummaryAlterRemoteVideoEventSubscriber implements
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
   * Add remote video image field formatter summary.
   *
   * This displays a message on image formatters linked to 'remote' if the play
   * icon is set to be shown over the thumbnail.
   *
   * @param \Drupal\field_event_dispatcher\Event\Field\FieldFormatterSettingsSummaryAlterEvent $event
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

    if (
      $context['formatter']->getPluginId() === 'image' &&
      // The 'remote' image link option is currently on available on the
      // 'remote_video' media entity.
      $context['formatter']->getSetting('image_link') === 'remote' &&
      // This is passed to us as a string ('1' or '0'), despite the schema
      // specifying it as boolean, likely because this is at the Form API stage
      // where checkbox values are treated as strings.
      (bool) $context['formatter']->getThirdPartySetting(
        'ambientimpact_media', 'play_icon'
      ) === true
    ) {
      $event->getSummary()[] = $this->t('Play icon shown');
    }
  }
}
