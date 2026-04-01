import "./bootstrap";

function hasUnsavedAdminSettingsChanges() {
    if (!window.location.pathname.startsWith("/admin/einstellungen")) {
        return false;
    }

    const root = document.querySelector("[data-admin-settings-root]");
    if (!root) {
        return false;
    }

    return root.querySelector("[data-admin-settings-save-class-button]:not([disabled])") !== null;
}

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

window.addEventListener("beforeunload", (event) => {
    if (!hasUnsavedAdminSettingsChanges()) {
        return;
    }

    event.preventDefault();
    event.returnValue = "";
});

document.addEventListener("livewire:navigate", (event) => {
    if (!hasUnsavedAdminSettingsChanges()) {
        return;
    }

    const shouldLeave = window.confirm("Du hast ungespeicherte Änderungen. Seite trotzdem verlassen?");
    if (!shouldLeave) {
        event.preventDefault();
    }
});
