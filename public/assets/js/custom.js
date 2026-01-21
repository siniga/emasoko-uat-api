//document.ready
$(document).ready(function () {
    $('.cancel').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');

        Swal.fire({
            title: 'Cancel Item',
            html: "Are sure you want to cancel item?",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#F44336',
            reverseButtons: true,
            confirmButtonText: 'Yes, Cancel',
            cancelButtonText: 'No, Do not Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    //archive
    $('.archive').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');

        Swal.fire({
            title: 'Archive Item',
            html: "Are sure you want to archive item?",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#F44336',
            reverseButtons: true,
            confirmButtonText: 'Yes, Archive',
            cancelButtonText: 'No, Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    //delete
    $('.delete').on('click', function (event) {
        event.preventDefault();
        const url = $(this).attr('href');

        Swal.fire({
            title: 'Delete Item',
            html: "Are sure you want to delete item?",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#4CAF50',
            cancelButtonColor: '#F44336',
            reverseButtons: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'No, Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
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