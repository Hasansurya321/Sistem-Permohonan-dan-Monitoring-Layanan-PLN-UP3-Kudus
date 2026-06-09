/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
        "./vendor/filament/**/*.blade.php",
    ],
    theme: {
        extend: {
            colors: {
                plnBlue: "#2F5AA8",
                plnOrange: "#D08A3C",
                ink: "#1F2A44",
                "pln-dark": "#093c5d",
                "pln-yellow": "#f7c600",
            },
            fontFamily: {
                sans: ["Inter", "sans-serif"],
            },
        },
    },
    plugins: [],
};
