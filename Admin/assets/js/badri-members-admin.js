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



    function activateBadriTab($button) {
        var target = $button.data('badri-tab');
        var $shell = $button.closest('[data-badri-tabs]');

        $shell.find('[data-badri-tab]').removeClass('is-active');
        $button.addClass('is-active');
        $shell.toggleClass('is-dashboard-active', target === 'dashboard');

        $shell.find('[data-badri-panel]').each(function () {
            var isActive = $(this).data('badri-panel') === target;
            $(this).prop('hidden', !isActive).toggleClass('is-active', isActive);
        });
    }

    $(document).on('click', '[data-badri-tab]', function (event) {
        event.preventDefault();
        activateBadriTab($(this));
    });

    $(document).on('click', '[data-badri-go-tab]', function (event) {
        event.preventDefault();
        var target = $(this).data('badri-go-tab');
        var $shell = $(this).closest('[data-badri-tabs]');
        var $button = $shell.find('[data-badri-tab="' + target + '"]');
        if ($button.length) {
            activateBadriTab($button);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    $(function () {
        toggleAdminCustomAmount();
        refreshAllBuilderRows();
        $('[data-badri-tabs]').each(function () {
            $(this).toggleClass('is-dashboard-active', $(this).find('[data-badri-tab].is-active').data('badri-tab') === 'dashboard');
        });
    });
})(jQuery);
