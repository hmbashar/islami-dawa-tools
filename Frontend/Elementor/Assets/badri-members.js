(function ($) {
    'use strict';

    function getMessage(key, fallback) {
        if (window.islamiDawaBadriMembers && window.islamiDawaBadriMembers.i18n && window.islamiDawaBadriMembers.i18n[key]) {
            return window.islamiDawaBadriMembers.i18n[key];
        }
        return fallback;
    }

    function showAlert(type, title, text) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: title,
                text: text,
                confirmButtonText: getMessage('ok', 'OK'),
                confirmButtonColor: '#0f6b3f',
                customClass: {
                    popup: 'at-badri-swal-popup'
                }
            });
            return;
        }
        window.alert(text);
    }

    $(document).on('submit', '.at-badri-form[data-badri-ajax="1"]', function (event) {
        event.preventDefault();

        var form = this;
        var $form = $(form);
        var $button = $form.find('.at-badri-submit');
        var originalButtonText = $button.text();
        var formData = new FormData(form);

        formData.set('action', 'islami_dawa_badri_member_submit_ajax');

        $button.prop('disabled', true).addClass('is-loading');
        $button.find('span').text(getMessage('processing', 'Processing...'));

        $.ajax({
            url: window.islamiDawaBadriMembers ? window.islamiDawaBadriMembers.ajaxUrl : '',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response && response.success) {
                    form.reset();
                    showAlert('success', getMessage('success', 'Success'), response.data && response.data.message ? response.data.message : getMessage('success', 'Submitted successfully.'));
                    return;
                }

                showAlert('error', getMessage('error', 'Error'), response && response.data && response.data.message ? response.data.message : getMessage('error', 'Something went wrong.'));
            },
            error: function () {
                showAlert('error', getMessage('error', 'Error'), getMessage('error', 'Something went wrong.'));
            },
            complete: function () {
                $button.prop('disabled', false).removeClass('is-loading');
                $button.find('span').text(originalButtonText);
            }
        });
    });
})(jQuery);
