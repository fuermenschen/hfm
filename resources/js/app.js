import "./bootstrap";
import "./../../vendor/power-components/livewire-powergrid/dist/powergrid";
import "./../../vendor/power-components/livewire-powergrid/dist/tailwind.css";

import.meta.glob(["../(images|files|image_templates)/**"]);

document.addEventListener("livewire:navigated", () => {
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
