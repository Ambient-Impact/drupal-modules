<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\field_event_dispatcher\Event\Field\FieldFormatterThirdPartySettingsFormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function in_array;

/**
 * Remote video hook_field_formatter_third_party_settings_form() event subscriber.
 */
class FieldFormatterThirdPartySettingsFormRemoteVideoEventSubscriber
implements EventSubscriberInterface {
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
      HookEventDispatcherInterface::FIELD_FORMATTER_THIRD_PARTY_SETTINGS_FORM =>
        'fieldFormatterThirdPartySettingsForm',
    ];
  }

  /**
   * Add remote video image field formatter form elements.
   *
   * @param \Drupal\field_event_dispatcher\Event\Field\FieldFormatterThirdPartySettingsFormEvent $event
   *   The event object.
   *
   * @see https://www.drupal.org/node/2130757
   *   Change record describing third party settings usage.
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21Entity%21ThirdPartySettingsInterface.php/interface/ThirdPartySettingsInterface
   *   API documentation for third party settings on entities.
   */
  public function fieldFormatterThirdPartySettingsForm(
    FieldFormatterThirdPartySettingsFormEvent $event
  ) {
    /** @var \Drupal\Core\Field\FormatterInterface */
    $plugin = $event->getPlugin();
    /** @var array */
    $form = $event->getForm();

    // Ignore anything that isn't:
    if (
      // an 'image' formatter,
      $plugin->getPluginId() !== 'image' ||
      // a 'media' entity,
      $form['#entity_type'] !== 'media' ||
      // or a media entity that isn't the 'remote_video' media type.
      $form['#bundle'] !== 'remote_video'
    ) {
      return;
    }

    $elements = [];

    $elements['play_icon'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Show provider/play icon'),
      '#description'    => $this->t('If enabled, will overlay a provider-specific play icon (e.g. YouTube or Vimeo) over the thumbnail, or a generic play icon if the provider is not recognized.'),
      '#default_value'  =>
        $plugin->getThirdPartySetting('ambientimpact_media', 'play_icon'),
    ];

    $event->addElements('ambientimpact_media', $elements);
  }
}
