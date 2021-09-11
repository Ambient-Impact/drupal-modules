<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The Drupal image style configuration entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The Drupal string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type plug-in manager.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    EntityTypeManagerInterface  $entityTypeManager,
    TranslationInterface        $stringTranslation
  ) {

    $this->imageStyleStorage = $entityTypeManager->getStorage('image_style');
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
   * @see \image_style_options()
   *   We generate image style select element options in a manner similar to
   *   this core function, but using dependency injection.
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

    /** @var array[] */
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
      '#description'    => $this->t('If enabled, will group all items in this field as a PhotoSwipe gallery. If this field is part of a media entity that\'s rendered in an entity reference field attached to another entity, this will group all referenced media entities in that field as a gallery.'),
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

    /** @var \Drupal\image\ImageStyleInterface[] */
    $imageStyles = $this->imageStyleStorage->loadMultiple();

    /** @var string[] */
    $imageStyleOptions = [];

    foreach ($imageStyles as $imageStyleName => $imageStyle) {
      $imageStyleOptions[$imageStyleName] = $imageStyle->label();
    }

    $elements['use_photoswipe_image_style'] = [
      '#type'           => 'select',
      '#title'          => $this->t('PhotoSwipe image style'),
      '#description'    => $this->t('The image style to link PhotoSwipe to.'),
      '#default_value'  => $plugin->getThirdPartySetting(
        'ambientimpact_media', 'use_photoswipe_image_style'
      ),
      '#empty_option'   => $this->t('None (original image)'),
      '#options'        => $imageStyleOptions,
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
