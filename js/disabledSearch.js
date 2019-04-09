(function($) {
  function checkEmpty() {
    var isEmpty = true;

    $('.event-database-pull-search-form input[type="text"]').each(function() {
      if ($.trim($(this).val()) != '') {
        isEmpty = false;
      }
    });
    if ($('.select2-selection__choice').length > 0 ) {
      isEmpty = false;
    }
    if (isEmpty == true) {
      $('input[type="submit"]').attr('disabled','disabled');
    }
    else {
      $('input[type="submit"]').removeAttr('disabled');
    }
  }

  // Start the show
  $(document).ready(function () {
    checkEmpty();

    var select2 = $('.js-select2');

    $('.event-database-pull-search-form').keyup(function() {
      checkEmpty();
    });

    $('#ui-datepicker-div').on('click', function () {
      $('.event-database-pull-search-form').keyup();
    });

    select2.on('select2:select', function () {
      $('.event-database-pull-search-form').keyup();
    });

    select2.on('select2:unselect', function () {
      $('.event-database-pull-search-form').keyup();
    });
  });

})(jQuery);
