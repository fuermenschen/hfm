const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
    presets: [require("./vendor/wireui/wireui/tailwind.config.js")],
    theme: {
        extend: {
            fontFamily: {
                sans: ["darkmode-on", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                hfm: {
                    red: "#E81010",
                    dark: "#1B2E47",
                    light: "#97C9E6",
                    white: "#f8fafc",
                    black: "#020617",
                },
            },
            spacing: {
                xs: "0.75rem",
                sm: "1.5rem",
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
    ],
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
