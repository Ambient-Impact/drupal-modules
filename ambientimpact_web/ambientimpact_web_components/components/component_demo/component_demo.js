// -----------------------------------------------------------------------------
//   Ambient.Impact - Web - Component pages - Component demo component
// -----------------------------------------------------------------------------

AmbientImpact.addComponent('componentDemo', function(aiComponentDemo, $) {
  'use strict';

  /**
   * The base class for the Component demo container.
   *
   * @type {String}
   */
  var baseClass = 'ambientimpact-component-demo';

  /**
   * The class of the Component demo content container.
   *
   * @type {String}
   */
  var contentClass = baseClass + '__content';

  /**
   * The class of the Component demo actions container.
   *
   * @type {String}
   */
  var actionsClass = baseClass + '__actions';

  /**
   * The demo content element, wrapped in a jQuery object.
   *
   * @type {jQuery}
   */
  var $content = $('.' + contentClass).first();

  /**
   * The demo action element, wrapped in a jQuery object.
   *
   * @type {jQuery}
   */
  var $actions = $('.' + actionsClass).first();

  /**
   * An object containing provided tests, keyed by test machine name.
   *
   * @type {Object}
   */
  var tests = {};

  /**
   * Get the demo content container.
   *
   * @return {jQuery}
   *   A jQuery collection containing the demo content container, if found.
   */
  this.getDemoContentContainer = function() {
    if ($content.length === 0) {
      console.error(
        Drupal.t('The Component demo content container cannot be found.')
      );
    }

    return $content;
  };

  /**
   * Add a Component demo.
   *
   * This provides a standard way to add a demo to the demo page, triggered by
   * defined actions (i.e. buttons).
   *
   * @param {Object} demoObject
   *   An object containing the following key/value pairs:
   *   - 'machineName': The unique machine name to identify this set of demos
   *     as. This is usually the Component machine name.
   *
   *   - 'actions': An object of key/value pairs, where the keys are the machine
   *     name of a given action, and the value is an object with the following
   *     key/value pairs:
   *
   *     - 'label': The content to display as the action (button) text. Can
   *       include HTML.
   *
   *     - 'events': An optional object of key/value pairs to be passed to
   *       jQuery().on() to be bound to the action (button).
   *
   * @return {Boolean}
   *   True if the demo was processed without errors, false otherwise.
   */
  this.addDemo = function(demoObject) {
    var $actionItems = $();

    if ($content.length === 0) {
      console.error(
        Drupal.t('The Component demo content container cannot be found.')
      );

      return returnObject;
    }

    if (!('machineName' in demoObject)) {
      console.error(
        Drupal.t('Please specify a "machineName" key.')
      );

      return false;
    }

    if (!('actions' in demoObject)) {
      console.error(
        Drupal.t('Please specify an "actions" key.')
      );

      return false;
    }

    for (var actionMachineName in demoObject.actions) {
      if (!demoObject.actions.hasOwnProperty(actionMachineName)) {
        continue;
      }

      if (!('label' in demoObject.actions[actionMachineName])) {
        console.error(
          Drupal.t(
            'Action "@actionMachineName" does not have a "label" key.',
            {
              '@actionMachineName': actionMachineName
            }
          )
        );

        return false;
      }

      var action = demoObject.actions[actionMachineName];

      var $action = $('<button></button>');

      var $listItem = $('<li></li>');

      $action
        .addClass([
          actionsClass + '-action-button',
          actionsClass + '-action-button--' + actionMachineName,
          'material-button'
        ].join(' '))
        .append(action.label)
        .appendTo($listItem);

      $listItem
        .addClass([
          actionsClass + '-action-item',
          actionsClass + '-action-item--' + actionMachineName,
        ].join(' '))
        .appendTo($actions);

      // Events are optional as the code calling this could be passing the
      // created actions off to a third-party library, in which case it may not
      // be able to define the events itself ahead of time.
      if ('events' in action) {
        $action.on(action.events);
      }

      // Make the action element available on the object so calling code can
      // access it and do whatever it needs with it.
      action.$action = $action;
    }

    if ($actions.children().length > 0) {
      $actions.removeClass('hidden');
    }

    return true;
  };
});
