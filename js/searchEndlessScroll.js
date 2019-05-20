(function ($, Drupal) {
  "use strict";

  /**
   *
   * @type {{attach: Drupal.behaviors.views_infinite_scroll_automatic.attach}}
   */
  Drupal.behaviors.views_infinite_scroll_automatic = {
    attach : function(context, settings) {
      $(window).on('scroll', function() {
        // Act on scroll near bottom.
        if($(window).scrollTop() + $(window).height() > $('#infinity-next-container').offset().top + 200) {
          var infiniteScrollEnabled = $('.infinite_scroll_enabled').length;
          // If enabled
          if (infiniteScrollEnabled > 0) {
            // Disable scrolling to prevent more calls.
            $('.infinite_scroll_enabled').removeClass('infinite_scroll_enabled');

            // Get next.
            var next = $('.js-infinite-scroll').data('infinity-next');
            if (next > 0) {
              $.ajax({
                url: '/event_database_pull/occurrences_next/' + next,
                type: 'get',
                data: {},
                dataType: 'json',
                success: function(response) {
                  $('#occurrences-list').addClass('infinite_scroll_enabled');
                  $('#infinity-next-container').remove();
                  $('.occurrence-list').append(response.html);
                },
                error: function(xhr) {
                  console.log('Error');
                  console.log(xhr);
                }
              });
            }
          }
          else {
            $('#js-infinite-scroll-wrapper').addClass('infiniteScrollEnabled');
          }
        }
      });

      $('.js-more-results').click(function() {
        $('.infinite-scroll-bottom .button').hide();
        $('.spinnerHidden').removeClass('spinnerHidden');
        // Get next.
        var next = $('.js-infinite-scroll').data('infinity-next');
        $.ajax({
          url: '/event_database_pull/occurrences_next/' + next,
          type: 'get',
          data: {},
          dataType: 'json',
          success: function(response) {
            $('#occurrences-list').addClass('infinite_scroll_enabled');
            $('#infinity-next-container').remove();
            $('.occurrence-list').append(response.html);
          },
          error: function(xhr) {
            console.log('Error');
            console.log(xhr);
          }
        });
      });
    }
  };
})(jQuery, Drupal);