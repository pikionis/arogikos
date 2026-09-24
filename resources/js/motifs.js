/* ============================================================
   ΑΡΩΓΗ — branded placeholder motifs

   The illustration drawn in place of a cover image when a model
   has none. Ported verbatim from the design prototype.

   Shared by app.js (the public site, which paints them into
   .photo-ph) and admin.js (the category panel, which previews
   them). One source of truth on purpose: a second copy in the
   admin would eventually disagree with what the site renders,
   and the preview would be lying.
   ============================================================ */

const G = '#1E9E58';
const C = '#E26D5A';
const Y = '#F4B62A';

function fig(x, color, h) {
    h = h || 0;
    return `<g transform="translate(${x},${64 - h})">
        <circle cx="0" cy="-30" r="9" fill="${color}"/>
        <path d="M-13 0 C-13 -22 13 -22 13 0 Z" fill="${color}"/></g>`;
}

export function motif(kind) {
    let inner = '';

    if (kind === 'group') {
        inner = fig(34, Y, 6) + fig(60, C, 0) + fig(86, G, 9);
    } else if (kind === 'one') {
        inner = `<circle cx="60" cy="26" r="13" fill="none" stroke="${Y}" stroke-width="5"/>` + fig(60, C, 0);
    } else if (kind === 'sun') {
        inner = `<circle cx="60" cy="30" r="15" fill="${Y}"/><path d="M30 64 A30 30 0 0 1 90 64" fill="none" stroke="${C}" stroke-width="6" stroke-linecap="round"/>`;
    } else if (kind === 'book') {
        inner = `<path d="M22 30 Q60 18 60 30 V62 Q60 52 22 60 Z" fill="${C}"/><path d="M98 30 Q60 18 60 30 V62 Q60 52 98 60 Z" fill="${G}"/><circle cx="60" cy="20" r="7" fill="${Y}"/>`;
    } else {
        /* figures — also the fallback for an unknown kind */
        inner = fig(44, C, 4) + fig(76, G, 0);
    }

    return `<svg viewBox="0 0 120 78" fill="none" aria-hidden="true">
      <rect x="14" y="62" width="92" height="5" rx="2.5" fill="${G}" opacity=".5"/>${inner}</svg>`;
}
