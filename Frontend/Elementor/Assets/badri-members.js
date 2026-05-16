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


    function resetUploadZone(form) {
        var $context = form ? $(form) : $(document);
        $context.find('[data-badri-upload-zone]').each(function () {
            var $zone = $(this);
            $zone.removeClass('has-file is-dragover');
            $zone.find('[data-badri-upload-preview]').removeAttr('style').html('<span>' + getMessage('uploadPreviewLabel', 'ছবি') + '</span>');
            $zone.find('[data-badri-upload-name]').text(getMessage('uploadEmptyName', 'কোনো ছবি নির্বাচন করা হয়নি'));
        });
    }

    function updateUploadZone(input) {
        var $input = $(input);
        var $zone = $input.closest('[data-badri-upload-zone]');
        var $name = $zone.find('[data-badri-upload-name]');
        var $preview = $zone.find('[data-badri-upload-preview]');
        var file = input.files && input.files.length ? input.files[0] : null;

        if (!file) {
            $zone.removeClass('has-file');
            $preview.removeAttr('style').html('<span>' + getMessage('uploadPreviewLabel', 'ছবি') + '</span>');
            $name.text(getMessage('uploadEmptyName', 'কোনো ছবি নির্বাচন করা হয়নি'));
            return;
        }

        $zone.addClass('has-file');
        $name.text(file.name);

        if (file.type && file.type.indexOf('image/') === 0 && window.FileReader) {
            var reader = new FileReader();
            reader.onload = function (event) {
                $preview.css('background-image', 'url(' + event.target.result + ')').empty();
            };
            reader.readAsDataURL(file);
        }
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

    $(document).on('click', '[data-badri-upload-trigger], [data-badri-upload-preview], .at-badri-upload-content strong', function (event) {
        event.preventDefault();
        $(this).closest('[data-badri-upload-zone]').find('input[type="file"]').trigger('click');
    });

    $(document).on('change', '.at-badri-upload-input', function () {
        updateUploadZone(this);
    });

    $(document).on('dragenter dragover', '[data-badri-upload-zone]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $(this).addClass('is-dragover');
    });

    $(document).on('dragleave dragend drop', '[data-badri-upload-zone]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $(this).removeClass('is-dragover');
    });

    $(document).on('drop', '[data-badri-upload-zone]', function (event) {
        var originalEvent = event.originalEvent;
        var files = originalEvent && originalEvent.dataTransfer ? originalEvent.dataTransfer.files : null;
        var input = $(this).find('input[type="file"]').get(0);

        if (!files || !files.length || !input) {
            return;
        }

        try {
            input.files = files;
        } catch (error) {
            if (window.DataTransfer) {
                var dataTransfer = new DataTransfer();
                dataTransfer.items.add(files[0]);
                input.files = dataTransfer.files;
            }
        }

        updateUploadZone(input);
        markField($(input), false);
    });

    $(document).on('change', '[data-badri-amount-select]', function () {
        toggleCustomAmount($(this).closest('form'));
    });

    $(function () {
        toggleCustomAmount(document);
        resetUploadZone(document);
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
                    resetUploadZone(form);
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
