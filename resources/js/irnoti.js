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

    /* Dedicated lines page — prefix tabs + digit/type/rond filters */
    const lineCards = document.getElementById("line-cards");
    if (lineCards) {
        const cards = Array.from(lineCards.querySelectorAll(".line-card"));
        const tabs = Array.from(document.querySelectorAll(".line-tab"));
        const digitsSel = document.getElementById("filter-digits");
        const typeSel = document.getElementById("filter-type");
        const rondChk = document.getElementById("filter-rond");
        const emptyState = document.getElementById("line-empty-state");
        const state = { prefix: "all" };

        const applyFilters = () => {
            const digits = digitsSel ? digitsSel.value : "all";
            const type = typeSel ? typeSel.value : "all";
            const rondOnly = rondChk ? rondChk.checked : false;
            let visible = 0;

            cards.forEach((card) => {
                const okPrefix =
                    state.prefix === "all" ||
                    card.dataset.prefix === state.prefix;
                const okDigits = digits === "all" || card.dataset.digits === digits;
                const okType = type === "all" || card.dataset.type === type;
                const okRond = !rondOnly || card.dataset.rond === "1";
                const show = okPrefix && okDigits && okType && okRond;
                card.hidden = !show;
                if (show) visible += 1;
            });

            if (emptyState) emptyState.hidden = visible !== 0;
        };

        tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                tabs.forEach((item) => {
                    item.classList.remove("active");
                    item.setAttribute("aria-selected", "false");
                });
                tab.classList.add("active");
                tab.setAttribute("aria-selected", "true");
                state.prefix = tab.dataset.prefix;
                applyFilters();
            });
        });

        [digitsSel, typeSel].forEach((el) => el && el.addEventListener("change", applyFilters));
        if (rondChk) rondChk.addEventListener("change", applyFilters);
    }
});
