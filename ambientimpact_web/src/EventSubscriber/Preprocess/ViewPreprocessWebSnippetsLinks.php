<?php

namespace Drupal\ambientimpact_web\EventSubscriber\Preprocess;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\ambientimpact_core\EventSubscriber\ContainerAwareEventSubscriber;
use Drupal\hook_event_dispatcher\Event\Preprocess\ViewPreprocessEvent;

/**
 * Preprocess variables for the 'web_snippets' view.
 *
 * This adds links to the view header to search snippets, view all web
 * development tags, and to view the web snippets RSS feed.
 *
 * @see \Drupal\hook_event_dispatcher\Event\Preprocess\ViewPreprocessEvent
 */
class ViewPreprocessWebSnippetsLinks extends ContainerAwareEventSubscriber {
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ViewPreprocessEvent::name() => 'preprocessView',
    ];
  }

  /**
   * Preprocess variables for the 'web_snippets' view.
   *
   * This adds links to the view header to search snippets, view all web
   * development tags, and to view the web snippets RSS feed.
   *
   * Note that we could use '#theme' => 'links', but that doesn't provide a
   * simple way to define attributes for the <li> elements other than a single
   * class via the #items key - multiple classes separated by spaces in the
   * key will be joined together by Drupal with the '-' character, so that
   * hack from Drupal 7 no longer works.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Preprocess\ViewPreprocessEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_web\EventSubscriber\Theme\HookThemeWebSnippetsLinks::theme()
   *   Defines the 'web_snippets_links' render element.
   */
  public function preprocessView(ViewPreprocessEvent $event) {
    /* @var \Drupal\hook_event_dispatcher\Event\Preprocess\Variables\ViewEventVariables $variables */
    $variables = $event->getVariables();
    $view = $variables->getView();

    // dpm($view->id());

    if ($view->id() !== 'web_snippets') {
      return;
    }

    $header = &$variables->getByReference('header');

    $header['web_snippets_links'] = [
      '#theme'      => 'web_snippets_links',
      '#items'      => [],
      '#weight'     => 100,
    ];

    $items = &$header['web_snippets_links']['#items'];

    // Generate the items.
    foreach ([
      // Search.
      'search' => [
        'route'     => 'view.web_snippets_search.page_results',
        'icon'      => 'search',
        'text'      => t('Search<span class="visually-hidden"> web snippets</span>'),
        'titleAttr' => t('Search web snippets.'),
      ],
      // View all tags.
      'view-all-tags' => [
        'route'     => 'view.tags.page_web_tags',
        'icon'      => 'bookmark_outline',
        'text'      => t('Tags<span class="visually-hidden"> (view all web development tags)</span>'),
        'titleAttr' => t('View all web development tags.'),
      ],
      // RSS feed.
      'feed' => [
        'route'     => 'view.web_snippets.feed',
        'icon'      => 'rss',
        'text'      => t('Subscribe<span class="visually-hidden"> to the web snippets RSS feed</span>'),
        'titleAttr' => t('View the web snippets RSS feed.'),
      ],
    ] as $key => $data) {
      $items[$key] = [
        'linkAttributes'  => new Attribute(),
        // Define the icon here rather than using {{ ambientimpact_icon() }} in
        // the template to ensure any HTML in the text doesn't get escaped. This
        // is probably better for render caching as well.
        'content'         => [
          '#type'           => 'ambientimpact_icon',
          '#bundle'         => 'core',
          '#icon'           => $data['icon'],
          '#text'           => $data['text'],
        ],
      ];

      // Convert the Url object into a string 'href' attribute.
      $items[$key]['linkAttributes']->setAttribute(
        'href', Url::fromRoute($data['route'])->toString()
      );

      // Add a 'title' attribute to the link, if one is available.
      if (!empty($data['titleAttr'])) {
        $items[$key]['linkAttributes']
          ->setAttribute('title', $data['titleAttr']);
      }
    }
  }
}
