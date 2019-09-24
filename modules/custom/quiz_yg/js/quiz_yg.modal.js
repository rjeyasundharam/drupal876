/**
 * @file quiz_yg.modal.js
 *
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Click handler for click "Add" button between quiz_yg.
   *
   * @type {Object}
   */
  Drupal.behaviors.quiz_ygModalAdd = {
    attach: function (context) {
      $('.paragraph-type-add-modal-button', context)
        .once('add-click-handler')
        .on('click', function (event) {
          var $button = $(this);
          var $add_more_wrapper = $button.parent().siblings('.quiz_yg-add-dialog');
          Drupal.quiz_ygAddModal.openDialog($add_more_wrapper, $button.val());

          // Stop default execution of click event.
          event.preventDefault();
          event.stopPropagation();
        });
    }
  };

  /**
   * Namespace for modal related javascript methods.
   *
   * @type {Object}
   */
  Drupal.quiz_ygAddModal = {};

  /**
   * Open modal dialog for adding new paragraph in list.
   *
   * @param {Object} $context
   *   jQuery element of form wrapper used to submit request for adding new
   *   paragraph to list. Wrapper also contains dialog template.
   * @param {string} title
   *   The title of the modal form window.
   */
  Drupal.quiz_ygAddModal.openDialog = function ($context, title) {

    $context.dialog({
      modal: true,
      resizable: false,
      title: title,
      width: 'auto',
      close: function () {
        var $dialog = $(this);

        // Destroy dialog object.
        $dialog.dialog('destroy');
        // Reset delta after destroying the dialog object.
        var $delta = $dialog.closest('.clearfix')
          .find('.paragraph-type-add-modal-delta');
        $delta.val('');
      }
    });

    // Close the dialog after a button was clicked.
    $('.field-add-more-submit', $context)
      .each(function () {
      // Use mousedown event, because we are using ajax in the modal add mode
      // which explicitly suppresses the click event.
      $(this).on('mousedown', function () {
        var $this = $(this);
        $this.closest('div.ui-dialog-content').dialog('close');
      });
    });
  };

}(jQuery, Drupal, drupalSettings));
