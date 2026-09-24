/* ============================================================
   ΑΡΩΓΗ — shared behaviour (Vite module)
   Ported from the design prototype's arogi.js. Header/footer are
   now server-rendered Blade, so their builders are dropped; the
   contact form really POSTs, so its fake handler is dropped too.
   Everything else (scroll-reveal, burger, sunrise, filters,
   lightbox, branded motif placeholders) is preserved verbatim.
   ============================================================ */
import { motif } from './motifs.js';

(function () {
  "use strict";

  /* ---- sticky nav shadow ---- */
  const nav = document.getElementById('nav');

  /* ---- scroll reveal + one-shot play triggers (rAF/scroll based) ---- */
  const revealTargets = Array.from(document.querySelectorAll('.reveal, .why-row, .events-hero, .footer-logo-assemble, [data-play]'));
  function checkReveals() {
    const vh = window.innerHeight || document.documentElement.clientHeight;
    for (let i = revealTargets.length - 1; i >= 0; i--) {
      const el = revealTargets[i];
      const r = el.getBoundingClientRect();
      if (r.top < vh * 0.9 && r.bottom > -40) {
        el.classList.add('in');
        if (el.dataset.play !== undefined) {
          el.querySelectorAll('.svc-icon').forEach(s => s.classList.add('play'));
        }
        revealTargets.splice(i, 1);
      }
    }
  }

  const onScroll = () => {
    if (nav) nav.classList.toggle('scrolled', window.scrollY > 8);
    checkReveals();
    updateSunrise();
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', () => { checkReveals(); updateSunrise(); }, { passive: true });

  /* ---- mobile menu ---- */
  const burger = document.getElementById('burger');
  const menu = document.getElementById('mobileMenu');
  if (burger && menu) {
    const close = () => { burger.classList.remove('open'); menu.classList.remove('open'); burger.setAttribute('aria-expanded', 'false'); document.body.style.overflow = ''; };
    burger.addEventListener('click', () => {
      const open = burger.classList.toggle('open');
      menu.classList.toggle('open', open);
      burger.setAttribute('aria-expanded', String(open));
      document.body.style.overflow = open ? 'hidden' : '';
    });
    menu.querySelectorAll('a').forEach(a => a.addEventListener('click', close));
  }

  /* ---- sunrise scroll-link ---- */
  let sunStages = [];
  function collectSun(){ sunStages = Array.from(document.querySelectorAll('.sunrise-stage')); }
  function updateSunrise() {
    sunStages.forEach(stage => {
      const r = stage.getBoundingClientRect();
      const vh = window.innerHeight;
      const p = Math.max(0, Math.min(1, (vh - r.top) / (vh + r.height * 0.5)));
      stage.style.setProperty('--p', p.toFixed(3));
    });
  }

  /* ---- branded photo placeholders (fallback when a model has no media) ---- */
  document.querySelectorAll('.photo-ph').forEach(el => { el.innerHTML = motif(el.dataset.motif || 'figures'); });

  /* ---- lightbox (gallery) ---- */
  function initLightbox() {
    const items = document.querySelectorAll('[data-lightbox]');
    if (!items.length) return;
    const ov = document.createElement('div');
    ov.className = 'lightbox';
    ov.innerHTML = `<button class="lb-close" aria-label="Κλείσιμο">✕</button>
      <button class="lb-nav lb-prev" aria-label="Προηγούμενο">‹</button>
      <div class="lb-stage"></div>
      <button class="lb-nav lb-next" aria-label="Επόμενο">›</button>
      <div class="lb-cap"></div>`;
    document.body.appendChild(ov);
    const stage = ov.querySelector('.lb-stage');
    const cap = ov.querySelector('.lb-cap');
    let idx = 0;
    const arr = Array.from(items);
    function show(i) {
      idx = (i + arr.length) % arr.length;
      const it = arr[idx];
      const tint = it.dataset.tint || 'var(--soft-green)';
      const shape = it.dataset.shape || 'arch';
      const frameClass = shape === 'circle' ? 'frame-circle' : 'frame-arch';
      if (it.dataset.full) {
        stage.innerHTML = `<div class="lb-frame ${frameClass}" style="background:${tint}"><img src="${it.dataset.full}" alt="${it.dataset.cap || ''}" style="width:100%;height:100%;object-fit:cover"></div>`;
      } else {
        stage.innerHTML = `<div class="lb-frame ${frameClass}" style="background:${tint}"><div class="photo-ph" style="background:transparent"></div></div>`;
        stage.querySelector('.photo-ph').innerHTML = motif(it.dataset.motif || 'group');
      }
      cap.textContent = it.dataset.cap || '';
    }
    items.forEach((it, i) => it.addEventListener('click', () => { ov.classList.add('open'); document.body.style.overflow = 'hidden'; show(i); }));
    ov.querySelector('.lb-close').addEventListener('click', () => { ov.classList.remove('open'); document.body.style.overflow = ''; });
    ov.querySelector('.lb-prev').addEventListener('click', () => show(idx - 1));
    ov.querySelector('.lb-next').addEventListener('click', () => show(idx + 1));
    ov.addEventListener('click', e => { if (e.target === ov) { ov.classList.remove('open'); document.body.style.overflow = ''; } });
    document.addEventListener('keydown', e => {
      if (!ov.classList.contains('open')) return;
      if (e.key === 'Escape') { ov.classList.remove('open'); document.body.style.overflow = ''; }
      if (e.key === 'ArrowLeft') show(idx - 1);
      if (e.key === 'ArrowRight') show(idx + 1);
    });
  }

  /* ---- blog + gallery filter chips ---- */
  function initFilters() {
    const chips = document.querySelectorAll('[data-filter]');
    if (!chips.length) return;
    const cards = document.querySelectorAll('[data-cat]');
    chips.forEach(chip => chip.addEventListener('click', () => {
      chips.forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      const f = chip.dataset.filter;
      cards.forEach(card => {
        const show = f === 'all' || card.dataset.cat === f;
        card.style.display = show ? '' : 'none';
      });
    }));
  }

  collectSun();
  updateSunrise();

  /* Arm entrance animations only once the browser actually paints. */
  requestAnimationFrame(function () {
    document.documentElement.classList.add('reveal-on');
    requestAnimationFrame(checkReveals);
  });
  window.addEventListener('load', function () {
    if (document.documentElement.classList.contains('reveal-on')) checkReveals();
  });

  initLightbox();
  initFilters();
})();
