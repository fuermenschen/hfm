const defaultTheme = require("tailwindcss/defaultTheme");

module.exports = {
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
                    white: "colors.slate.100",
                    black: "colors.slate.900",
                },
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
    ],
    plugins: [
        require("@tailwindcss/forms"),
        require("@tailwindcss/typography"),
    ],
};
