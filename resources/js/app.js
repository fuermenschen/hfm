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

function setStoryShareStatus(container, message) {
    const status = container.querySelector("[data-story-share-status]");
    if (status) {
        status.textContent = message;
    }
}

function storyUrl(container, variant, type) {
    return container.dataset[`storyShare${variant[0].toUpperCase()}${variant.slice(1)}${type}`];
}

function prepareStoryFile(container, variant) {
    const state = container.storyShareState;
    if (state.files.has(variant)) {
        return state.files.get(variant);
    }

    const promise = fetch(storyUrl(container, variant, "Preview"), { credentials: "same-origin" })
        .then((response) => {
            if (!response.ok) {
                throw new Error("Story image could not be loaded.");
            }

            return response.blob();
        })
        .then((blob) => {
            const file = new File([blob], `spendenaktion-${variant}.jpg`, { type: blob.type || "image/jpeg" });
            state.readyFiles.set(variant, file);
            const preview = container.querySelector(`[data-story-preview="${variant}"]`);
            if (preview) {
                preview.src = URL.createObjectURL(file);
                preview.classList.remove("hidden");
                container.querySelector(`[data-story-preview-skeleton="${variant}"]`)?.setAttribute("hidden", "hidden");
            }

            return file;
        });

    state.files.set(variant, promise.catch((error) => {
        state.files.delete(variant);

        throw error;
    }));

    return state.files.get(variant);
}

function selectStoryVariant(container, variant) {
    const state = container.storyShareState;
    state.variant = variant;
    container.querySelectorAll("[data-story-variant]").forEach((button) => {
        button.dataset.selected = String(button.dataset.storyVariant === variant);
    });

    const shareButton = container.querySelector("[data-share-story]");
    if (!shareButton) {
        return;
    }

    shareButton.disabled = true;
    prepareStoryFile(container, variant)
        .then(() => {
            if (state.variant === variant) {
                shareButton.disabled = false;
            }
        })
        .catch(() => setStoryShareStatus(container, "Bild konnte nicht vorbereitet werden. Bitte herunterladen."));
}

function initStoryShare(scope = document) {
    scope.querySelectorAll("[data-story-share]").forEach((container) => {
        if (container.dataset.storyShareInitialized === "1") {
            return;
        }

        container.dataset.storyShareInitialized = "1";
        container.storyShareState = { files: new Map(), readyFiles: new Map(), variant: "light" };
        container.querySelectorAll("[data-story-variant]").forEach((button) => {
            button.addEventListener("click", () => selectStoryVariant(container, button.dataset.storyVariant));
        });
        container.querySelector("[data-share-story]")?.addEventListener("click", () => {
            const state = container.storyShareState;
            const file = state.readyFiles.get(state.variant);
            if (!file) {
                setStoryShareStatus(container, "Bild wird noch vorbereitet.");
                return;
            }

            if (typeof navigator.share !== "function" || navigator.canShare?.({ files: [file] }) === false) {
                setStoryShareStatus(container, "Teilen wird von diesem Browser nicht unterstützt. Nutze Story-Bild herunterladen.");
                return;
            }

            navigator.share({ files: [file] })
                .catch((error) => {
                    if (error?.name !== "AbortError") {
                        setStoryShareStatus(container, "Teilen nicht verfügbar. Nutze Story-Bild herunterladen.");
                    }
                });
        });
        container.querySelector("[data-download-story]")?.addEventListener("click", () => {
            window.location.assign(storyUrl(container, container.storyShareState.variant, "Download"));
        });
    });
}

function initExpandableComments(scope = document) {
    scope.querySelectorAll("[data-expandable-comment]").forEach((comment) => {
        const button = comment.parentElement?.querySelector("[data-expand-comment]");
        if (!button || comment.dataset.expandableCommentInitialized === "1") {
            return;
        }

        comment.dataset.expandableCommentInitialized = "1";
        requestAnimationFrame(() => {
            if (comment.scrollHeight <= comment.clientHeight) {
                return;
            }

            button.hidden = false;
            button.addEventListener("click", () => {
                const expanded = comment.classList.toggle("line-clamp-3");
                button.textContent = expanded ? "Gesamten Kommentar anzeigen" : "Kommentar einklappen";
            });
        });
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        initHeroLqip();
        initStoryShare();
        initExpandableComments();
    });
} else {
    initHeroLqip();
}

document.addEventListener("click", (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const trigger = event.target.closest("[data-story-share-open]");
    if (!trigger) {
        return;
    }

    const container = document.getElementById(trigger.dataset.storyShareOpen);
    if (container?.storyShareState) {
        Promise.all([prepareStoryFile(container, "light"), prepareStoryFile(container, "dark")])
            .catch(() => setStoryShareStatus(container, "Bilder konnten nicht vorbereitet werden. Bitte versuche es erneut."));
        selectStoryVariant(container, "light");
    }
});

initStoryShare();
initExpandableComments();

document.addEventListener("livewire:navigated", () => {
    initHeroLqip();
    initStoryShare();
    initExpandableComments();
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
