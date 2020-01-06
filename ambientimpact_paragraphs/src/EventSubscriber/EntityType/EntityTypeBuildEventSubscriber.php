<?php

namespace Drupal\ambientimpact_paragraphs\EventSubscriber\EntityType;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityTypeBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * hook_entity_type_build() event subscriber class.
 */
class EntityTypeBuildEventSubscriber implements EventSubscriberInterface {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_TYPE_BUILD => 'entityTypeBuild',
    ];
  }

  /**
   * hook_entity_type_build() event handler.
   *
   * This sets our own URI callback for 'paragraph' entity types if none is set
   * on the entity type. See the URI callback for more information.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityTypeBuildEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_paragraphs\ParagraphParentContentEntityURI::URICallback()
   *   This is the URI callback method. Contains more information.
   *
   * @see \Drupal\Core\Entity\EntityType::getUriCallback()
   *   Gets any current URI callback for an entity type, returning null if none
   *   is set.
   *
   * @see \Drupal\Core\Entity\EntityType::setUriCallback()
   *   Sets the URI callback for an entity type.
   *
   * @see \hook_entity_type_build()
   *   Hook documentation.
   */
  public function entityTypeBuild(EntityTypeBuildEvent $event) {
    $entityTypes = &$event->getEntityTypes();

    if ($entityTypes['paragraph']->getUriCallback() === null) {
      $entityTypes['paragraph']->setUriCallback([
        '\\Drupal\\ambientimpact_paragraphs\\ParagraphParentContentEntityURI',
        'URICallback',
      ]);
    }
  }
}
