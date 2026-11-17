/*
 | Public site script. Alpine ships with Livewire; the compare store is registered inline
 | in the site layout (it has to exist before Alpine starts). What lives here is decoration:
 | the home hero readout "settling" like a scale that has just been loaded.
 */

const SEGMAP = { 0: 'abcdef', 1: 'bc', 2: 'abged', 3: 'abgcd', 4: 'fgbc', 5: 'afgcd', 6: 'afgedc', 7: 'abc', 8: 'abcdefg', 9: 'abcdfg', '-': 'g', ' ': '' };
const SEGS = [['a', 3, 0, 12, 4], ['b', 14, 3, 4, 14], ['c', 14, 19, 4, 14], ['d', 3, 32, 12, 4], ['e', 0, 19, 4, 14], ['f', 0, 3, 4, 14], ['g', 3, 16, 12, 4]];

/* Same drawing as the <x-site.seg7> Blade component. */
function seg7(str) {
    const chars = [];
    for (const ch of str) {
        if (ch === '.' && chars.length) chars[chars.length - 1].dp = true;
        else chars.push({ c: ch });
    }
    const W = 24;
    const body = chars.map((o, n) => {
        const on = SEGMAP[o.c] || '';
        const rects = SEGS.map((s) => `<rect class="${on.includes(s[0]) ? 'on' : 'off'}" x="${s[1]}" y="${s[2]}" width="${s[3]}" height="${s[4]}" rx="1.6"/>`).join('');
        return `<g transform="translate(${n * W + 4},2) skewX(-5)">${rects}${o.dp ? '<rect class="on" x="19.5" y="32" width="4" height="4" rx="1"/>' : ''}</g>`;
    }).join('');
    return `<svg class="seg" viewBox="0 0 ${chars.length * W + 4} 40" aria-hidden="true">${body}</svg>`;
}

function settleReadout() {
    const readout = document.querySelector('[data-readout]');
    if (!readout) return;
    let reduced = true;
    try { reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) { /* keep the still readout */ }
    if (reduced) return;

    const weight = readout.querySelector('[data-ro="weight"]');
    const total = readout.querySelector('[data-ro="total"]');
    if (!weight || !total) return;

    const steps = [['0.000', '    0'], ['1.318', '15816'], ['1.236', '14832'], ['1.262', '15144'], ['1.250', '15000']];
    let i = 0;
    const step = () => {
        weight.innerHTML = seg7(steps[i][0]);
        total.innerHTML = seg7(steps[i][1]);
        i += 1;
        if (i >= steps.length) clearInterval(timer);
    };
    const timer = setInterval(step, 240);
    step();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', settleReadout);
else settleReadout();
