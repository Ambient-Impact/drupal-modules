<?php

namespace Drupal\ambientimpact_core\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Provides an icon render element.
 *
 * @RenderElement("ambientimpact_icon")
 */
class Icon extends RenderElement {
	/**
	 * {@inheritdoc}
	 */
	public function getInfo() {
		return [
			'#theme'		=> 'ambientimpact_icon',
			'#pre_render'	=> [
				[self::class, 'preRenderIcon'],
			],
		];
	}

	/**
	 * Pre-render callback to build icon attributes.
	 *
	 * @param array $element
	 *   The icon element render array.
	 *
	 * @return array
	 *   The modified $element render array.
	 */
	public static function preRenderIcon(array $element) {
		$componentManager =
			\Drupal::service('plugin.manager.ambientimpact_component');
		$iconBundleManager =
			\Drupal::service('plugin.manager.ambientimpact_icon_bundle');

		$iconConfig = $componentManager->getComponentConfiguration('icon');
		$containerBaseClass	= $iconConfig['containerBaseClass'];

		$uri = '';

		foreach ($iconConfig['defaults'] as $key => $value) {
			if (!isset($element['#' . $key]) && $key !== 'bundle') {
				$element['#' . $key] = $value;
			}
		}

		// Attach the component library.
		$element['#attached']['library'][] =
			'ambientimpact_core/component.icon';

		// Convert our attributes arrays into Attribute objects so that they're
		// sanitized and passed to Twig as something that can be easily printed.
		foreach ([
			'containerAttributes', 'iconAttributes', 'useAttributes',
			'textAttributes',
		] as $variableName) {
			$propertyKey = '#' . $variableName;

			$element[$propertyKey] = new Attribute(
				isset($element[$propertyKey]) ?
				$element[$propertyKey] :
				[]
			);
		}

		// Set BEM classes for the container, the <svg>, and the text element.
		$element['#containerAttributes']->addClass($containerBaseClass);
		$element['#iconAttributes']->addClass(
			$containerBaseClass . '__icon'
		);
		$element['#textAttributes']->addClass(
			$containerBaseClass . '__text'
		);

		// Add a class indicating the specific icon in use.
		if (isset($element['#icon'])) {
			$element['#containerAttributes']->addClass(
				$containerBaseClass . '--name-' . $element['#icon']
			);
		}

		// Decide whether to consider the icon standalone.
		if (isset($element['#standalone'])) {
			$standalone = $element['#standalone'];

		} else {
			if (
				!empty($element['#text']) &&
				!empty($element['#textDisplay']) &&
				$element['#textDisplay'] === 'visible'
			) {
				// Text is found and is visible, so we're not standalone.
				$standalone = false;
			} else {
				// Text not found, or is not visible, so we default to
				// standalone.
				$standalone = true;
			}
		}

		// Determine what container tag we should use.
		if (
			!empty($element['#containerTag']) &&
			is_string($element['#containerTag'])
		) {
			$containerTag = $element['#containerTag'];
		} else {
			$containerTag = 'span';
		}

		// If a bundle is present, use the bundle URL.
		if (!empty($element['#bundle'])) {
			$bundle =
				$iconBundleManager->getIconBundleInstance($element['#bundle']);

			if ($bundle !== false) {
				$urlObject = Url::fromUri(
					'base:' . $bundle->getPath()
				);

				$uri = $urlObject->toString();

				// Mark the bundle as being in use.
				$bundle->markUsed();

				// Add a class indicating the icon's bundle.
				$element['#containerAttributes']->addClass(
					$containerBaseClass . '--bundle-' . $element['#bundle']
				);
			}
		}

		// Fall back to the 'uri' variable if no bundle was specified. This can
		// be used to bypass fetching the bundle URL when outputting a template,
		// for example.
		if (empty($uri) && !empty($element['#uri'])) {
			$uri = $element['#uri'];
		}

		// Determine how to display the text.
		switch ($element['#textDisplay']) {
			case 'visuallyHidden':
				$element['#containerAttributes']->addClass(
					$containerBaseClass . '--text-visually-hidden'
				);

				break;

			case 'hidden':
				$element['#containerAttributes']->addClass(
					$containerBaseClass . '--text-hidden'
				);

				break;
		}

		// Add the standalone class if explicitly set to true.
		if ($standalone === true) {
			$element['#containerAttributes']->addClass(
				$containerBaseClass . '--icon-standalone'
			);
		}

		// set the <svg> attributes.
		foreach ([
			'viewBox'	=> '0 0 '. $element['#size'] . ' ' . $element['#size'],
			// Add inline dimensions in case of no CSS:
			// https://twitter.com/chriscoyier/status/799294264446287872
			'width'			=> $element['#size'],
			'height'		=> $element['#size'],
			// Hide icon from screen readers, as they are redundant:
			// http://www.456bereastreet.com/archive/201609/hiding_inline_svg_icons_from_screen_readers/
			'aria-hidden'	=> 'true',
		] as $key => $value) {
			$element['#iconAttributes']->setAttribute($key, $value);
		}

		// Set the icon file path and icon ID.
		$element['#useAttributes']->setAttribute(
			'xlink:href',
			$uri . '#icon-' . $element['#icon']
		);

		return $element;
	}
}
