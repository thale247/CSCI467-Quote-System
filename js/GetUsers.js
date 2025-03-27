function getData() {
    let formData = new FormData(document.getElementById("get_users_form"));

    fetch("/php/GetNames.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("response").innerHTML = data;
    })
    .catch(error => console.error('Error:', error));
}
