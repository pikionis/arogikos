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


  /* ---- προφίλ ανθρώπων (ομάδα + συντάκτης άρθρου) ----

     Το βιογραφικό ζει κομμένο μέσα στην κάρτα και ολόκληρο μέσα σε ένα
     <dialog>. Το native dialog φέρνει Esc, παγίδευση εστίασης και inert
     υπόλοιπη σελίδα — τίποτα από αυτά δεν ξαναγράφεται εδώ. Μένει μόνο ο
     συγχρονισμός της κίνησης: η κλάση μπαίνει στο επόμενο καρέ ώστε το
     transition να έχει από πού να ξεκινήσει, και το κλείσιμο περιμένει
     να τελειώσει πριν αποσυρθεί το dialog. */
  function initProfiles() {
    var openers = document.querySelectorAll('[data-profile-open]');
    if (!openers.length) return;

    var quick = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function close(dlg) {
      dlg.classList.remove('is-open');
      if (quick) { dlg.close(); return; }
      window.setTimeout(function () { if (dlg.open) dlg.close(); }, 260);
    }

    openers.forEach(function (btn) {
      var dlg = document.getElementById(btn.dataset.profileOpen);
      if (!dlg) return;

      btn.addEventListener('click', function () {
        dlg.showModal();
        requestAnimationFrame(function () { dlg.classList.add('is-open'); });
      });

      /* Το κλικ στο φόντο φτάνει στο ίδιο το dialog, όχι στο πάνελ. */
      dlg.addEventListener('click', function (e) {
        if (e.target === dlg) close(dlg);
      });

      dlg.querySelectorAll('[data-profile-close]').forEach(function (x) {
        x.addEventListener('click', function () { close(dlg); });
      });

      /* Esc κλείνει το dialog μόνο του· το κρατάμε για να παίξει η κίνηση. */
      dlg.addEventListener('cancel', function (e) {
        e.preventDefault();
        close(dlg);
      });
    });
  }

  initLightbox();
  initFilters();

  /* ---- επιλογέας συντάκτη ----

     Το <details> ανοίγει και κλείνει μόνο του με το πληκτρολόγιο· αυτό που
     δεν κάνει είναι να κλείνει όταν ο αναγνώστης κοιτάξει αλλού. */
  function initAuthorSelect() {
    var picker = document.querySelector('.author-select');
    if (!picker) return;

    document.addEventListener('click', function (e) {
      if (picker.open && !picker.contains(e.target)) picker.open = false;
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && picker.open) {
        picker.open = false;
        picker.querySelector('summary').focus();
      }
    });
  }

  /* ---- καρουζέλ άρθρων ---- */
  /* Το κύλισμα το κάνει το CSS· εδώ μπαίνουν μόνο τα βελάκια, και μόνο όταν
     έχουν νόημα: μια σειρά που χωράει ολόκληρη δεν θέλει κουμπιά, και ένα
     κουμπί που δεν πάει πουθενά είναι χειρότερο από κανένα. */
  function initCarousels() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-carousel]'), function (box) {
      var track = box.querySelector('[data-carousel-track]');
      var back = box.querySelector('[data-carousel-back]');
      var next = box.querySelector('[data-carousel-next]');

      if (!track || !back || !next) return;

      function step() {
        var card = track.firstElementChild;

        return card ? card.getBoundingClientRect().width + 24 : track.clientWidth * 0.8;
      }

      function refresh() {
        var over = track.scrollWidth - track.clientWidth;
        var room = over > 4;

        back.hidden = !room || track.scrollLeft <= 2;
        next.hidden = !room || track.scrollLeft >= over - 2;
      }

      back.addEventListener('click', function () {
        track.scrollBy({ left: -step(), behavior: 'smooth' });
      });

      next.addEventListener('click', function () {
        track.scrollBy({ left: step(), behavior: 'smooth' });
      });

      track.addEventListener('scroll', refresh, { passive: true });
      window.addEventListener('resize', refresh);

      refresh();
    });
  }

  /* ---- κοινοποίηση ---- */
  /* Ο σύνδεσμος είναι η διεύθυνση της ίδιας της σελίδας, οπότε όποιος τον
     πατήσει έρχεται κατευθείαν εδώ. Το πρόχειρο θέλει ασφαλή σύνδεση και
     δεν υπάρχει παντού, γι' αυτό το πεδίο με τη διεύθυνση μένει πάντα: αν
     αποτύχει η αντιγραφή, διαλέγεται με το χέρι. */
  function initShare() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-share]'), function (bar) {
      var url = bar.getAttribute('data-share-url');
      var title = bar.getAttribute('data-share-title') || document.title;
      var native = bar.querySelector('[data-share-native]');
      var copy = bar.querySelector('[data-share-copy-button]');
      var label = bar.querySelector('[data-share-copy-label]');
      var field = bar.querySelector('[data-share-field]');

      if (native && navigator.share) {
        native.hidden = false;
        bar.classList.add('has-native');
        native.addEventListener('click', function () {
          navigator.share({ title: title, url: url }).catch(function () {});
        });
      }

      if (!copy || !label || !field) return;

      copy.addEventListener('click', function () {
        function done() {
          var was = bar.getAttribute('data-share-copy');
          label.textContent = bar.getAttribute('data-share-copied');
          copy.classList.add('is-done');
          window.setTimeout(function () {
            label.textContent = was;
            copy.classList.remove('is-done');
          }, 2400);
        }

        if (navigator.clipboard && window.isSecureContext) {
          navigator.clipboard.writeText(url).then(done, select);

          return;
        }

        select();
      });

      /* Η διεύθυνση διαλεγμένη, έτοιμη για Ctrl+C. */
      function select() {
        field.hidden = false;
        field.focus();
        field.setSelectionRange(0, field.value.length);
      }
    });
  }

  initProfiles();
  initAuthorSelect();
  initCarousels();
  initShare();
})();
