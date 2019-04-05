(function($) {
  $(document).ready(function() {
    $('.js-select2').select2({
      placeholder: 'Tryk her for at vælge kategorier'
    });
    $('.js-date-popup').datepicker({
      dateFormat: "dd-mm-yy",
      altField: "input[data-drupal-selector=edit-created]",
      altFormat: "yy/mm/dd 23:59:59"
    });
  });
})(jQuery);
