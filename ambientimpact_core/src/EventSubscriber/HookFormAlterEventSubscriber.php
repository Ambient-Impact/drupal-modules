<?php

namespace Drupal\ambientimpact_core\EventSubscriber;

use Drupal\ambientimpact_core\ContainerAwareEventSubscriber;

use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\hook_event_dispatcher\Event\Form\FormAlterEvent;

/**
 * hook_form_alter() event subscriber class.
 */
class HookFormAlterEventSubscriber extends ContainerAwareEventSubscriber {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::FORM_ALTER => 'formAlter',
    ];
  }

  /**
   * Alter all forms.
   *
   * This does the following:
   *
   * - Attaches the form component library.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Form\FormAlterEvent $event
   *   The event object.
   */
  public function formAlter(FormAlterEvent $event) {
    $form = &$event->getForm();

    $form['#attached']['library'][] = 'ambientimpact_core/component.form';
  }
}
