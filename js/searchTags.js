(function($) {
  $(document).ready(function() {
    $('.js-select2').select2({
      placeholder: 'Tryk her'
    });
    $('.js-date-popup').datepicker({
      dateFormat: "dd-mm-yy",
      altField: "input[data-drupal-selector=edit-created]",
      altFormat: "yy/mm/dd 23:59:59",
      // See disabledSearch.js
      onSelect:function(){
        $('.event-database-pull-search-form').keyup();
      }
    });
  });
})(jQuery);
