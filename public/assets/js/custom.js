//document.ready
$(document).ready(function () {
    $('.cancel').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');
        swal({
            title: 'Cancel',
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
            title: 'Archive',
            text: 'Are you sure you want to archive?',
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
            title: 'Delete',
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

    // Select/Deselect all checkboxes
    $('#select_all').on('click', function (event) {  //on click
        if (this.checked) { // check select status
            $('.checkbox').each(function () { //loop through each checkbox
                this.checked = true;  //select all checkboxes with class "checkbox"
            });
        } else {
            $('.checkbox').each(function () { //loop through each checkbox
                this.checked = false; //deselect all checkboxes with class "checkbox"
            });
        }
    });

});