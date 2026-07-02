document.addEventListener("click", (e) => {
    if (e.target.classList.contains("delete")) {
        e.preventDefault();
        alert("Clique!");
    }
});