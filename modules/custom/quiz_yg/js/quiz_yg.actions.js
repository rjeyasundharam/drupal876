/**
 * @file
 * Paragraphs actions JS code for quiz_yg actions button.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Process paragraph_actions elements.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches quiz_ygActions behaviors.
   */
  Drupal.behaviors.quiz_ygActions = {
    attach: function (context, settings) {
      var $actionsElement = $(context).find('.quiz_yg-dropdown').once('quiz_yg-dropdown');
      // Attach event handlers to toggle button.
      $actionsElement.each(function () {
        var $this = $(this);
        var $toggle = $this.find('.quiz_yg-dropdown-toggle');

        $toggle.on('click', function (e) {
          e.preventDefault();
          $this.toggleClass('open');
        });

        $toggle.on('focusout', function (e) {
          $this.removeClass('open');
        });
      });
    }
  };

})(jQuery, Drupal);
