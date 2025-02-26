//document.ready
$(document).ready(function () {
    $('.cancel').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');
        swal({
            title: 'Cancel Mandate',
            text: 'Once cancel it, you will not be able to revert back.',
            icon: 'error',
            buttons: {
                cancel: 'No',
                confirm: { text: 'Yes, Cancel', className: 'btn-danger' },
            },
        }).then(function (value) {
            if (value) {
                window.location.href = url;
            }
        });
    });

    $('.archive').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');
        swal({
            title: 'Archive Mandate',
            text: 'Are you sure you want to archive mandate?',
            icon: 'warning',
            buttons: {
                cancel: 'No',
                confirm: { text: 'Yes, Archive', className: 'btn-success' },
            },
        }).then(function (value) {
            if (value) {
                window.location.href = url;
            }
        });
    });


    $('.delete').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');
        swal({
            title: 'Delete Mandate',
            text: 'Once deleted, you will not be able to revert back.',
            icon: 'error',
            buttons: {
                cancel: 'No',
                confirm: { text: 'Yes, Delete', className: 'btn-danger' },
            },
        }).then(function (value) {
            if (value) {
                window.location.href = url;
            }
        });
    });

});