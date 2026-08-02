// Colors below resolve to CSS custom properties (set at runtime by the admin's
// Theme settings page — see app/Support/ThemeColorGenerator.php and
// resources/views/templates/wrapper.blade.php) instead of static hex values.
// This is what lets an admin re-theme the whole panel without a rebuild: the
// generated CSS still references var(--color-neutral-900) etc, and the actual
// values are swapped server-side per request.
function withOpacity(variableName) {
    return ({ opacityValue }) => {
        if (opacityValue === undefined) {
            return `rgb(var(${variableName}))`;
        }
        return `rgb(var(${variableName}) / ${opacityValue})`;
    };
}

function themeableRamp(variablePrefix) {
    return {
        50: withOpacity(`--color-${variablePrefix}-50`),
        100: withOpacity(`--color-${variablePrefix}-100`),
        200: withOpacity(`--color-${variablePrefix}-200`),
        300: withOpacity(`--color-${variablePrefix}-300`),
        400: withOpacity(`--color-${variablePrefix}-400`),
        500: withOpacity(`--color-${variablePrefix}-500`),
        600: withOpacity(`--color-${variablePrefix}-600`),
        700: withOpacity(`--color-${variablePrefix}-700`),
        800: withOpacity(`--color-${variablePrefix}-800`),
        900: withOpacity(`--color-${variablePrefix}-900`),
    };
}

module.exports = {
    content: [
        './resources/scripts/**/*.{js,ts,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                header: ['"IBM Plex Sans"', '"Roboto"', 'system-ui', 'sans-serif'],
            },
            colors: {
                black: '#131a20',
                // "primary" and "neutral" are deprecated, prefer the use of "blue" and "gray"
                // in new code.
                primary: themeableRamp('primary'),
                gray: themeableRamp('neutral'),
                neutral: themeableRamp('neutral'),
                // Aliased to the same "primary"/accent variables as `primary` above —
                // `cyan-*` is used pervasively throughout the app as the de facto
                // accent color (active nav states, links, progress bars, badges), so
                // this keeps all of that in sync with the single "Accent" theme color.
                cyan: themeableRamp('primary'),
            },
            fontSize: {
                '2xs': '0.625rem',
            },
            transitionDuration: {
                250: '250ms',
            },
            borderColor: theme => ({
                default: theme('colors.neutral.400', 'currentColor'),
            }),
        },
    },
    plugins: [
        require('@tailwindcss/line-clamp'),
        require('@tailwindcss/forms')({
            strategy: 'class',
        }),
    ]
};
