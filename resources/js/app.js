import "./bootstrap";
import "./../../vendor/power-components/livewire-powergrid/dist/powergrid";
import "./../../vendor/power-components/livewire-powergrid/dist/tailwind.css";

import.meta.glob(["../(images|files|image_templates)/**"]);

function initHeroLqip(scope = document) {
    const heroes = scope.querySelectorAll(".hfm-hero");
    heroes.forEach((hero) => {
        if (hero.dataset.lqipInitialized === "1") {
            return;
        }
        const full = hero.querySelector(".hfm-hero__img--full");
        const placeholder = hero.querySelector(".hfm-hero__img--placeholder");
        if (!full || !placeholder) {
            return;
        }
        hero.dataset.lqipInitialized = "1";
        const fullSrc = full.getAttribute("data-src");
        if (!fullSrc) {
            return;
        }
        const loader = new Image();
        loader.decoding = "async";
        loader.onload = () => {
            full.src = fullSrc;
            // Wait a microtask to ensure DOM applies src
            requestAnimationFrame(() => {
                hero.classList.add("is-loaded");
            });
        };
        loader.src = fullSrc;
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => initHeroLqip());
} else {
    initHeroLqip();
}

document.addEventListener("livewire:navigated", () => {
    initHeroLqip();
    const hash = window.location.hash;
    if (hash) {
        const el = document.querySelector(hash);
        if (el) {
            setTimeout(() => {
                el.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
            }, 100);
        }
    }
});
