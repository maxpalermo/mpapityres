document.addEventListener("DOMContentLoaded", () => {
    const content = document.getElementById("content");
    if (content) {
        content.classList.remove("nobootstrap");
        content.classList.add("bootstrap");
    }
});
