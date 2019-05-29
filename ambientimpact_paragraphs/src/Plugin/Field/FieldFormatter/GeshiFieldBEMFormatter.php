<?php

namespace Drupal\ambientimpact_paragraphs\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\geshifilter\GeshiFilter;

/**
 * Plugin implementation of the 'geshifield_bem' formatter.
 *
 * This alters the GeshiField output, replacing the default GeSHi classes with
 * BEM classes, and wraps the output in a render array for easier alteration.
 *
 * @FieldFormatter(
 *   id = "geshifield_bem",
 *   label = @Translation("GeshiField BEM classes"),
 *   field_types = {
 *     "geshifield"
 *   }
 * )
 */
class GeshiFieldBEMFormatter extends FormatterBase implements ContainerFactoryPluginInterface {
  /**
   * The Drupal renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a GeshiFieldBEMFormatter object.
   *
   * @param string $pluginID
   *   The plugin_id for the formatter.
   *
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition
   *   The definition of the field to which the formatter is associated.
   *
   * @param array $settings
   *   The formatter settings.
   *
   * @param string $label
   *   The formatter label display setting.
   *
   * @param string $viewMode
   *   The view mode.
   *
   * @param array $thirdPartySettings
   *   Any third party settings settings.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   */
  public function __construct(
    $pluginID,
    $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings,
    $label,
    $viewMode,
    array $thirdPartySettings,
    RendererInterface $renderer
  ) {
    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings
    );

    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginID,
    $pluginDefinition
  ) {
    return new static(
      $pluginID,
      $pluginDefinition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $elements = [];
    $enabledLanguages = GeshiFilter::getEnabledLanguages();
    $baseClass = 'code-highlighted';

    foreach ($items as $delta => $item) {
      $initialRenderArray = [
        '#theme'      => 'geshifield_default',
        '#language'   => $item->language,
        '#sourcecode' => $item->sourcecode,
      ];
      $languageHumanName = $enabledLanguages[$item->language];

      // Ideally, we wouldn't render here like the geshifield_default formatter
      // does, since it's against Drupal 8 best practices, but we need the
      // rendered GeSHi output. GeSHi doesn't provide any way to change the
      // class names so we have to search and replace what it renders.
      $renderedCode = $this->renderer->render($initialRenderArray);

      // This array contains search and replace keys, which are arrays to be
      // passed to str_replace.
      // @see https://php.net/manual/en/function.str-replace.php
      $codeReplacements = [
        'search'  => [],
        'replace' => [],
      ];

      // Code highlight classes that have numberical variations.
      foreach ([
        're'  => 'reserved-word',
        'kw'  => 'keyword',
        'sy'  => 'symbol',
        'br'  => 'bracket',
        'nu'  => 'number',
        'co'  => 'comment',
        'me'  => 'method',
      ] as $old => $new) {
        // Depending on the language, these can have up to (and possibly more
        // than) 5 variations.
        for ($i = 0; $i <= 5; $i++) {
          $codeReplacements['search'][]   = '<span class="' . $old . $i . '">';
          $codeReplacements['replace'][]  =
            '<span class="' . $baseClass . '__' . $new . ' ' .
              $baseClass . '__' . $new .'--' . $i . '">';
        }
      }

      // Code highlight classes that do *not* have numberical variations.
      foreach ([
        'st_h'    => 'string',
      ] as $old => $new) {
        $codeReplacements['search'][]   = '<span class="' . $old . '">';
        $codeReplacements['replace'][]  =
          '<span class="' . $baseClass . '__' . $new . '">';
      }

      // Multi-line comments; these need to be handled separately because they
      // need both the comment class and the modifier.
      $codeReplacements['search'][]   = '<span class="coMULTI">';
      $codeReplacements['replace'][]  =
        '<span class="' . $baseClass . '__comment ' .
          $baseClass . '__comment--multi-line">';

      // Replace!
      $renderedCode = str_replace(
        $codeReplacements['search'],
        $codeReplacements['replace'],
        $renderedCode
      );

      $elements[$delta] = [
        '#type'       => 'html_tag',
        '#tag'        => 'div',
        '#attributes' => [
          'class'       => [
            $baseClass,
            $baseClass . '--language-' . $item->language,
          ],
        ],
        // Pass the human name of the language and the base class along so that
        // any code altering the render array has these handy in a predictable
        // place.
        '#code_human_name'  => $languageHumanName,
        '#base_class' => $baseClass,

        'pre'         => [
          '#type'       => 'html_tag',
          '#tag'        => 'pre',
          '#attributes' => [
            'class'       => [
              $baseClass . '__code',
            ],
            'data-code-language-human-name'   => $languageHumanName,
            'data-code-language-machine-name' => $item->language,
          ],
          // Remove the wrapper elements generated by the geshifilter module.
          // @see \Drupal\geshifilter\GeshiFilterProcess::geshiProcess()
          '#value'      => preg_replace(
            [
              // Opening tags.
              '/^<div class="geshifilter"><div class="[^"]+"><pre class="[^"]+">/',
              // Closing tags.
              '/<\/pre><\/div><\/div>$/',
            ],
            '',
            $renderedCode
          ),
        ],
      ];
    }

    return $elements;
  }

}
