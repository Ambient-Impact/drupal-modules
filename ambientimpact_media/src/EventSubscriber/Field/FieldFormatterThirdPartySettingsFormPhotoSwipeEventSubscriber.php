<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\field_event_dispatcher\Event\Field\FieldFormatterThirdPartySettingsFormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use function in_array;

/**
 * PhotoSwipe hook_field_formatter_third_party_settings_form() event subscriber.
 */
class FieldFormatterThirdPartySettingsFormPhotoSwipeEventSubscriber
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
   * Add PhotoSwipe image field formatter form elements.
   *
   * @param \Drupal\field_event_dispatcher\Event\Field\FieldFormatterThirdPartySettingsFormEvent $event
   *   The event object.
   *
   * @see https://www.drupal.org/node/2130757
   *   Change record describing third party settings usage.
   *
   * @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Config%21Entity%21ThirdPartySettingsInterface.php/interface/ThirdPartySettingsInterface
   *   API documentation for third party settings on entities.
   *
   * @todo Can we hide the 'use_photoswipe' checkbox if the 'image' formatter
   * $plugin->getSetting('image_link') !== 'file', but as a client-side #state
   * so that it shows/hides without having to save the formatter settings?
   */
  public function fieldFormatterThirdPartySettingsForm(
    FieldFormatterThirdPartySettingsFormEvent $event
  ) {
    /** @var \Drupal\Core\Field\FormatterInterface */
    $plugin = $event->getPlugin();

    // Only add the form elements for these formatter types.
    if (!in_array($plugin->getPluginId(), [
      'image',
      'image_formatter_link_to_image_style',
      'responsive_image',
    ])) {
      return;
    }

    $elements = [];

    $elements['use_photoswipe'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Use PhotoSwipe'),
      '#description'    => $this->t('If enabled, will use the <a href="https://photoswipe.com/" target="_blank">PhotoSwipe</a> JavaScript library to display linked images.'),
      '#default_value'  => $plugin->getThirdPartySetting(
        'ambientimpact_media', 'use_photoswipe'
      ),
    ];
    $elements['use_photoswipe_gallery'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Group as PhotoSwipe gallery'),
      '#description'    => $this->t('If enabled, will group all items in this field as a PhotoSwipe gallery.'),
      '#default_value'  => $plugin->getThirdPartySetting(
        'ambientimpact_media', 'use_photoswipe_gallery'
      ),
      '#states'   => [
        'visible'   => [
          // This hides this item if 'use_photoswipe' is not checked.
          ':input[name*="[ambientimpact_media][use_photoswipe]"]' => [
            'checked' => true,
          ],
        ],
      ],
    ];

    $event->addElements('ambientimpact_media', $elements);
  }
}
