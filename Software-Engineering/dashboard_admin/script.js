const sidebar = document.getElementById("sidebar");
const menuBtn = document.getElementById("menu-btn");
const menuIcon = menuBtn.querySelector("i");

menuBtn.addEventListener("click", () => {

    sidebar.classList.toggle("hide");

    if (sidebar.classList.contains("hide")) {

        menuIcon.classList.remove("fa-angle-left");
        menuIcon.classList.add("fa-bars");
    } else {

        menuIcon.classList.remove("fa-bars");
        menuIcon.classList.add("fa-angle-left");
    }

});

const menuItems = document.querySelectorAll(".menu li");

menuItems.forEach(item => {
    item.addEventListener("click", () => {

        menuItems.forEach(i => i.classList.remove("active"));

        item.classList.add("active");
    });
});

const cards = document.querySelectorAll(".card");

cards.forEach(card => {
    card.addEventListener("mouseenter", () => {
        card.style.transform = "translateY(-8px) scale(1.02)";
    });
    card.addEventListener("mouseleave", () => {
        card.style.transform = "";
    });
});

window.addEventListener("load", () => {
    const panels = document.querySelectorAll(".panel, .card, .announcement");

    panels.forEach((panel, index) => {
        panel.style.opacity = "0";
        panel.style.transform = "translateY(20px)";

        setTimeout(() => {
            panel.style.transition = ".5s ease";
            panel.style.opacity = "1";
            panel.style.transform = "translateY(0)";
        }, index * 120);
    });
});

const buttons = document.querySelectorAll("button");

buttons.forEach(button => {
    button.addEventListener("click", function(e) {
        const circle = document.createElement("span");

        const diameter = Math.max(this.clientWidth, this.clientHeight);

        const radius = diameter / 2;

        circle.style.width = circle.style.height = `${diameter}px`;

        circle.style.left = `${e.clientX - this.offsetLeft - radius}px`;

        circle.style.top = `${e.clientY - this.offsetTop - radius}px`;

        circle.classList.add("ripple");

        const ripple = this.getElementsByClassName("ripple")[0];

        if (ripple) {
            ripple.remove();
        }
        this.appendChild(circle);
    });
});

const hour = new Date().getHours();

let greeting = "Welcome";

if (hour < 12) {
    greeting = "Good Morning";
}
else if (hour < 18) {
    greeting = "Good Afternoon";
}
else {
    greeting = "Good Evening";
}

const welcomeText = document.querySelector(".page-title p");
if (welcomeText) {
    welcomeText.textContent = `${greeting}, Juan Dela Cruz!`;
}