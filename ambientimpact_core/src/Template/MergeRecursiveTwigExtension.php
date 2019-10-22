<?php

namespace Drupal\ambientimpact_core\Template;

use Twig\TwigFilter;
use Twig\Error\RuntimeError;
use Drupal\Component\Utility\NestedArray;

/**
 * merge_recursive() Twig function.
 *
 * Recursively merging two arrays in Twig can quickly become a nightmare of
 * nested merge() calls, so this provides a merge_recursive() function to avoid
 * that.
 *
 * @todo Add support for merging Attributes objects rather than overwriting?
 */
class MergeRecursiveTwigExtension extends \Twig_Extension {
  /**
   * Returns an array of filters to declare to Twig.
   */
  public function getFilters() {
    return [
      new TwigFilter('merge_recursive', [$this, 'mergeRecursive'])
    ];
  }

  /**
   * Merge two arrays recursively.
   *
   *  {% set items = {
   *    'apple':  ['fruit'],
   *    'orange': {'type': 'fruit'}
   *  } %}
   *
   *  {% set items = items|merge_recursive({
   *    'apple':  ['not-a-car'],
   *    'orange': {'not': 'car'}
   *  }) %}
   *
   *  {# items is now {
   *    'apple':  ['fruit', 'not-a-car'],
   *    'orange': {'type': 'fruit', 'not': 'car'}
   *  } #}
   *
   * @param array|\Traversable $array1
   *   An array or Traversable, the latter of which will be converted to an
   *   array before merging.
   *
   * @param array|\Traversable $array2
   *   An array or Traversable, the latter of which will be converted to an
   *   array before merging.
   *
   * @return array
   *   The merged array or Traversables as an array.
   *
   * @see \twig_array_merge()
   *   The core Twig 'merge' filter, which we're loosely based on.
   *
   * @see \Drupal\Component\Utility\NestedArray::mergeDeep()
   *   Used to merge the two arrays.
   *
   * @throws \Twig\Error\RuntimeError
   *   If either of the parameters are not an array or Traversable.
   */
  public function mergeRecursive($array1, $array2) {
    $arrays = [$array1, $array2];

    foreach ($arrays as $i => $array) {
      if ($array instanceof \Traversable) {
        $arrays[$i] = iterator_to_array($array, true);
      } else if (!\is_array($array)) {
        throw new RuntimeError(sprintf('The merge_recursive filter only works with arrays or "Traversable", got "%s" as argument %s.', \gettype($array), $i));
      }
    }

    return NestedArray::mergeDeep($array1, $array2);
  }
}
