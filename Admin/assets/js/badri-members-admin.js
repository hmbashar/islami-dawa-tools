(function ($) {
    'use strict';

    function toggleAdminCustomAmount() {
        var $select = $('[data-badri-admin-amount-select]');
        var $wrap = $('[data-badri-admin-custom-amount-wrap]');
        var $input = $wrap.find('input');
        var isOther = $select.val() === 'other';

        $wrap.prop('hidden', !isOther).toggleClass('is-visible', isOther);
        $input.prop('required', isOther);

        if (!isOther) {
            $input.val('');
        }
    }

    $(document).on('change', '[data-badri-admin-amount-select]', toggleAdminCustomAmount);
    $(toggleAdminCustomAmount);
})(jQuery);
