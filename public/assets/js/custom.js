//document.ready
$(document).ready(function () {
    /*========================= DELETE ==============================*/
    $('.delete').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');
        swal({
            title: 'Are you sure?',
            text: 'Once deleted, you will not be able to revert back!',
            icon: 'error',
            buttons: {
                cancel: 'No, Cancel!',
                confirm: { text: 'Yes, delete it!', className: 'btn-danger' },
            },
        }).then(function (value) {
            if (value) {
                window.location.href = url;
            }
        });
    });

});