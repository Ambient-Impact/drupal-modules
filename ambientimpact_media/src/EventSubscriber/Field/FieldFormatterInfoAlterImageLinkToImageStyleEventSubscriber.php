<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\field_event_dispatcher\Event\Field\FieldFormatterInfoAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Image link to image style hook_field_formatter_info_alter() event subscriber.
 */
class FieldFormatterInfoAlterImageLinkToImageStyleEventSubscriber implements
EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::FIELD_FORMATTER_INFO_ALTER =>
        'fieldFormatterInfoAlter',
    ];
  }

  /**
   * Replace the 'image_formatter_link_to_image_style' field formatter.
   *
   * Note that this will only replace the formatter if the existing class is the
   * default formatter class defined by that module to avoid breaking other
   * modules.
   *
   * @param \Drupal\media_event_dispatcher\Event\Field\FieldFormatterInfoAlterEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_media\Plugin\Field\FieldFormatter\ImageFormatterLinkToImageStyleFormatter
   *   Our 'image_formatter_link_to_image_style' field formatter override class.
   */
  public function fieldFormatterInfoAlter(FieldFormatterInfoAlterEvent $event) {
    /** @var array */
    $info = &$event->getInfo();

    if (
      isset($info['image_formatter_link_to_image_style']) &&
      $info['image_formatter_link_to_image_style']['class'] ===
        'Drupal\image_formatter_link_to_image_style\Plugin\Field\FieldFormatter\ImageFormatterLinkToImageStyleFormatter'
    ) {
      $info['image_formatter_link_to_image_style']['class'] =
        'Drupal\ambientimpact_media\Plugin\Field\FieldFormatter\ImageFormatterLinkToImageStyleFormatter';
      $info['image_formatter_link_to_image_style']['provider'] = 'ambientimpact_media';
    }
  }
}
