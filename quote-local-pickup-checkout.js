/**
 * quote-local-pickup-checkout.js
 *
 * Injects the "local pickup" checkbox into the placeholder rendered by
 * ppqlp_quote_button_extras(). Runs when Cart to Quote fires its
 * `custom_quote_button_rendered` event, and is idempotent (it will not add a
 * second checkbox if one is already present).
 */
(function ($) {
  'use strict';

  $(window).on('custom_quote_button_rendered', function () {
    const outerContainer = $('[data-quote-local-pickup]');
    if (outerContainer.length === 0 || outerContainer.find('input').length > 0) {
      return;
    }

    const params = outerContainer.data('quote-local-pickup');

    const checkbox = $('<input type="checkbox" value="yes" />')
      .attr('id', params.fieldName)
      .attr('name', params.fieldName);

    const label = $('<label></label>')
      .attr('for', params.fieldName)
      .text(params.labelText);

    outerContainer.append(checkbox).append(label);
  });
})(jQuery);
