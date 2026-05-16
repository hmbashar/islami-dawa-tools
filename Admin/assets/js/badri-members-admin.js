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

    function toggleBuilderOptions($row) {
        var type = $row.find('[data-badri-builder-type]').val();
        $row.find('.idt-badri-builder-options').toggle(type === 'select');
    }

    function refreshAllBuilderRows() {
        $('[data-badri-field-row]').each(function () {
            toggleBuilderOptions($(this));
        });
    }

    $(document).on('change', '[data-badri-admin-amount-select]', toggleAdminCustomAmount);

    $(document).on('change', '[data-badri-builder-type]', function () {
        toggleBuilderOptions($(this).closest('[data-badri-field-row]'));
    });

    $(document).on('click', '[data-badri-add-field]', function (event) {
        event.preventDefault();

        var $builder = $(this).closest('[data-badri-field-builder]');
        var $list = $builder.find('[data-badri-field-builder-list]');
        var template = $('#tmpl-idt-badri-field-row').html();
        var index = Date.now();

        if (!template) {
            return;
        }

        template = template.replace(/__INDEX__/g, index);
        $list.append(template);
        refreshAllBuilderRows();
    });

    $(document).on('click', '[data-badri-remove-field]', function (event) {
        event.preventDefault();
        $(this).closest('[data-badri-field-row]').remove();
    });

    $(function () {
        toggleAdminCustomAmount();
        refreshAllBuilderRows();
    });
})(jQuery);
