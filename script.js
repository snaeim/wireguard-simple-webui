function downloadOnClick(filename, content) {

    let config = content;
    let contentAsBlob = new Blob([content], {
        type: 'text/plain'
    });

    var downloadLink = document.createElement("a");
    downloadLink.download = filename;
    downloadLink.innerHTML = "Download File";
    if (window.webkitURL != null) {
        // Chrome allows the link to be clicked without actually adding it to the DOM.
        downloadLink.href = window.webkitURL.createObjectURL(contentAsBlob);
    } else {
        // Firefox requires the link to be added to the DOM before it can be clicked.
        downloadLink.href = window.URL.createObjectURL(contentAsBlob);
        downloadLink.onclick = destroyClickedElement;
        downloadLink.style.display = "none";
        document.body.appendChild(downloadLink);
    }

    downloadLink.click();

}

document.querySelector("#config-save").addEventListener("click", function(e) {

    e.preventDefault();
    let config = document.querySelector("#result").value;
    downloadOnClick('VPN.conf', config);

});

document.querySelector("#config-qrcode").addEventListener("click", function(e) {

    e.preventDefault();

    let config = document.querySelector("#result").value;
    let configEncoded = encodeURIComponent(config);

    var url = "https://chart.googleapis.com/chart?cht=qr&chs=500x500&chl=" + configEncoded + "&choe=UTF-8";

    let modal = document.querySelector("#modal-qrcode");
    let modalImage = modal.querySelector("img");

    modalImage.src = url;

    modal.classList.add("is-active", "is-clipped");

});

document.querySelector("#config-copy").addEventListener("click", function(e) {

    e.preventDefault();
    let config = document.querySelector("#result");
    
    config.select();
    document.execCommand("copy");
    config.blur();
    alert("Config copied to clipboard");

});

document.querySelector(".modal-close").addEventListener("click", function(e) {

    this.closest("div.modal").classList.remove("is-active", "is-clipped");
    document.querySelector("#modal-qrcode img").src = "./loading.gif";

});

document.querySelector("#enable-address-edit").addEventListener("dblclick", (e) => {

    let inputAddress = document.querySelector("input[name=address]");
    inputAddress.removeAttribute("readonly");

});

