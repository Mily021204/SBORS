const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", () => {
    const type =
        password.getAttribute("type") === "password"
            ? "text"
            : "password";

    password.setAttribute("type", type);

    togglePassword.innerHTML =
        type === "password"
            ? '<i class="fa-solid fa-eye"></i>'
            : '<i class="fa-solid fa-eye-slash"></i>';
});