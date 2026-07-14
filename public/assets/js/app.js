$(document).ready(function() {
    // Global AJAX setup if needed
    $.ajaxSetup({
        headers: {
            // CSRF token in the future
        }
    });

    // Handle standard generic notifications
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    window.notify = function(type, message) {
        Toast.fire({
            icon: type,
            title: message
        });
    }
});
