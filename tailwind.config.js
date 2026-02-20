import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                indigo: {
                    50: "#e6f0ff",
                    100: "#cce1ff",
                    200: "#99c2ff",
                    300: "#66a3ff",
                    400: "#3385ff",
                    500: "#0066f9",
                    600: "#0052c7",
                    700: "#003d95",
                    800: "#002963",
                    900: "#001432",
                },
                blue: {
                    50: "#e6f0ff",
                    100: "#cce1ff",
                    200: "#99c2ff",
                    300: "#66a3ff",
                    400: "#3385ff",
                    500: "#0066f9",
                    600: "#0052c7",
                    700: "#003d95",
                    800: "#002963",
                    900: "#001432",
                },
                green: {
                    50: "#e6ffe6",
                    100: "#ccffcc",
                    200: "#99ff99",
                    300: "#66ff66",
                    400: "#33ff33",
                    500: "#00fc00",
                    600: "#00ca00",
                    700: "#009700",
                    800: "#006500",
                    900: "#003200",
                },
                yellow: {
                    50: "#fff6e6",
                    100: "#ffedcc",
                    200: "#ffdb99",
                    300: "#ffc866",
                    400: "#ffb533",
                    500: "#ffa608",
                    600: "#cc8506",
                    700: "#996305",
                    800: "#664203",
                    900: "#332102",
                },
                red: {
                    50: "#ffebe6",
                    100: "#ffd6cc",
                    200: "#ffad99",
                    300: "#ff8566",
                    400: "#ff5c33",
                    500: "#ff3000",
                    600: "#cc2600",
                    700: "#991d00",
                    800: "#661300",
                    900: "#330a00",
                },
            },
        },
    },

    plugins: [forms],
};
