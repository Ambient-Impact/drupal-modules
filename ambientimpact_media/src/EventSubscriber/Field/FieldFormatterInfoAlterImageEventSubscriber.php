<?php

namespace Drupal\ambientimpact_media\EventSubscriber\Field;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\field_event_dispatcher\Event\Field\FieldFormatterInfoAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Image formatter hook_field_formatter_info_alter() event subscriber.
 */
class FieldFormatterInfoAlterImageEventSubscriber implements
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
   * Replace the core 'image' field formatter with our own.
   *
   * Note that this will only replace the formatter if the existing class is the
   * core image formatter class to avoid breaking other modules.
   *
   * @param \Drupal\media_event_dispatcher\Event\Field\FieldFormatterInfoAlterEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_media\Plugin\Field\FieldFormatter\ImageFormatter
   *   Our 'image' field formatter override class.
   */
  public function fieldFormatterInfoAlter(FieldFormatterInfoAlterEvent $event) {
    /** @var array */
    $info = &$event->getInfo();

    if (
      isset($info['image']) &&
      $info['image']['class'] ===
        'Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter'
    ) {
      $info['image']['class'] =
        'Drupal\ambientimpact_media\Plugin\Field\FieldFormatter\ImageFormatter';
      $info['image']['provider'] = 'ambientimpact_media';
    }
  }
}
