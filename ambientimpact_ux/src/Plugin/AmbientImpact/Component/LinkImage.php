<?php

namespace Drupal\ambientimpact_ux\Plugin\AmbientImpact\Component;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ambientimpact_core\ComponentBase;
use Drupal\ambientimpact_core\Utility\Html;

/**
 * Link: image component.
 *
 * @Component(
 *   id = "link.image",
 *   title = @Translation("Link: image"),
 *   description = @Translation("Marks links to image files and links that contain image elements with a class, and marks them as external, causing them to open in a new tab.")
 * )
 */
class LinkImage extends ComponentBase {
  /**
   * The Drupal MIME type guesser service.
   *
   * @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface
   */
  protected $MIMEGuesser;

  /**
   * Constructor; saves dependencies.
   *
   * @param array $configuration
   *   A configuration array containing information about the plug-in instance.
   *
   * @param string $pluginID
   *   The plugin_id for the plug-in instance.
   *
   * @param array $pluginDefinition
   *   The plug-in implementation definition.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The Drupal module handler service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Drupal language manager service.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @param \Drupal\Component\Serialization\SerializationInterface $yamlSerialization
   *   The Drupal YAML serialization class.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $htmlCacheService
   *   The Component HTML cache service.
   *
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $MIMEGuesser
   *   The Drupal MIME type guesser service.
   */
  public function __construct(
    array $configuration, string $pluginID, array $pluginDefinition,
    ModuleHandlerInterface $moduleHandler,
    LanguageManagerInterface $languageManager,
    RendererInterface $renderer,
    SerializationInterface $yamlSerialization,
    TranslationInterface $stringTranslation,
    CacheBackendInterface $htmlCacheService,
    MimeTypeGuesserInterface $MIMEGuesser
  ) {
    parent::__construct(
      $configuration, $pluginID, $pluginDefinition,
      $moduleHandler,
      $languageManager,
      $renderer,
      $yamlSerialization,
      $stringTranslation,
      $htmlCacheService
    );

    // Save dependencies.
    $this->MIMEGuesser = $MIMEGuesser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginID, $pluginDefinition
  ) {
    return new static(
      $configuration, $pluginID, $pluginDefinition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('renderer'),
      $container->get('serialization.yaml'),
      $container->get('string_translation'),
      $container->get('cache.ambientimpact_component_html'),
      $container->get('file.mime_type.guesser')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'MIMETypes' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/svg+xml',
      ],
      'elementTypes' => [
        'img',
        'picture',
        'svg',
        'canvas',
      ],
      'classes' => [
        'imageDestinationLink'  => 'ambientimpact-is-image-link',
        'linkContainsImage'     => 'ambientimpact-link-has-image',
        'linkContainsImageText' => 'ambientimpact-link-has-image__text',
      ],
    ];
  }

  /**
   * Get image MIME types recognized by this component.
   *
   * @return array
   *   An array of MIME types as strings.
   */
  public function getImageMIMETypes(): array {
    return $this->getConfiguration()['MIMETypes'];
  }

  /**
   * Determine if a passed MIME type is considered an image.
   *
   * @param string $MIMEType
   *   A MIME type to look at.
   *
   * @return boolean
   *   True if $MIMEType is considered an image, false otherwise.
   */
  public function isImageMIMEType(string $MIMEType): bool {
    return in_array($MIMEType, $this->getImageMIMETypes());
  }

  /**
   * Get HTML element names for all recognized image elements.
   *
   * @return array
   *   An array of element name strings.
   */
  public function getImageElementTypes(): array {
    return $this->getConfiguration()['elementTypes'];
  }

  /**
   * Determine if a given element selector is considered an image element.
   *
   * @param string $elementType
   *   The element selector to look at.
   *
   * @return boolean
   *   True if $elementType is considered an image element, false otherwise.
   */
  public function isImageElementType(string $elementType): bool {
    return in_array($MIMEType, $this->getImageElementTypes());
  }

  /**
   * Determine if the passed URI looks like a recogized image file.
   *
   * @param string $uri
   *   A URI to look at.
   *
   * @return boolean
   *   True if $uri looks like an image file, false otherwise.
   */
  public function isURIDestinationImage(string $uri): bool {
    $guessedMIMEType = $this->MIMEGuesser->guess($uri);

    // If the MIME type can't be guessed by the MIME type guesser, it'll return
    // null, which is still a valid value to search an array for, but of course
    // it'll return false because it won't be found.
    return $this->isImageMIMEType($guessedMIMEType);
  }

  /**
   * Process a provided link for this component.
   *
   * This does two things:
   * - Determines if the link points a recognized image MIME type, adding a
   *   class to the link if so.
   *
   * - Determines if the link contains a recognized image element, and adds a
   *   class to the link if so. Additionally, if the link also contains any text
   *   nodes, they're wrapped in elements so that the 'link.underline' component
   *   can correctly apply underlines only behind text and not images.
   *
   * @param \DOMElement|array &$link
   *   Either an instance of \DOMElement or a link settings array as found in
   *   \hook_link_alter().
   */
  public function processLink(&$link) {
    $config = $this->getConfiguration();

    $classes = [];

    $href = '';

    $hasImageElement = false;

    $hasText = false;

    // Check if this is a \DOMElement.
    if ($link instanceof \DOMElement) {
      // Extract the classes into an array if found.
      if ($link->hasAttribute('class')) {
        $classes = explode(' ', $link->getAttribute('class'));
      }

      // Extract the href attribute if found.
      if ($link->hasAttribute('href')) {
        $href = $link->getAttribute('href');
      }

      // Bail if no child nodes are present as this could cause a fatal error
      // when creating the Symfony DomCrawler.
      if (empty($link->childNodes)) {
        return;
      }

      /** @var \Symfony\Component\DomCrawler\Crawler */
      $linkContentCrawler = new Crawler($link->childNodes);

    // Check if this is a link settings array, like that found in
    // \hook_link_alter().
    } else if (is_array($link) && isset($link['options'])) {
      // Extract the classes into an array if found.
      if (isset($link['options']['attributes']['class'])) {
        $classes = $link['options']['attributes']['class'];
      }

      // Get the link's href from the \Drupal\Core\Url object.
      $href = $link['url']->toString();

      // If this a TranslatableMarkup, get the untranslated string as the link
      // content.
      if ($link['text'] instanceof TranslatableMarkup) {
        $linkContent = $link['text']->getUntranslatedString();

      // Otherwise, just cast the link text as a string, which catches both
      // when it's already a string and when it's a Markup object.
      } else {
        $linkContent = (string) $link['text'];
      }

      // Bail if there's no link content for whatever reason.
      if (empty($linkContent)) {
        return;
      }

      // Create a new Symfony DomCrawler with the link content.
      /** @var \Symfony\Component\DomCrawler\Crawler */
      $linkContentCrawler = new Crawler($linkContent);

    // If neither was provided, return here.
    } else {
      return;
    }

    // Attempt to find any image element types within the link, setting
    // $hasImageElement to true if one or more is found.
    if (
      $linkContentCrawler->filter(
        implode(',', $config['elementTypes'])
      )->count() > 0
    ) {
      $hasImageElement = true;
    }

    // Find any text nodes in the link regardless of depth.
    $textCrawler = $linkContentCrawler->filterXPath('//text()');

    // If any text nodes are found, set $hasText to true.
    if ($textCrawler->count() > 0) {
      $hasText = true;
    }

    // If this link's href appears to point to a recognized image file MIME
    // type, add a class indicating so.
    if ($this->isURIDestinationImage($href)) {
      $classes[] = $config['classes']['imageDestinationLink'];
    }

    // Add the class indicating this link has an image if one was found. Note
    // that we don't care if there are text nodes when adding this class.
    if ($hasImageElement === true) {
      $classes[] = $config['classes']['linkContainsImage'];
    }

    // If the link contains both an image element and text nodes, wrap the text
    // nodes in an element.
    if ($hasImageElement === true && $hasText === true) {
      foreach ($textCrawler as $textNode) {
        // Skip text nodes that have no content or contain only white-space
        // characters.
        if ($textNode->isWhitespaceInElementContent()) {
          continue;
        }

        $textContainer = $textNode->ownerDocument
          // Note that we have to escape any special HTML characters like '<',
          // '>', '&', etc., or we'll get a DOM warning the text won't be
          // parsed correctly.
          ->createElement('span', Html::escape($textNode->wholeText));

        $textContainer->setAttribute(
          'class',
          $config['classes']['linkContainsImageText']
        );

        $textNode->parentNode->replaceChild($textContainer, $textNode);
      }
    }

    // Now that we've extracted any classes, href, and detected whether there
    // are image elements and text nodes in the link, we can alter the link
    // contents and save any new classes to the link.
    if ($link instanceof \DOMElement) {
      // We don't need nor want to render a \DOMElement, as calling code is
      // expected to do that when it's done with it.

      // Save classes back to the element.
      $link->setAttribute('class', implode(' ', $classes));

    } else if (is_array($link)) {
      // If the original link content was a string, it will have been normalized
      // into a DOM structure with a <body> and <p> element.
      $linkContentCrawlerNormalized = $linkContentCrawler->filter('body > p');

      // If the above normalized structure returns any results, use it as the
      // content crawler.
      if ($linkContentCrawlerNormalized->count() > 0) {
        $linkContentCrawler = $linkContentCrawlerNormalized;

      // If the structure didn't contain a <p> element, try again with just the
      // <body>. Yes, this can happen.
      } else {
        $linkContentCrawlerNormalized = $linkContentCrawler->filter('body');

        if ($linkContentCrawlerNormalized->count() > 0) {
          $linkContentCrawler = $linkContentCrawlerNormalized;
        }
      }

      // Try to render the content, returning if the Symfony DomCrawler throws
      // an exception.
      try {
        $linkContentRendered = $linkContentCrawler->html();

      } catch (\Exception $exception) {
        return;
      }

      // If the original link text was a string, set the HTML as a string.
      if (is_string($link['text'])) {
        $link['text'] = $linkContentRendered;

      // If it was a Markup object, create a new Markup object with the HTML.
      } else if ($link['text'] instanceof Markup) {
        $link['text'] = Markup::create($linkContentRendered);

      // If it was a TranslatableMarkup, create a new one with the updated HTML,
      // and copy the arguments and options from the original object.
      } else if ($link['text'] instanceof TranslatableMarkup) {
        $link['text'] = new TranslatableMarkup(
          $linkContent,
          $link['text']->getArguments(),
          $link['text']->getOptions()
        );
      }

      // Save classes back to the element.
      $link['options']['attributes']['class'] = $classes;
    }
  }
}
