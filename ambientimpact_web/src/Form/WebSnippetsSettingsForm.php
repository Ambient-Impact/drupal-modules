<?php

namespace Drupal\ambientimpact_web\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Web snippets settings form.
 */
class WebSnippetsSettingsForm extends ConfigFormBase {

  /**
   * Name of the configuration that we're editing.
   */
  protected const CONFIG_NAME = 'ambientimpact_web.snippets';

  /**
   * The Drupal node entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Form constructor; saves dependencies.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Drupal configuration object factory service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type plug-in manager.
   */
  public function __construct(
    ConfigFactoryInterface      $configFactory,
    EntityTypeManagerInterface  $entityTypeManager
  ) {

    parent::__construct($configFactory);

    $this->nodeStorage  = $entityTypeManager->getStorage('node');

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'web_snippets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['about_node'] = [
      '#type'               => 'entity_autocomplete',
      '#title'              => $this->t('About page'),
      '#description'        => $this->t(
        'The content to link to describing web snippets and their purpose.'
      ),
      '#target_type'        => 'node',
      '#selection_handler'  => 'default',
    ];

    /** @var \Drupal\Core\Config\ImmutableConfig */
    $config = $this->configFactory->get(self::CONFIG_NAME);

    /** @var string|null */
    $aboutNid = $config->get('about_node');

    if (!\is_null($aboutNid)) {

      /** @var \Drupal\node\NodeInterface|null */
      $aboutNode = $this->nodeStorage->load($aboutNid);

      if (\is_object($aboutNode)) {
        $form['about_node']['#default_value'] = $aboutNode;
      }

    }

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory->getEditable(self::CONFIG_NAME)
      ->set('about_node', $form_state->getValue('about_node'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
