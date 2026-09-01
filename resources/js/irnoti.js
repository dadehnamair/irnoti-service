document.addEventListener("DOMContentLoaded", () => {
    const menuToggle = document.querySelector(".menu-toggle");
    const year = document.getElementById("year");
    const toggleButtons = document.querySelectorAll(".toggle-btn");
    const pricingPrices = document.querySelectorAll(".price, .price-compare");
    const periods = document.querySelectorAll(".period");

    if (year) {
        year.textContent = new Date().getFullYear();
    }

    /* Mobile navigation — styling lives in irnoti.css under body.nav--open */
    if (menuToggle) {
        menuToggle.addEventListener("click", () => {
            const open = document.body.classList.toggle("nav--open");
            menuToggle.setAttribute("aria-expanded", String(open));
        });

        document
            .querySelectorAll(".main-nav a, .nav-actions a")
            .forEach((link) => {
                link.addEventListener("click", () => {
                    document.body.classList.remove("nav--open");
                    menuToggle.setAttribute("aria-expanded", "false");
                });
            });
    }

    /* Pricing period toggle */
    toggleButtons.forEach((button) => {
        button.addEventListener("click", () => {
            toggleButtons.forEach((item) => {
                item.classList.remove("active");
                item.setAttribute("aria-pressed", "false");
            });
            button.classList.add("active");
            button.setAttribute("aria-pressed", "true");

            const period = button.dataset.period;

            pricingPrices.forEach((priceEl) => {
                const value = priceEl.dataset[period];
                if (value) {
                    priceEl.textContent = Number(value).toLocaleString("fa-IR");
                }
            });

            periods.forEach((periodEl) => {
                periodEl.textContent = period === "monthly" ? "/ماه" : "/سال";
            });
        });
    });
});
