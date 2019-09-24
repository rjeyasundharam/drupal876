(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.jsXray = {
    attach: function (context, settings) {
      $('.xray-preview').click(function() {
        $('#edit-site').attr('disabled', 'disabled');
        $('.xray-preview').attr('disabled', 'disabled');
        $('.loading').css('display', 'block');
        $('ul.module-list').empty();
        $('div.version').empty();
        version();
        module_list(0);
        return false;
      });

      function version() {
        $.ajax({
            url: drupalSettings.path.baseUrl + 'xray_version',
            type: "POST",
            dataType: "json",
            data: {
              siteurl: $('#edit-site').val(),
            },
            success: function(response) {
                $('div.version').append(response.data);
            }
          });
      }
      function module_list(count) {

        $.ajax({
            url: drupalSettings.path.baseUrl + 'x-ray',
            type: "POST",
            dataType: "json",
            data: {
              siteurl: $('#edit-site').val(),
              email: $('#edit-email').val(),
              count: count
            },
            success: function(response) {
              if(response.flag == 0) {
                $('ul.module-list').append(response.data);
                console.log(response);
                module_list(response.count);
              }
              else {
                $('.loading').css('display', 'none');
                $('#edit-site').removeAttr('disabled');
                $('.xray-preview').removeAttr('disabled');
              }
            }
          });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
