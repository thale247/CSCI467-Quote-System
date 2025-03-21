function sendData() {
    let formData = new FormData(document.getElementById("send_quote_form"));

    fetch("SendPurchaseOrder.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        document.getElementById("response").innerHTML = data;
    })
    .catch(error => console.error('Error:', error));
}
