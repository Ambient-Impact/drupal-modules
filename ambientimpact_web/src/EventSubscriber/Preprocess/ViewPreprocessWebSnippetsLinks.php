<?php

namespace Drupal\ambientimpact_web\EventSubscriber\Preprocess;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\preprocess_event_dispatcher\Event\ViewPreprocessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Preprocess variables for the 'web_snippets' view.
 *
 * This adds links to the view header to search snippets, view all web
 * development tags, to view the web snippets RSS feed, and to view the about
 * page.
 *
 * @see \Drupal\preprocess_event_dispatcher\Event\ViewPreprocessEvent
 */
class ViewPreprocessWebSnippetsLinks implements EventSubscriberInterface {
  use StringTranslationTrait;

  /**
   * Name of the configuration that we're editing.
   */
  protected const CONFIG_NAME = 'ambientimpact_web.snippets';

  /**
   * The Drupal configuration object factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Drupal node entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The Drupal string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal configuration object factory service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type plug-in manager.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    ConfigFactoryInterface      $configFactory,
    EntityTypeManagerInterface  $entityTypeManager,
    TranslationInterface        $stringTranslation
  ) {
    $this->configFactory      = $configFactory;
    $this->nodeStorage        = $entityTypeManager->getStorage('node');
    $this->stringTranslation  = $stringTranslation;
  }

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
   * This adds links to the view header when in the 'page' display to search
   * snippets, view all web development tags, and to view the web snippets RSS
   * feed.
   *
   * Note that we could use '#theme' => 'links', but that doesn't provide a
   * simple way to define attributes for the <li> elements other than a single
   * class via the #items key - multiple classes separated by spaces in the
   * key will be joined together by Drupal with the '-' character, so that
   * hack from Drupal 7 no longer works.
   *
   * @param \Drupal\preprocess_event_dispatcher\Event\ViewPreprocessEvent $event
   *   The event object.
   *
   * @see \Drupal\ambientimpact_web\EventSubscriber\Theme\ThemeWebSnippetsLinks::theme()
   *   Defines the 'web_snippets_links' render element.
   */
  public function preprocessView(ViewPreprocessEvent $event) {
    /* @var \Drupal\preprocess_event_dispatcher\Event\Variables\ViewEventVariables $variables */
    $variables = $event->getVariables();
    $view = $variables->getView();

    if (
      $view->id() !== 'web_snippets' ||
      $view->current_display !== 'page'
    ) {
      return;
    }

    $header = &$variables->getByReference('header');

    $header['web_snippets_links'] = [
      '#theme'      => 'web_snippets_links',
      '#items'      => [],
      '#weight'     => 100,
      '#cache'      => [
        // This ensures that the links are rebuilt whenever the web snippets
        // configuration is changed.
        'tags'  => ['config:' . self::CONFIG_NAME],
      ],
    ];

    $items = &$header['web_snippets_links']['#items'];

    /** @var array */
    $linkTypes = [
      // Search.
      'search' => [
        'route'       => 'view.web_snippets_search.page_results',
        'iconName'    => 'loupe',
        'iconBundle'  => 'libricons',
        'text'        => $this->t('Search<span class="visually-hidden"> web snippets</span>'),
        'titleAttr'   => $this->t('Search web snippets.'),
      ],
      // View all tags.
      'view-all-tags' => [
        'route'       => 'view.web_tags.page',
        'iconName'    => 'bookmark-outline',
        'iconBundle'  => 'core',
        'text'        => $this->t('Tags<span class="visually-hidden"> (view all web development tags)</span>'),
        'titleAttr'   => $this->t('View all web development tags.'),
      ],
      // RSS feed.
      'feed' => [
        'route'       => 'view.web_snippets.feed',
        'iconName'    => 'rss',
        'iconBundle'  => 'core',
        'text'        => $this->t('Subscribe<span class="visually-hidden"> to the web snippets RSS feed</span>'),
        'titleAttr'   => $this->t('View the web snippets RSS feed.'),
      ],
    ];

    /** @var \Drupal\Core\Config\ImmutableConfig */
    $config = $this->configFactory->get(self::CONFIG_NAME);

    /** @var string|null */
    $aboutNid = $config->get('about_node');

    if (!\is_null($aboutNid)) {

      /** @var \Drupal\node\NodeInterface|null */
      $aboutNode = $this->nodeStorage->load($aboutNid);

      if (\is_object($aboutNode)) {
        $linkTypes['about'] = [
          'url'         => $aboutNode->toUrl(),
          'iconName'    => 'info',
          'iconBundle'  => 'core',
          'text'        => $this->t('About<span class="visually-hidden"> web snippets</span>'),
          'titleAttr'   => $this->t('What even are these?'),
        ];
      }

    }

    // Generate the items.
    foreach ($linkTypes as $key => $data) {
      $items[$key] = [
        'link_attributes' => new Attribute(),
        'linkURL'         => isset($data['url']) ? $data['url'] : Url::fromRoute($data['route']),
        // Define the icon here rather than using {{ ambientimpact_icon() }} in
        // the template to ensure any HTML in the text doesn't get escaped. This
        // is probably better for render caching as well.
        'content'         => [
          '#type'           => 'ambientimpact_icon',
          '#bundle'         => $data['iconBundle'],
          '#icon'           => $data['iconName'],
          '#text'           => $data['text'],
        ],
      ];

      // Add a 'title' attribute to the link, if one is available.
      if (!empty($data['titleAttr'])) {
        $items[$key]['link_attributes']
          ->setAttribute('title', $data['titleAttr']);
      }
    }
  }
}
