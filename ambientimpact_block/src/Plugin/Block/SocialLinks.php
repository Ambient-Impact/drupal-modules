<?php

namespace Drupal\ambientimpact_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\SortArray;

/**
 * Social links block.
 *
 * @Block(
 *   id = "ambientimpact_block_social_links",
 *   admin_label = @Translation("Social links"),
 *   category = @Translation("Ambient.Impact"),
 * )
 */
class SocialLinks extends BlockBase implements BlockPluginInterface {
	/**
	 * {@inheritdoc}
	 *
	 * Retrieve and set the default configuration for this block instance.
	 */
	public function defaultConfiguration() {
		$defaultConfig =
			\Drupal::config('ambientimpact_block.social_links')->get();
		$defaultNetworkConfig =
			\Drupal::config('ambientimpact_block.social_links_network')->get();

		// Remove these keys as we shouldn't save them. The langcode
		// specifically would be redundant as Drupal already saves the block
		// language in the config for us.
		foreach ([&$defaultConfig, &$defaultNetworkConfig] as &$config) {
			unset($config['langcode']);
			unset($config['_core']);
		}

		// Merge the pre-set networks over the network defaults.
		foreach ($defaultConfig['networks'] as $machineName => $network) {
			$defaultConfig['networks'][$machineName] = NestedArray::mergeDeep(
				$defaultNetworkConfig,
				$defaultConfig['networks'][$machineName]
			);
		}

		return ['social_links' => $defaultConfig];
	}

	/**
	 * {@inheritdoc}
	 *
	 * Build the render array for this social links block instance.
	 */
	public function build() {
		$blockConfig	= $this->getConfiguration()['social_links'];
		$blockBaseClass	= 'ambientimpact-block-social-links';
		$renderArray	= [];

		// Add classes to the block to target the alignment on both the list and
		// the block title.
		$renderArray['#attributes']['class'][] = $blockBaseClass;
		$renderArray['#attributes']['class'][] =
			$blockBaseClass. '--alignment-' . $blockConfig['alignment'];

		$renderArray['social_links'] = [
			'#theme'		=> 'ambientimpact_block_social_links',

			// Pass the base BEM class as a variable so that we don't have to
			// define this in too many places.
			'#base_class'	=> 'ambientimpact-social-links',

			// Attach assets.
			'#attached'		=> [
				'library'		=> ['ambientimpact_block/component.social_links'],
			],
		];

		// Set all config entries as variables for the template.
		foreach ($blockConfig as $key => $value) {
			$renderArray['social_links']['#' . $key] = $value;
		}

		foreach (
			$renderArray['social_links']['#networks'] as
			$machineName => &$network
		) {
			// Remove networks that don't have a URL, both so that they aren't
			// displayed and also to avoid Twig errors.
			if (empty($network['url'])) {
				unset($renderArray['social_links']['#networks'][$machineName]);

				continue;
			}

			// The content for this link. Note that we don't include
			// @accessibilitytext as that's replaced in the template with
			// markup. Drupal's translation system should also strip out/escape
			// any harmful markup from the content.
			if ($blockConfig['display'] === 'text_only') {
				$network['content'] = $this->t(
					$blockConfig['link_text'], [
						'@pronoun'	=> $blockConfig['link_text_pronoun'],
					]
				);
			} else {
				$network['content'] = [
					'#type'					=> 'ambientimpact_icon',
					'#bundle'				=> 'brands',
					'#icon'					=> $machineName,
					'#text'					=> $this->t(
						$blockConfig['link_text'], [
							'@pronoun'	=> $blockConfig['link_text_pronoun'],
						]
					),
				];
			}

			// Pass the accessibility text separately to the template. Since
			// this is run through Drupal's translation system, it should strip
			// out/escape any harmful markup from the content.
			$network['contentAccessibilityText'] = $this->t(
				$blockConfig['link_text_accessibility'],
				['@network' => $network['title']]
			);

			// Title attribute for the link, displayed as a tooltip. Contains
			// full text.
			$network['titleAttribute'] = $this->t(
				$blockConfig['link_text'], [
					'@pronoun'				=>
						$blockConfig['link_text_pronoun'],
					'@accessibilitytext'	=>
						$network['contentAccessibilityText'],
				]
			);

			// Set the icon text as visually hidden if the block is set to
			// display only icons and no text.
			if ($blockConfig['display'] === 'icon_only') {
				$network['content']['#textDisplay'] = 'visuallyHidden';
			}
		}

		// Sort the remaining networks by weight.
		// @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21SortArray.php/function/SortArray%3A%3AsortByWeightElement/8.6.x
		// @see https://www.drupal.org/node/2181331
		uasort(
			$renderArray['social_links']['#networks'],
			['Drupal\Component\Utility\SortArray', 'sortByWeightElement']
		);

		return $renderArray;
	}

	/**
	 * {@inheritdoc}
	 *
	 * Generates the structure for this block's configuration form.
	 */
	public function blockForm($form, FormStateInterface $formState) {
		$form			= parent::blockForm($form, $formState);
		$blockConfig	= $this->getConfiguration()['social_links'];
		$sharedConfig	= \Drupal::config(
			'ambientimpact_block.social_links_shared'
		);

		$socialLinksIdentifier	= 'ambientimpact-block-social-links';
		$settingsSectionClass	= $socialLinksIdentifier . '-settings-edit';
		$networks				= [];
		$networksSectionClass	= $socialLinksIdentifier . '-networks-edit';

		// Attach the admin assets.
		$form['#attached']['library'][] =
			'ambientimpact_block/component.social_links.admin';

		// The link settings fieldset.
		$form['link_settings'] = [
			'#type'				=> 'fieldset',
			'#title'			=> $this->t('Link settings'),
			'#attributes'		=> [
				'class'				=> [$settingsSectionClass],
			],

			'link_text'				=> [
				'#type'				=> 'textfield',
				'#title'			=> $this->t('Text'),
				'#description'		=> $this->t('The text that will be used in the link text/title attribute for each social network. Note that <code>@pronoun</code> and <code>@accessibilitytext</code> are replaced when the block is displayed, and are required. Setting this to an empty value will reset to the default text.'),
				'#default_value'	=> $blockConfig['link_text'],
			],

			'link_text_pronoun'		=> [
				'#type'				=> 'textfield',
				'#title'			=> $this->t('Pronoun'),
				'#description'		=> $this->t('The pronoun to replace in the link text/title attribute. Suggested values are "us" or "me", but any string can be used.'),
				'#required'			=> true,
				'#default_value'	=> $blockConfig['link_text_pronoun'],
			],

			'link_text_accessibility'	=> [
				'#type'				=> 'textfield',
				'#title'			=> $this->t('Accessibility text'),
				'#description'		=> $this->t('This text is placed replaces the <code>@accessibilitytext</code> in the "Text" field. This provides additional information to people accessing the site with a screen reader and other assitive technology, and will be visible in the tooltip (if displaying only icons or icons and text) and will be placed in the text itself if set to display text only. The <code>@network</code> tag is replaced with the name of the social network, and is required. Setting this to an empty value will reset it to the default value.'),
				'#default_value'	=> $blockConfig['link_text_accessibility'],
			],

			'display'			=> [
				'#type'				=> 'radios',
				'#title'			=> $this->t('Display'),
				'#description'		=> $this->t('How the links will be displayed.'),
				'#required'			=> true,
				'#options'			=> $sharedConfig->get('display_types'),
				'#default_value'	=> $blockConfig['display'],
			],

			'orientation'		=> [
				'#type'				=> 'radios',
				'#title'			=> $this->t('Orientation'),
				'#description'		=> $this->t('How the list of links will be oriented.'),
				'#required'			=> true,
				'#options'			=> $sharedConfig->get('orientation_types'),
				'#default_value'	=> $blockConfig['orientation'],
			],

			'alignment'		=> [
				'#type'				=> 'radios',
				'#title'			=> $this->t('Alignment'),
				'#description'		=> $this->t('How the list of links will be aligned.'),
				'#required'			=> true,
				'#options'			=> $sharedConfig->get('alignment_types'),
				'#default_value'	=> $blockConfig['alignment'],
			],
		];

		// Social networks fieldset.
		$form['social_networks'] = [
			'#type'				=> 'fieldset',
			'#title'			=> $this->t('Social networks'),
			'#description'		=> $this->t(
				'Only networks that have a URL will be displayed on the site. Deleting the contents of a URL field will cause this block to not display that social network link.'
			),
			'#attributes'		=> [
				'class'				=> [$networksSectionClass],
			],

			// The table containing the individual social networks. This has
			// table drag functionality.
			'networks'	=> [
				'#type'			=> 'table',
				'#header'			=> [
					$this->t('Network'),
					$this->t('URL'),
					$this->t('Weight'),
				],
				'#empty'		=> $this->t('No social networks are available.'),
				'#attributes'	=> [
					'class'			=> [
						$networksSectionClass . '__networks-table'
					],
				],
				'#tabledrag' => [
					[
						'action'		=> 'order',
						'relationship'	=> 'sibling',
						// This is the class of the weight form element for each
						// row. Table drag needs this to know what field to save
						// the updated weight to.
						'group'			=> $networksSectionClass . '__weight',
					],
				],
			],
		];

		// Sort the networks by weight.
		// @see https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Component%21Utility%21SortArray.php/function/SortArray%3A%3AsortByWeightElement/8.6.x
		// @see https://www.drupal.org/node/2181331
		uasort(
			$blockConfig['networks'],
			['Drupal\Component\Utility\SortArray', 'sortByWeightElement']
		);

		foreach ($blockConfig['networks'] as $machineName => $network) {
			$form['social_networks']['networks'][$machineName] = [
				'#attributes'	=> [
					'class' => ['draggable'],
				],
				'#weight'		=> $network['weight'],

				// Network title.
				'title_and_icon'	=> [
					'#type'					=> 'ambientimpact_icon',
					'#bundle'				=> 'brands',
					'#icon'					=> $machineName,
					'#text'					=> $network['title'],
					'#containerAttributes'	=> [
						'class'					=> [
							$networksSectionClass . '__title-and-icon',
						],
					],
				],

				// URL field.
				'url'	=> [
					'#type'				=> 'url',
					'#title'			=> $this->t('URL'),
					'#title_display'	=> 'invisible',
					'#default_value'	=> $network['url'],
					'#attributes'		=> [
						'class'				=> [
							$networksSectionClass . '__url'
						],
					],
				],

				// Weight field.
				'weight'	=> [
					'#type'				=> 'weight',
					'#title'			=> $this->t(
						'Weight for @network',
						['@network' => $network['title']]
					),
					'#title_display'	=> 'invisible',
					'#default_value'	=> $network['weight'],
					'#attributes'		=> [
						// This must be the same as the 'group' setting in the
						// table's #tabledrag array.
						'class'				=> [
							$networksSectionClass . '__weight'
						],
					],
				],
			];
		}

		return $form;
	}

	/**
	 * {@inheritdoc}
	 *
	 * This validates the link and accessibility text to ensure they contain the
	 * necessary tags.
	 */
	public function blockValidate($form, FormStateInterface $formState) {
		$linkTextPath				= ['link_settings', 'text'];
		$linkTextValue				= $formState->getValue($linkTextPath);
		$linkAccessibilityTextPath	= ['link_settings', 'accessibility_text'];
		$linkAccessibilityTextValue	= $formState->getValue(
			$linkAccessibilityTextPath
		);

		// Don't validate if field is empty as we fill it with the default
		// string if so.
		if (!empty(trim($linkTextValue))) {
			// Require the '@pronoun' tag.
			if (strpos($linkTextValue, '@pronoun') === false) {
				$formState->setErrorByName(
					$linkTextPath,
					$this->t('The <code>@pronoun</code> tag is required.')
				);
			}
			// Require the '@accessibilitytext' tag.
			if (strpos($linkTextValue, '@accessibilitytext') === false) {
				$formState->setErrorByName(
					$linkTextPath,
					$this->t('The <code>@accessibilitytext</code> tag is required.')
				);
			}
		}

		// Don't validate if field is empty as we fill it with the default
		// string if so.
		if (!empty(trim($linkAccessibilityTextValue))) {
			// Require the '@network' tag.
			if (strpos($linkAccessibilityTextValue, '@network') === false) {
				$formState->setErrorByName(
					$linkAccessibilityTextPath,
					$this->t('The <code>@network</code> tag is required.')
				);
			}
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * Save the form values to this block instance's config.
	 */
	public function blockSubmit($form, FormStateInterface $formState) {
		parent::blockSubmit($form, $formState);

		$defaultConfig	= \Drupal::config('ambientimpact_block.social_links');
		$blockConfig	= $this->getConfiguration()['social_links'];
		$formValues		= $formState->getValues();
		$savedValues	= [];

		// Not sure if Drupal strips out values that weren't in the form, so
		// only save values that are already present in the config to be safe.
		foreach ($blockConfig as $key => $savedValue) {
			if (isset($formValues['link_settings'][$key])) {
				$savedValues[$key] = $formValues['link_settings'][$key];
			}
		}

		// Reset these to the defaults if they're empty.
		foreach (['link_text', 'link_text_accessibility'] as $key) {
			if (empty($savedValues[$key])) {
				$savedValues[$key] = $defaultConfig->get($key);
			}
		}

		// Save the network settings.
		$savedValues['networks'] = $formValues['social_networks']['networks'];

		$this->setConfigurationValue('social_links', $savedValues);
	}
}
