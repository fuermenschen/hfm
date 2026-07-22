const defaultTheme = require("tailwindcss/defaultTheme");

const colors = require("tailwindcss/colors");

module.exports = {
    darkMode: "class",
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
            typography: ({ theme }) => ({
                DEFAULT: {
                    css: {
                        "--tw-prose-body": theme("colors.hfm.dark"),
                        "--tw-prose-headings": theme("colors.hfm.dark"),
                        "--tw-prose-lead": theme("colors.hfm.dark"),
                        "--tw-prose-links": theme("colors.hfm.red"),
                        "--tw-prose-bold": theme("colors.hfm.dark"),
                        "--tw-prose-counters": theme("colors.hfm.dark"),
                        "--tw-prose-bullets": theme("colors.hfm.dark"),
                        "--tw-prose-quotes": theme("colors.hfm.dark"),
                        "--tw-prose-code": theme("colors.hfm.dark"),
                        "--tw-prose-invert-body": theme("colors.hfm.white"),
                        "--tw-prose-invert-headings": theme("colors.hfm.white"),
                        "--tw-prose-invert-lead": theme("colors.hfm.white"),
                        "--tw-prose-invert-links": theme("colors.hfm.lightred"),
                        "--tw-prose-invert-bold": theme("colors.hfm.white"),
                        "--tw-prose-invert-counters": theme("colors.hfm.white"),
                        "--tw-prose-invert-bullets": theme("colors.hfm.white"),
                        "--tw-prose-invert-quotes": theme("colors.hfm.white"),
                        "--tw-prose-invert-code": theme("colors.hfm.white"),
                    },
                },
            }),
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
        "./app/Livewire/**/*Table.php",
        "./app/Themes/**/*.php",
        "./vendor/power-components/livewire-powergrid/resources/views/**/*.php",
        "./vendor/power-components/livewire-powergrid/src/Themes/HfmGrid.php",
        "./vendor/livewire/flux-pro/stubs/**/*.blade.php",
        "./vendor/livewire/flux/stubs/**/*.blade.php",
    ],
    plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography")],
};
