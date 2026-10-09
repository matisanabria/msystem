// Compares the CSS written in this project against the minified production bundle and lists every rule that
// got lost, lost its condition (@media / @container / @supports) or lost its selector in the bundle.
//
//   npm run build && npm run check:css            # checks the bundle referenced by app/Views/partial/header.php
//   node build/check-css-bundle.js path/to/bundle.min.css [--strict]
//
// Exit code 1 when a rule is missing, sits outside its condition or has no selector (--strict: also missing properties).
import fs from 'node:fs';
import { ownCssBefore, unminifiedCss, cleanedThirdParty } from './css-sources.js';

const strict = process.argv.includes('--strict');
const argPath = process.argv.slice(2).find((a) => !a.startsWith('--'));

function bundlePath() {
    if (argPath) return argPath;
    const header = fs.readFileSync('app/Views/partial/header.php', 'utf8');
    const match = header.match(/resources\/(opensourcepos-[a-f0-9]+\.min\.css)/);
    if (!match) throw new Error('No bundle referenced in header.php (run npm run build first)');
    return 'public/resources/' + match[1];
}

// ---- tiny CSS parser: blocks with a prelude, declarations and child blocks ----
function stripComments(css) {
    let out = '';
    for (let i = 0; i < css.length; i++) {
        const c = css[i];
        if (c === '"' || c === "'") {
            let j = i + 1;
            while (j < css.length && css[j] !== c) j += css[j] === '\\' ? 2 : 1;
            out += css.slice(i, j + 1);
            i = j;
        } else if (c === '/' && css[i + 1] === '*') {
            const end = css.indexOf('*/', i + 2);
            i = end === -1 ? css.length : end + 1;
        } else {
            out += c;
        }
    }
    return out;
}

function parse(css) {
    css = stripComments(css);
    const root = { prelude: '', decls: [], children: [] };
    const stack = [root];
    let buf = '', paren = 0, quote = null;
    for (let i = 0; i < css.length; i++) {
        const c = css[i];
        if (quote) {
            buf += c;
            if (c === '\\') { buf += css[++i] || ''; } else if (c === quote) { quote = null; }
            continue;
        }
        if (c === '"' || c === "'") { quote = c; buf += c; continue; }
        if (c === '(') { paren++; buf += c; continue; }
        if (c === ')') { paren = Math.max(0, paren - 1); buf += c; continue; }
        if (paren > 0) { buf += c; continue; }
        const top = stack[stack.length - 1];
        if (c === '{') {
            const node = { prelude: buf.trim(), decls: [], children: [] };
            top.children.push(node);
            stack.push(node);
            buf = '';
        } else if (c === ';') {
            if (buf.trim()) top.decls.push(buf.trim());
            buf = '';
        } else if (c === '}') {
            if (buf.trim()) top.decls.push(buf.trim());
            buf = '';
            if (stack.length > 1) stack.pop();
        } else {
            buf += c;
        }
    }
    return root;
}

// ---- normalisation ----
const norm = {
    at: (s) => s.toLowerCase().replace(/\s+/g, ' ').replace(/\s*([:(),])\s*/g, '$1').replace(/\s*(>=|<=|<|>)\s*/g, '$1').trim(),
    selector: (s) => s
        .replace(/\s+/g, ' ')
        .replace(/\s*([>+~,])\s*/g, '$1')
        .replace(/\[([^\]=~|^$*]+)([~|^$*]?=)["']([^"']*)["']\]/g, '[$1$2$3]')
        .replace(/::(before|after|first-line|first-letter)/g, ':$1')
        .replace(/\s*([()])\s*/g, '$1')
        .trim(),
    frame: (s) => (s === 'from' ? '0%' : s === 'to' ? '100%' : s),
    prop: (d) => d.split(':')[0].trim().toLowerCase()
};

function splitTopLevel(s, sep) {
    const parts = [];
    let depth = 0, buf = '';
    for (const c of s) {
        if (c === '(' || c === '[') depth++;
        if (c === ')' || c === ']') depth--;
        if (c === sep && depth === 0) { parts.push(buf); buf = ''; } else { buf += c; }
    }
    parts.push(buf);
    return parts.map((p) => p.trim()).filter(Boolean);
}

const conditional = /^@(media|container|supports|layer|document)\b/i;

// Flattens the tree into leaves { ctx, selector, props } and collects structural problems
function collect(root, label, problems) {
    const leaves = [];
    (function walk(node, ctx) {
        for (const child of node.children) {
            const p = child.prelude;
            if (p.startsWith('@')) {
                const isKeyframes = /^@(-\w+-)?keyframes/i.test(p);
                if (child.children.length || conditional.test(p) || isKeyframes) {
                    if (conditional.test(p) && child.decls.length) {
                        problems.push({ kind: 'loose', text: `${label}: declaraciones sueltas dentro de "${p}": ${child.decls.slice(0, 3).join('; ')}` });
                    }
                    walk(child, ctx.concat(norm.at(p)));
                } else {
                    leaves.push({ ctx, selector: norm.at(p), props: child.decls.map(norm.prop) });
                }
            } else if (!p) {
                problems.push({ kind: 'noselector', text: `${label}: regla sin selector (${ctx.join(' > ') || 'raíz'}): ${child.decls.slice(0, 3).join('; ')}` });
            } else {
                const inKeyframes = ctx.some((c) => /^@(-\w+-)?keyframes/.test(c));
                for (const sel of splitTopLevel(p, ',')) {
                    leaves.push({ ctx, selector: inKeyframes ? norm.frame(sel) : norm.selector(sel), props: child.decls.map(norm.prop) });
                }
                if (child.children.length) walk(child, ctx); // nesting
            }
        }
    })(root, []);
    return leaves;
}

// ---- run ----
const bundleFile = bundlePath();
const sources = [...cleanedThirdParty, ...ownCssBefore, ...unminifiedCss]
    .filter((f, i, all) => all.indexOf(f) === i);

const bundleProblems = [];
const bundleLeaves = collect(parse(fs.readFileSync(bundleFile, 'utf8')), 'bundle', bundleProblems);
const index = new Map();
for (const leaf of bundleLeaves) {
    const key = leaf.ctx.join(' > ') + '||' + leaf.selector;
    if (!index.has(key)) index.set(key, new Set());
    leaf.props.forEach((p) => index.get(key).add(p));
}
const bySelector = new Map();
for (const leaf of bundleLeaves) {
    if (!bySelector.has(leaf.selector)) bySelector.set(leaf.selector, new Set());
    bySelector.get(leaf.selector).add(leaf.ctx.join(' > ') || '(sin condición)');
}

// clean-css merges longhands into shorthands (margin-top + margin-left -> margin), so accept a covering shorthand
const covers = (bundleProps, prop) => bundleProps.has(prop)
    || [...bundleProps].some((b) => prop.startsWith(b + '-'));

const errors = [...bundleProblems];
const warnings = [];
let checked = 0;
for (const file of sources) {
    const problems = [];
    const leaves = collect(parse(fs.readFileSync(file, 'utf8')), file, problems);
    const wanted = new Map();
    for (const leaf of leaves) {
        if (!leaf.props.length) continue; // empty rules are dropped on purpose by the minifier
        const key = leaf.ctx.join(' > ') + '||' + leaf.selector;
        if (!wanted.has(key)) wanted.set(key, { leaf, props: new Set() });
        leaf.props.forEach((p) => wanted.get(key).props.add(p));
    }
    for (const [key, { leaf, props }] of wanted) {
        checked++;
        const found = index.get(key);
        const where = `${file}: ${leaf.ctx.length ? '[' + leaf.ctx.join(' > ') + '] ' : ''}${leaf.selector}`;
        if (!found) {
            const elsewhere = bySelector.get(leaf.selector);
            if (elsewhere) {
                errors.push({ kind: 'context', text: `${where}\n      → en el bundle solo existe en: ${[...elsewhere].join(' | ')}` });
            } else {
                errors.push({ kind: 'missing', text: `${where}\n      → ausente en el bundle` });
            }
            continue;
        }
        const lost = [...props].filter((p) => !p.startsWith('--') && !covers(found, p));
        if (lost.length) warnings.push({ kind: 'props', text: `${where}\n      → propiedades ausentes: ${lost.join(', ')}` });
    }
}

console.log(`Bundle: ${bundleFile}`);
console.log(`Reglas fuente revisadas: ${checked} (en ${sources.length} archivos) · reglas del bundle: ${bundleLeaves.length}`);
if (errors.length) {
    console.log(`\nERRORES (${errors.length}):`);
    errors.forEach((e) => console.log(` - [${e.kind}] ${e.text}`));
}
if (warnings.length) {
    console.log(`\nAVISOS (${warnings.length}, solo fallan con --strict):`);
    warnings.forEach((w) => console.log(` - ${w.text}`));
}
if (!errors.length && !warnings.length) console.log('\nOK: ninguna regla perdida, sin condición ni sin selector.');
process.exit(errors.length || (strict && warnings.length) ? 1 : 0);
