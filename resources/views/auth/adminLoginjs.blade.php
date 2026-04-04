<script>
   document.querySelector('.admin-login-toggle-pw')?.addEventListener('click', function () {
       const input = document.getElementById('admin-password');
       const icon = this.querySelector('i');
       if (!input || !icon) return;
       if (input.type === 'password') {
           input.type = 'text';
           icon.classList.remove('fa-eye');
           icon.classList.add('fa-eye-slash');
           this.setAttribute('aria-label', 'Hide password');
       } else {
           input.type = 'password';
           icon.classList.remove('fa-eye-slash');
           icon.classList.add('fa-eye');
           this.setAttribute('aria-label', 'Show password');
       }
   });
</script>