/* MarMal Effects – stránka nastavení barev */
jQuery(function ($) {
  'use strict';
  var $table = $('.mm-s-table');

  function mode() {
    return $('input[name="mm_effects_settings[mode]"]:checked').val() || 'breakdance';
  }

  function update($row) {
    var own = ($row.find('.mm-s-color').val() || '').trim();
    var fallback = own || $row.data('default');
    var source = $row.find('.mm-s-source').val();
    var $cv = $row.find('.mm-s-customvar');
    $cv.toggle(source === 'custom-var');
    if (source === 'custom-var') source = ($cv.val() || '').trim();

    var expr = fallback;
    if (mode() === 'breakdance' && /^--[\w-]+$/.test(source)) expr = 'var(' + source + ', ' + fallback + ')';

    var sw = $row.find('.mm-s-swatch')[0];
    sw.style.background = '';
    sw.style.background = expr;
    var shown = getComputedStyle(sw).backgroundColor;
    var fromBd = mode() === 'breakdance' && expr.indexOf('var(') === 0;
    $row.find('.mm-s-value').text(shown + (fromBd ? '' : (own ? '' : ' (výchozí)')));
  }

  function updateAll() {
    $table.attr('data-mode', mode());
    $('.mm-s').attr('data-mode', mode());
    $('.mm-s-row').each(function () { update($(this)); });
  }

  $('.mm-s-color').wpColorPicker({
    change: function (e, ui) {
      var $row = $(e.target).closest('.mm-s-row');
      $(e.target).val(ui.color.toString());
      update($row);
    },
    clear: function () { setTimeout(updateAll, 0); }
  });

  $(document).on('change input', '.mm-s-source, .mm-s-customvar', function () { update($(this).closest('.mm-s-row')); });
  $(document).on('change', 'input[name="mm_effects_settings[mode]"]', updateAll);
  updateAll();
});
