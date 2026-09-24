/* ============================================================
   ΑΡΩΓΗ — shared behaviour
   ============================================================ */
(function () {
  "use strict";

  /* ---- reusable logo mark ---- */
  function logoMark(cls) {
    return `<svg class="logo-mark ${cls || ''}" viewBox="0 0 100 100" aria-hidden="true">
      <circle cx="50" cy="27" r="11.5" fill="none" stroke="#F4B62A" stroke-width="5.5"/>
      <rect x="29" y="47" width="42" height="5.5" rx="2.75" fill="#1E9E58"/>
      <path d="M31 76 A19 19 0 0 1 69 76" fill="none" stroke="#E26D5A" stroke-width="5.5" stroke-linecap="round"/>
    </svg>`;
  }

  const NAV = [
    ["index.html", "Αρχική", "home"],
    ["about.html", "Σχετικά με εμάς", "about"],
    ["services.html", "Υπηρεσίες", "services"],
    ["blog.html", "Blog", "blog"],
    ["events.html", "Εκδηλώσεις", "events"],
    ["gallery.html", "Gallery", "gallery"],
  ];
  const CONTACT = {
    phone: "22420 29 100",
    phoneHref: "+302242029100",
    email: "info@arogi.gr",
    address: "Ιπποκράτους 24, Κως 85300",
    ig: "https://instagram.com/arogicenter",
    fb: "https://facebook.com/arogicenter",
  };

  const page = document.body.dataset.page || "";

  /* ---- header ---- */
  function buildHeader() {
    const links = NAV.map(([href, label, key]) =>
      `<a href="${href}" class="${key === page ? 'active' : ''}">${label}</a>`).join("");
    const mlinks = NAV.map(([href, label, key]) =>
      `<a href="${href}" class="${key === page ? 'active' : ''}">${label}</a>`).join("");
    return `
    <nav class="nav" id="nav">
      <div class="container nav-inner">
        <a class="brand" href="index.html">${logoMark()}<span>Αρωγή</span></a>
        <div class="nav-links">${links}</div>
        <span class="lang-switch desk" title="Γλώσσα">🌐 <span class="on">ΕΛ</span> · EN</span>
        <a class="btn btn-coral nav-cta desk" href="contact.html">Επικοινωνία</a>
        <button class="burger" id="burger" aria-label="Μενού" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </nav>
    <div class="mobile-menu" id="mobileMenu">
      ${mlinks}
      <a class="btn btn-coral" href="contact.html">Επικοινωνία</a>
      <p style="margin-top:24px;color:var(--ink-400);font-weight:700;font-family:var(--font-head)">🌐 ΕΛ · EN</p>
    </div>`;
  }

  /* ---- footer ---- */
  function buildFooter() {
    const links = NAV.map(([href, label]) => `<a href="${href}">${label}</a>`).join("");
    return `
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <div class="footer-assembly footer-logo-assemble" id="footerLogo">
            <svg class="logo-mark" viewBox="0 0 100 100" width="44" height="44" aria-hidden="true">
              <circle class="fa-circle" cx="50" cy="27" r="11.5" fill="none" stroke="#F4B62A" stroke-width="5.5"/>
              <rect class="fa-line" x="29" y="47" width="42" height="5.5" rx="2.75" fill="#1E9E58"/>
              <path class="fa-arch" d="M31 76 A19 19 0 0 1 69 76" fill="none" stroke="#E26D5A" stroke-width="5.5" stroke-linecap="round"/>
            </svg>
            <span class="brand" style="margin-bottom:0">Αρωγή</span>
          </div>
          <p style="margin-top:16px">Κέντρο εξειδικευμένης μαθησιακής υποστήριξης, συμβουλευτικής και επαγγελματικού προσανατολισμού στην Κω.</p>
          <div class="socials">
            <a href="${CONTACT.ig}" aria-label="Instagram" target="_blank" rel="noopener">${icon('instagram')}</a>
            <a href="${CONTACT.fb}" aria-label="Facebook" target="_blank" rel="noopener">${icon('facebook')}</a>
          </div>
        </div>
        <div class="footer-col">
          <h4>Σελίδες</h4>
          ${links}
        </div>
        <div class="footer-col">
          <h4>Υπηρεσίες</h4>
          <a href="services.html">Μαθησιακή Υποστήριξη</a>
          <a href="services.html">Συμβουλευτική</a>
          <a href="services.html">Επαγγελματικός Προσανατολισμός</a>
        </div>
        <div class="footer-col">
          <h4>Επικοινωνία</h4>
          <a href="tel:${CONTACT.phoneHref}">${CONTACT.phone}</a>
          <a href="mailto:${CONTACT.email}">${CONTACT.email}</a>
          <p>${CONTACT.address}</p>
          <p>Δευ–Παρ 09:00–21:00</p>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© ${new Date().getFullYear()} Αρωγή · Κέντρο Μάθησης &amp; Συμβουλευτικής</span>
        <span>Φτιαγμένο με φροντίδα στην Κω</span>
      </div>
    </div>`;
  }

  function icon(name) {
    if (name === 'instagram') return `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.1" fill="currentColor" stroke="none"/></svg>`;
    if (name === 'facebook') return `<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-7h2.3l.4-2.8h-2.7V9.4c0-.8.2-1.4 1.4-1.4h1.5V5.6c-.3 0-1.1-.1-2.1-.1-2.1 0-3.6 1.3-3.6 3.7v2H8.2V14h2.5v7z"/></svg>`;
    return '';
  }

  /* mount chrome */
  const hdr = document.getElementById('site-header');
  const ftr = document.getElementById('site-footer');
  if (hdr) hdr.innerHTML = buildHeader();
  if (ftr) ftr.innerHTML = buildFooter();

  /* ---- sticky nav shadow ---- */
  const nav = document.getElementById('nav');

  /* ---- scroll reveal + one-shot play triggers (rAF/scroll based — robust) ---- */
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

  /* ---- scroll reveal handled above via checkReveals ---- */

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

  /* ---- lightbox ---- */
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
      stage.innerHTML = `<div class="lb-frame ${shape === 'circle' ? 'frame-circle' : 'frame-arch'}" style="background:${tint}"><div class="photo-ph" style="background:transparent"></div></div>`;
      stage.querySelector('.photo-ph').innerHTML = motif(it.dataset.motif || 'group');
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

  /* ---- contact form success ---- */
  function initContact() {
    const form = document.getElementById('contactForm');
    if (!form) return;
    const wrap = document.querySelector('.contact-form-wrap');
    form.addEventListener('submit', e => {
      e.preventDefault();
      wrap.classList.add('sent');
    });
  }

  /* ---- blog filters ---- */
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

  /* ---- branded photo placeholders (swap for real photos later) ---- */
  function motif(kind) {
    const G = '#1E9E58', C = '#E26D5A', Y = '#F4B62A';
    function fig(x, color, h) {
      h = h || 0;
      return `<g transform="translate(${x},${64 - h})">
        <circle cx="0" cy="-30" r="9" fill="${color}"/>
        <path d="M-13 0 C-13 -22 13 -22 13 0 Z" fill="${color}"/></g>`;
    }
    let inner = '';
    if (kind === 'group') {
      inner = fig(34, Y, 6) + fig(60, C, 0) + fig(86, G, 9);
    } else if (kind === 'one') {
      inner = `<circle cx="60" cy="26" r="13" fill="none" stroke="${Y}" stroke-width="5"/>` + fig(60, C, 0);
    } else if (kind === 'sun') {
      inner = `<circle cx="60" cy="30" r="15" fill="${Y}"/><path d="M30 64 A30 30 0 0 1 90 64" fill="none" stroke="${C}" stroke-width="6" stroke-linecap="round"/>`;
    } else if (kind === 'book') {
      inner = `<path d="M22 30 Q60 18 60 30 V62 Q60 52 22 60 Z" fill="${C}"/><path d="M98 30 Q60 18 60 30 V62 Q60 52 98 60 Z" fill="${G}"/><circle cx="60" cy="20" r="7" fill="${Y}"/>`;
    } else { /* figures */
      inner = fig(44, C, 4) + fig(76, G, 0);
    }
    return `<svg viewBox="0 0 120 78" fill="none" aria-hidden="true">
      <rect x="14" y="62" width="92" height="5" rx="2.5" fill="${G}" opacity=".5"/>${inner}</svg>`;
  }
  document.querySelectorAll('.photo-ph').forEach(el => { el.innerHTML = motif(el.dataset.motif || 'figures'); });

  collectSun();
  updateSunrise();

  /* Arm entrance animations only if the browser actually paints (rAF fires).
     In non-painting preview contexts rAF never runs, so content stays visible. */
  requestAnimationFrame(function () {
    document.documentElement.classList.add('reveal-on');
    requestAnimationFrame(checkReveals);
  });
  window.addEventListener('load', function () {
    if (document.documentElement.classList.contains('reveal-on')) checkReveals();
  });
  initLightbox();
  initContact();
  initFilters();
})();
