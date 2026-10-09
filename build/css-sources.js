// CSS written in this project (not third-party .min.css). Shared by gulpfile.js (prod-css) and build/check-css-bundle.js.
// clean-css 4 (gulp-clean-css) cannot parse everything we write, so these files are concatenated as they are:
//  - register.css: @container (clean-css 4 drops the selector and the condition of the whole block)
//  - reports.css: the SVG property "r" (.ct-tooltip-point:hover) is deleted as unknown
// Run "npm run check:css" after every build to see what the minifier lost.

export const unminifiedCss = [
    './public/css/register.css',
    './public/css/reports.css'
];

// Own CSS that goes through cleanCSS, in bundle order (the unminified files come right after, as in the original order)
export const ownCssBefore = [
    './node_modules/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.css',
    './public/css/bootstrap.autocomplete.css',
    './public/css/invoice.css',
    './public/css/ospos.css',
    './public/css/ospos_print.css',
    './public/css/popupbox.css',
    './public/css/receipt.css'
];

// Third-party CSS that the build passes through cleanCSS (the rest of the bundle is already minified upstream)
export const cleanedThirdParty = [
    './node_modules/bootstrap-daterangepicker/daterangepicker.css',
    './node_modules/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.css'
];
