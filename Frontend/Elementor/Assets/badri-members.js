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
                buttonsStyling: true,
                customClass: {
                    popup: 'at-badri-swal-popup',
                    title: 'at-badri-swal-title',
                    htmlContainer: 'at-badri-swal-text'
                }
            });
            return;
        }
        window.alert((title ? title + '\n\n' : '') + text);
    }

    function requiredMessage(fieldName) {
        return getMessage('requiredMessage', 'Please fill in {field}.').replace('{field}', fieldName);
    }

    function getFieldLabel($field) {
        var label = $field.data('badri-label');
        if (label) {
            return label;
        }

        var id = $field.attr('id');
        if (id) {
            var $label = $('label[for="' + id + '"]').first();
            if ($label.length) {
                return $.trim($label.clone().children().remove().end().text());
            }
        }

        return $field.attr('name') || 'field';
    }

    function markField($field, hasError) {
        var $wrap = $field.closest('.at-badri-field');
        $wrap.toggleClass('has-error', !!hasError);
    }

    function validateForm(form) {
        var $form = $(form);
        var invalidMessage = '';
        var invalidField = null;

        $form.find('.has-error').removeClass('has-error');

        $form.find('[required]').each(function () {
            var field = this;
            var $field = $(field);
            var type = ($field.attr('type') || '').toLowerCase();
            var name = $field.attr('name');

            if (!$field.is(':visible') && type !== 'radio' && type !== 'checkbox') {
                return true;
            }

            if ((type === 'radio' || type === 'checkbox') && name) {
                if (!$form.find('[name="' + name + '"]:checked').length) {
                    invalidMessage = requiredMessage(getFieldLabel($field));
                    invalidField = field;
                    markField($field, true);
                    return false;
                }
                return true;
            }

            if (!$.trim($field.val())) {
                invalidMessage = requiredMessage(getFieldLabel($field));
                invalidField = field;
                markField($field, true);
                return false;
            }

            if (type === 'email' && field.validity && !field.validity.valid) {
                invalidMessage = requiredMessage(getFieldLabel($field));
                invalidField = field;
                markField($field, true);
                return false;
            }

            return true;
        });

        var $amountSelect = $form.find('[data-badri-amount-select]');
        var $customAmount = $form.find('[data-badri-custom-amount]');
        if (!invalidMessage && $amountSelect.val() === 'other' && !$.trim($customAmount.val())) {
            invalidMessage = getMessage('customAmountError', 'Please enter custom amount.');
            invalidField = $customAmount.get(0);
            markField($customAmount, true);
        }

        var $photo = $form.find('input[name="member_photo"]');
        if (!invalidMessage && $photo.length && $photo.get(0).files && $photo.get(0).files.length) {
            var file = $photo.get(0).files[0];
            var allowed = ['image/jpeg', 'image/png', 'image/webp'];
            var maxSizeMb = parseInt($photo.data('badri-max-size-mb'), 10) || 2;
            var maxSize = maxSizeMb * 1024 * 1024;

            if (allowed.indexOf(file.type) === -1) {
                invalidMessage = getMessage('photoTypeError', 'Please upload JPG, PNG or WEBP image.');
                invalidField = $photo.get(0);
                markField($photo, true);
            } else if (file.size > maxSize) {
                invalidMessage = getMessage('photoSizeError', 'Photo size can be maximum {size}MB.').replace('{size}', maxSizeMb);
                invalidField = $photo.get(0);
                markField($photo, true);
            }
        }

        if (invalidMessage) {
            showAlert('error', getMessage('validationTitle', 'Required information'), invalidMessage);
            if (invalidField && typeof invalidField.focus === 'function') {
                setTimeout(function () {
                    invalidField.focus();
                }, 250);
            }
            return false;
        }

        return true;
    }

    function toggleCustomAmount(context) {
        var $context = context ? $(context) : $(document);
        $context.find('[data-badri-amount-select]').each(function () {
            var $select = $(this);
            var $form = $select.closest('form');
            var $wrap = $form.find('[data-badri-custom-amount-wrap]');
            var $input = $form.find('[data-badri-custom-amount]');
            var isOther = $select.val() === 'other';

            $wrap.prop('hidden', !isOther).toggleClass('is-visible', isOther);
            $input.prop('required', isOther);

            if (!isOther) {
                $input.val('');
                markField($input, false);
            }
        });
    }

    $(document).on('change input', '.at-badri-field input, .at-badri-field select, .at-badri-field textarea', function () {
        markField($(this), false);
    });

    $(document).on('change', '[data-badri-amount-select]', function () {
        toggleCustomAmount($(this).closest('form'));
    });

    $(function () {
        toggleCustomAmount(document);
    });

    $(document).on('submit', '.at-badri-form[data-badri-ajax="1"]', function (event) {
        event.preventDefault();

        var form = this;
        var $form = $(form);

        if (!validateForm(form)) {
            return;
        }

        var $button = $form.find('.at-badri-submit');
        var originalButtonText = $button.find('span').text() || $button.text();
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
                    toggleCustomAmount(form);
                    showAlert(
                        'success',
                        getMessage('successTitle', 'Success'),
                        response.data && response.data.message ? response.data.message : getMessage('success', 'Submitted successfully.')
                    );
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
