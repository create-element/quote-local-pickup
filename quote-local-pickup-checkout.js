/**
 * quote-local-pickup-checkout.js
 */
(function ($) {
  'use strict';

  $(window).on('custom_quote_button_rendered', (event) => {
    console.log('Rendered');

    const outerContainer = $('[data-quote-local-pickup]');
    let checkbox = $(outerContainer).find('input');

    if (outerContainer.length > 0 && checkbox.length === 0) {
      const params = $(outerContainer).data('quote-local-pickup');

      checkbox = $('<input type="checkbox" name="quote_local_pickup" id="quote_local_pickup" />');
      $(checkbox).attr('id', params.fieldName);
      $(checkbox).attr('name', params.fieldName);
      outerContainer.append(checkbox);

      const labelElement = $('<label for="quote_local_pickup"></label>');
      $(checkbox).attr('for', params.fieldName);

      labelElement.text(params.labelText);
      outerContainer.append(labelElement);
    }
  });
})(jQuery);
