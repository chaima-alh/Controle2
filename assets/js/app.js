document.addEventListener('DOMContentLoaded', () => {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach((el) => {
        setTimeout(() => {
            const btn = el.querySelector('.btn-close');
            if (btn) btn.click();
        }, 5000);
    });
});
