const defaultTheme = require("tailwindcss/defaultTheme");

const colors = require("tailwindcss/colors");

module.exports = {
    darkMode: "media",
    presets: [require("./vendor/wireui/wireui/tailwind.config.js")],
    theme: {
        extend: {
            fontFamily: {
                sans: ["darkmode-on", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                hfm: {
                    red: "#E81010",
                    lightred: "#F24040",
                    dark: "#1B2E47",
                    light: "#97C9E6",
                    white: "#f8fafc",
                    black: "#020617",
                },
                primary: colors.red,
                secondary: colors.neutral,
                "pg-primary": colors.slate,
                "pg-secondary": colors.slate,
            },
            spacing: {
                xs: "0.75rem",
                sm: "1.5rem",
                md: "2.25rem",
                lg: "3rem",
            },
        },
    },
    variants: {
        extend: {
            backgroundColor: ["active"],
        },
    },
    content: [
        "./app/**/*.php",
        "./resources/**/*.html",
        "./resources/**/*.js",
        "./resources/**/*.jsx",
        "./resources/**/*.ts",
        "./resources/**/*.tsx",
        "./resources/**/*.php",
        "./resources/**/*.vue",
        "./resources/**/*.twig",
        "./vendor/wireui/wireui/resources/**/*.blade.php",
        "./vendor/wireui/wireui/ts/**/*.ts",
        "./vendor/wireui/wireui/src/View/**/*.php",
        "./app/Livewire/**/*Table.php",
        "./app/Themes/**/*.php",
        "./vendor/power-components/livewire-powergrid/resources/views/**/*.php",
        "./vendor/power-components/livewire-powergrid/src/Themes/HfmGrid.php",
        "./vendor/livewire/flux-pro/stubs/**/*.blade.php",
        "./vendor/livewire/flux/stubs/**/*.blade.php",
    ],
    plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography")],
};
