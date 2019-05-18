<?php

namespace Drupal\ambientimpact_paragraphs\Plugin\Field\FieldFormatter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\ambientimpact_paragraphs\Plugin\Field\FieldFormatter\GeshiFieldBEMFormatter;

/**
 * Plugin implementation of the 'geshifield_bem_paragraphs' formatter.
 *
 * This builds on the 'geshifield_bem' formatter with specific changes for
 * Paragraphs items:
 *   - Adds a heading to the output.
 *
 * @FieldFormatter(
 *   id = "geshifield_bem_paragraphs",
 *   label = @Translation("GeshiField BEM classes for Paragraphs"),
 *   field_types = {
 *     "geshifield"
 *   }
 * )
 */
class GeshiFieldBEMParagraphsFormatter extends GeshiFieldBEMFormatter {
  use StringTranslationTrait;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $stringTranslation;

  /**
   * Constructs a GeshiFieldBEMParagraphsFormatter object.
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
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    $pluginID,
    $pluginDefinition,
    FieldDefinitionInterface $fieldDefinition,
    array $settings,
    $label,
    $viewMode,
    array $thirdPartySettings,
    RendererInterface $renderer,
    TranslationInterface $stringTranslation
  ) {
    parent::__construct(
      $pluginID, $pluginDefinition, $fieldDefinition, $settings, $label,
      $viewMode, $thirdPartySettings,
      $renderer
    );

    $this->stringTranslation = $stringTranslation;
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
      $container->get('renderer'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Port the tab size from Drupal 7.
   */
  public function viewElements(FieldItemListInterface $items, $langCode) {
    $elements = parent::viewElements($items, $langCode);

    foreach ($elements as $delta => &$element) {
      $baseClass          = $element['#base_class'];
      $languageHumanName  = $element['#code_human_name'];

      // Correct this to how JavaScript is supposed to be capitalized.
      if ($languageHumanName === 'Javascript') {
        $languageHumanName = $this->t('JavaScript');
      }

      // Rename HTML5 to just HTML, as that's the default standard nowadays.
      if ($languageHumanName === 'HTML5') {
        $languageHumanName = 'HTML';
      }

      $element['heading'] = [
        '#type'       => 'html_tag',
        '#tag'        => 'h3',
        '#attributes' => [
          'class'       => [
            $baseClass . '__heading',
          ],
          'title'       => $this->t(
            'Code language: @name',
            ['@name' => $languageHumanName]
          ),
        ],
        '#value'      => $this->t(
            '<span class="visually-hidden">Code language: </span>@name',
            ['@name' => $languageHumanName]
          ),
        '#weight'     => -1,
      ];
    }

    return $elements;
  }
}
