import "./bootstrap";

function sanitizeImageSrc(rawValue) {
    if (typeof rawValue !== "string" || rawValue.trim() === "") {
        return null;
    }

    let url;
    try {
        url = new URL(rawValue, window.location.origin);
    } catch {
        return null;
    }

    const isAllowedProtocol = url.protocol === "http:" || url.protocol === "https:";
    if (!isAllowedProtocol) {
        return null;
    }

    if (url.origin !== window.location.origin) {
        return null;
    }

    return url.toString();
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

        const safeFullSrc = sanitizeImageSrc(fullSrc);
        if (!safeFullSrc) {
            return;
        }

        const loader = new Image();
        loader.decoding = "async";
        loader.onload = () => {
            full.src = safeFullSrc;
            // Wait a microtask to ensure DOM applies src
            requestAnimationFrame(() => {
                hero.classList.add("is-loaded");
            });
        };
        loader.src = safeFullSrc;
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
        let hashId = "";
        try {
            hashId = decodeURIComponent(hash.slice(1));
        } catch {
            hashId = "";
        }
        const element = hashId ? document.getElementById(hashId) : null;
        if (element) {
            setTimeout(() => {
                element.scrollIntoView({ behavior: "smooth", block: "start", inline: "nearest" });
            }, 100);
        }
    }
});
