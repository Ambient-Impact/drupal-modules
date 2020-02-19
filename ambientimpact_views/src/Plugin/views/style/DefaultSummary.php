<?php

namespace Drupal\ambientimpact_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\DefaultSummary as CoreDefaultSummary;

/**
 * Extension of the default style plugin for Views summaries.
 *
 * This adds a 'separate_date_arguments' option for summaries on 'date_fulldate'
 * and 'date_year_month' argument/contextual filter plug-in forms.
 *
 * This intentionally does not have an annotation to avoid being picked up as
 * a separate plug-in.
 *
 * @see ambientimpact_views_preprocess_views_view_summary()
 *   Rewrites the output based on the 'separate_date_arguments' option.
 */
class DefaultSummary extends CoreDefaultSummary {
  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['separate_date_arguments'] = ['default' => false];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $formState) {
    parent::buildOptionsForm($form, $formState);

    $storage = &$formState->getStorage();

    if (
      isset($storage['type']) &&
      $storage['type'] === 'argument' &&
      isset($storage['id']) &&
      (
        $storage['id'] === 'created_fulldate' ||
        $storage['id'] === 'created_year_month'
      )
    ) {
      $form['separate_date_arguments'] = [
        '#type'   => 'checkbox',
        '#default_value' => $this->options['separate_date_arguments'],
        '#title'  => $this->t('Separate date components in URL'),
        '#description'  => $this->t(
          'This splits the generated URL with a slash into <strong>@format</strong> format. Don\'t forget to make sure they point to valid paths.',
          [
            '@format' => $storage['id'] === 'created_fulldate' ?
              'YYYY/MM/DD' : 'YYYY/MM',
          ]
        ),
      ];
    }
  }
}
