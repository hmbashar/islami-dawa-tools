(function () {
    'use strict';

    function getMessage(type, fallback) {
        if (window.IslamiDawaBadriMembers && window.IslamiDawaBadriMembers.messages && window.IslamiDawaBadriMembers.messages[type]) {
            return window.IslamiDawaBadriMembers.messages[type];
        }

        return fallback;
    }

    function showAlert(icon, title, text) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'ঠিক আছে'
            });
            return;
        }

        window.alert(text || title);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('[data-badri-ajax-form="1"]');

        forms.forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();

                var submitButton = form.querySelector('.at-badri-submit');
                var originalText = submitButton ? submitButton.textContent : '';
                var formData = new FormData(form);

                formData.set('action', 'islami_dawa_badri_member_submit_ajax');

                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.classList.add('at-badri-submit-loading');
                    submitButton.textContent = getMessage('processing', 'তথ্য জমা হচ্ছে...');
                }

                fetch(window.IslamiDawaBadriMembers.ajaxUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.success) {
                            form.reset();
                            showAlert(
                                'success',
                                'সফল',
                                data.data && data.data.message ? data.data.message : getMessage('success', 'আপনার তথ্য সফলভাবে জমা হয়েছে।')
                            );
                            return;
                        }

                        showAlert(
                            'error',
                            'দুঃখিত',
                            data && data.data && data.data.message ? data.data.message : getMessage('error', 'দুঃখিত, তথ্য জমা দেওয়া যায়নি।')
                        );
                    })
                    .catch(function () {
                        showAlert('error', 'দুঃখিত', getMessage('error', 'দুঃখিত, তথ্য জমা দেওয়া যায়নি।'));
                    })
                    .finally(function () {
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.classList.remove('at-badri-submit-loading');
                            submitButton.textContent = originalText;
                        }
                    });
            });
        });
    });
}());
