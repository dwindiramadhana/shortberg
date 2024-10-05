document.addEventListener("DOMContentLoaded", function() {
    const buttons = document.querySelectorAll(".copy-button");
    buttons.forEach(button => {
        button.addEventListener("click", function() {
            const text = this.getAttribute("data-clipboard-text");
            navigator.clipboard.writeText(text).then(function() {
                alert("Copied: " + text);
            }, function(err) {
                console.error("Could not copy text: ", err);
            });
        });
    });
});