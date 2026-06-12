<script>
    (function($) {
        'use strict';

        function confirmLogout($form) {
            var message = 'Are you sure you want to logout?';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Log out',
                    text: message,
                    showCancelButton: true,
                    confirmButtonText: 'Yes, log out',
                    cancelButtonText: 'Cancel',
                    focusCancel: true,
                }).then(function(result) {
                    if (result.isConfirmed) {
                        $form.off('submit.sappcLogoutConfirm');
                        if ($form[0]) {
                            $form[0].submit();
                        }
                    }
                });
                return;
            }

            if (window.confirm(message)) {
                $form.off('submit.sappcLogoutConfirm');
                if ($form[0]) {
                    $form[0].submit();
                }
            }
        }

        $(function() {
            $(document).on('submit.sappcLogoutConfirm', '.sappc-topbar_logout-form', function(e) {
                e.preventDefault();
                confirmLogout($(this));
            });
        });
    })(jQuery);
</script>
