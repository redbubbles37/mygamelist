/** @type {import('tailwindcss').Config} */
// As cores vêm de variáveis CSS definidas por tema (public/assets/js/theme.js).
const v = (name) => `rgb(var(${name}) / <alpha-value>)`;

module.exports = {
    content: ['./views/**/*.php', './src/**/*.php', './public/assets/js/*.js'],
    theme: {
        extend: {
            fontSize: { '2xs': ['0.6875rem', { lineHeight: '1rem' }] },
            zIndex: { pop: '30', fab: '40', modal: '45', menu: '50', viewer: '60' },
            fontFamily: { sans: ['var(--font-sans)'] },
            borderRadius: { md: 'var(--r-md)', lg: 'var(--r-lg)', xl: 'var(--r-xl)', '2xl': 'var(--r-2xl)' },
            colors: {
                slate: Object.fromEntries([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].map((k) => [k, v('--s-' + k)])),
                brand: { 400: v('--brand-400'), 500: v('--brand-500'), 600: v('--brand-600') },
                accent: v('--accent'), c3: v('--c3'), danger: v('--danger'), 'danger-solid': v('--danger-solid'),
                ok: v('--ok'), neutral: v('--neutral'),
                gold: Object.fromEntries([50, 200, 300, 400, 500, 600, 700, 900, 950].map((k) => [k, v('--gold-' + k)])),
                hero: v('--hero'),
                'on-brand': v('--on-brand'), 'on-tile': v('--on-tile'),
            },
        },
    },
};
