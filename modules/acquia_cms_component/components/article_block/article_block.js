
/**
 * @file
 * Contains article block component code.
 */

(function ($) {
  Drupal.behaviors.articleBlockComponent = {
    attach: function(context, settings) {
      $('#article-block-component', context).once('article-block-component').each(function() {
        // Add loader span.
        $('#article-block-component').html('<div class="loader"></div>');
        // Map select option with component configuration form select.
        let selectOption = ['5', '10', '20', '30', '50', 'All'];
        let data_display_item = $('.article_block').attr('data-display-item');
        let item_to_display = selectOption[data_display_item];
        let endpoint = '/jsonapi/node/article';
        if (item_to_display && item_to_display !=='All') {
          endpoint = '/jsonapi/node/article?page[offset]=0&page[limit]=' + item_to_display;
        }
        $.get( endpoint, function( response ) {
          let data = response.data;
          if (data.length > 0) {
            let ul = $('<ul/>');
            $.each(data, function (index, value) {
              let li = $('<li/>');
              let anchor = $('<a/>');
              anchor.attr('href', value.attributes.path.alias);
              anchor.text(value.attributes.title);
              li.append(anchor);
              ul.append(li);
            });
            $('#article-block-component').html(ul);
          }
        });
      }).fail(function () {
        $('#article-block-component').html('<span>Something went wrong!</span>');
      });
    },
  };
})(jQuery);
