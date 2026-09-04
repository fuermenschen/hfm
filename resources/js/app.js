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
                const previewUrl = URL.createObjectURL(file);
                state.previewUrls.set(variant, previewUrl);
                preview.src = previewUrl;
                requestAnimationFrame(() => preview.classList.remove("opacity-0"));
                const skeleton = container.querySelector(`[data-story-preview-skeleton="${variant}"]`);
                skeleton?.classList.add("opacity-0");
                window.setTimeout(() => skeleton?.setAttribute("hidden", "hidden"), 300);
            }

            return file;
        });

    state.files.set(
        variant,
        promise.catch((error) => {
            state.files.delete(variant);

            throw error;
        }),
    );

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

function cleanupStoryShare(scope = document) {
    scope.querySelectorAll("[data-story-share]").forEach((container) => {
        container.storyShareState?.previewUrls.forEach((url) => URL.revokeObjectURL(url));
    });
}

function initStoryShare(scope = document) {
    scope.querySelectorAll("[data-story-share]").forEach((container) => {
        if (container.dataset.storyShareInitialized === "1") {
            return;
        }

        container.dataset.storyShareInitialized = "1";
        container.storyShareState = {
            files: new Map(),
            readyFiles: new Map(),
            previewUrls: new Map(),
            variant: "light",
        };
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
                setStoryShareStatus(
                    container,
                    "Teilen wird von diesem Browser nicht unterstützt. Nutze Story-Bild herunterladen.",
                );
                return;
            }

            navigator.share({ files: [file] }).catch((error) => {
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

function copyShareText(textarea) {
    textarea.select();
    textarea.setSelectionRange(0, textarea.value.length);

    if (navigator.clipboard?.writeText) {
        return navigator.clipboard.writeText(textarea.value);
    }

    try {
        return document.execCommand("copy") ? Promise.resolve() : Promise.reject(new Error("Copy failed"));
    } catch (error) {
        return Promise.reject(error);
    }
}

function initShareTexts(scope = document) {
    scope.querySelectorAll("[data-share-text-template]").forEach((template) => {
        if (template.dataset.shareTextInitialized === "1") {
            return;
        }

        template.dataset.shareTextInitialized = "1";
        const textarea = template.querySelector("[data-share-text-content]");
        const status = template.querySelector("[data-share-text-status]");
        if (!(textarea instanceof HTMLTextAreaElement) || !status) {
            return;
        }

        template.querySelector("[data-share-text]")?.addEventListener("click", () => {
            if (typeof navigator.share !== "function") {
                status.textContent = "Teilen wird von diesem Browser nicht unterstützt. Nutze Text kopieren.";
                return;
            }

            navigator.share({ text: textarea.value }).catch((error) => {
                if (error?.name !== "AbortError") {
                    status.textContent = "Teilen nicht verfügbar. Nutze Text kopieren.";
                }
            });
        });
        template.querySelector("[data-copy-text]")?.addEventListener("click", () => {
            copyShareText(textarea)
                .then(() => {
                    status.textContent = "Text kopiert.";
                })
                .catch(() => {
                    status.textContent = "Text konnte nicht kopiert werden. Bitte markiere ihn manuell.";
                });
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

function syncChartTodayMarkers(chart) {
    const svg = chart.querySelector("svg");
    if (!svg) {
        return;
    }

    const gridLines = Array.from(svg.querySelectorAll('[data-grid-line][data-axis="x"]'));
    chart.querySelectorAll("[data-today-marker]").forEach((marker) => {
        const tickIndex = Number.parseInt(marker.dataset.todayMarker, 10);
        const gridLine = gridLines[tickIndex];
        if (!gridLine) {
            return;
        }

        ["x1", "x2", "y1", "y2"].forEach((attribute) => {
            const value = gridLine.getAttribute(attribute);
            if (value !== null) {
                marker.setAttribute(attribute, value);
            }
        });
    });

    syncCompactChartYAxisLabels(chart);
}

function syncCompactChartYAxisLabels(chart) {
    if (!chart.hasAttribute("data-compact-y-axis")) {
        return;
    }

    const locale = chart.getAttribute("locale") || navigator.language || "en-US";
    const localeParts = new Intl.NumberFormat(locale).formatToParts(12345.6);
    const groupSeparator = localeParts.find((part) => part.type === "group")?.value || ",";
    const decimalSeparator = localeParts.find((part) => part.type === "decimal")?.value || ".";
    const compactFormatter = new Intl.NumberFormat("en-US", {
        notation: "compact",
        compactDisplay: "short",
        maximumFractionDigits: 0,
    });

    chart.querySelectorAll('[data-tick-label][data-axis="y"] text').forEach((label) => {
        const text = label.textContent.trim();
        if (text.toLowerCase().endsWith("k")) {
            return;
        }

        const normalized = text
            .split(groupSeparator)
            .join("")
            .replace(decimalSeparator, ".")
            .replace(/[^\d.-]/g, "");
        const value = Number(normalized);
        if (!Number.isFinite(value)) {
            return;
        }

        const compactLabel = compactFormatter.format(value).toLowerCase();
        if (label.textContent !== compactLabel) {
            label.textContent = compactLabel;
        }
    });
}

function initChartTodayMarkers(scope = document) {
    scope.querySelectorAll("ui-chart").forEach((chart) => {
        if (chart.dataset.todayMarkersInitialized === "1") {
            return;
        }

        chart.dataset.todayMarkersInitialized = "1";
        let frame = null;
        let mutationObserver;
        let resizeObserver;
        const scheduleSync = () => {
            if (!chart.isConnected) {
                mutationObserver.disconnect();
                resizeObserver.disconnect();
                return;
            }

            if (frame !== null) {
                cancelAnimationFrame(frame);
            }

            frame = requestAnimationFrame(() => {
                frame = null;
                syncChartTodayMarkers(chart);
            });
        };

        mutationObserver = new MutationObserver(scheduleSync);
        mutationObserver.observe(chart, { childList: true, subtree: true });

        resizeObserver = new ResizeObserver(scheduleSync);
        resizeObserver.observe(chart);

        syncChartTodayMarkers(chart);
    });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
        initHeroLqip();
        initStoryShare();
        initShareTexts();
        initExpandableComments();
        initChartTodayMarkers();
    });
} else {
    initHeroLqip();
    initChartTodayMarkers();
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
        setStoryShareStatus(container, "Bilder werden vorbereitet. Das kann einen Moment dauern.");
        Promise.all([prepareStoryFile(container, "light"), prepareStoryFile(container, "dark")])
            .then(() => setStoryShareStatus(container, "Bilder sind bereit zum Teilen."))
            .catch(() =>
                setStoryShareStatus(container, "Bilder konnten nicht vorbereitet werden. Bitte versuche es erneut."),
            );
        selectStoryVariant(container, "light");
    }
});

initStoryShare();
initShareTexts();
initExpandableComments();

document.addEventListener("livewire:navigating", () => cleanupStoryShare());

document.addEventListener("livewire:navigated", () => {
    initHeroLqip();
    initStoryShare();
    initShareTexts();
    initExpandableComments();
    initChartTodayMarkers();
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
