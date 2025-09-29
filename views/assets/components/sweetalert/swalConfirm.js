async function swalConfirm(message, title = "Conferma", icon = "question", confirmButtonText = "Si", cancelButtonText = "No") {
    return new Promise((resolve, reject) => {
        Swal.fire({
            title: title,
            html: message,
            icon: icon,
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            confirmButtonColor: "#25b9d7"
        }).then(result => {
            if (result.isConfirmed) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    });
}
