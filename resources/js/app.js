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
    var picker = document.querySelector('.list-select');
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
     πατήσει έρχεται κατευθείαν εκεί.

     Ο διάλογος του συστήματος δεν ανοίγει ποτέ από μόνος του: στον
     υπολογιστή είναι ένα παράθυρο που δεν γράφει πουθενά «αντιγραφή», και ο
     κόσμος έμενε να ψάχνει. Είναι επιλογή μέσα στο μενού, με το όνομά της. */

  /* Το πρόχειρο του browser θέλει ασφαλή σύνδεση και δεν υπάρχει παντού· από
     κάτω του μένει το παλιό κόλπο με το κρυφό πεδίο, που δουλεύει και χωρίς. */
  function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise(function (resolve, reject) {
      var field = document.createElement('textarea');
      field.value = text;
      field.setAttribute('readonly', '');
      field.style.position = 'fixed';
      field.style.top = '-1000px';
      field.style.opacity = '0';
      document.body.appendChild(field);
      field.select();

      var done = false;
      try { done = document.execCommand('copy'); } catch (e) { done = false; }

      document.body.removeChild(field);
      done ? resolve() : reject();
    });
  }

  /* Κάθε κουμπί αντιγραφής, όπου κι αν στέκεται: στην κάρτα, στο μενού, στη
     μπάρα. Το ίδιο πάτημα, η ίδια επιβεβαίωση. */
  function initCopyLinks() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-share-copy-link]'), function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        var url = button.getAttribute('data-share-url');

        copyText(url).then(function () {
          button.classList.add('is-done');
          window.setTimeout(function () { button.classList.remove('is-done'); }, 2400);
        }, function () {
          /* Ούτε αυτό; Τότε η διεύθυνση διαλεγμένη, έτοιμη για Ctrl+C. */
          var panel = button.closest('[data-share-menu], .share-bar');
          var field = panel && panel.querySelector('[data-share-field]');

          if (field) {
            field.focus();
            field.setSelectionRange(0, field.value.length);

            return;
          }

          window.prompt(button.getAttribute('data-share-copied'), url);
        });
      });
    });
  }

  /* Ο διάλογος της συσκευής, μόνο όπου υπάρχει και μόνο όταν ζητηθεί. */
  function initNativeShare() {
    if (!navigator.share) return;

    Array.prototype.forEach.call(document.querySelectorAll('[data-share-native]'), function (button) {
      button.hidden = false;
      button.addEventListener('click', function () {
        navigator.share({
          title: button.getAttribute('data-share-title') || document.title,
          url: button.getAttribute('data-share-url'),
        }).catch(function () {});
      });
    });
  }

  /* Το μενού κλείνει όταν ο αναγνώστης κοιτάξει αλλού ή πατήσει Esc — όπως
     ο επιλογέας συντάκτη. */
  function initShareMenus() {
    var menus = Array.prototype.slice.call(document.querySelectorAll('[data-share-menu]'));

    if (!menus.length) return;

    document.addEventListener('click', function (event) {
      menus.forEach(function (menu) {
        if (menu.open && !menu.contains(event.target)) menu.open = false;
      });
    });

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;

      menus.forEach(function (menu) {
        if (!menu.open) return;

        menu.open = false;
        menu.querySelector('summary').focus();
      });
    });
  }

  function initShare() {
    initCopyLinks();
    initNativeShare();
    initShareMenus();
  }


  /* ---- υπηρεσίες: η επιλεγμένη έρχεται πρώτη ----

     Δεκατέσσερις υπηρεσίες σε μια στήλη σημαίνει ότι η δέκατη τέταρτη είναι
     δεκατρείς οθόνες μακριά. Αντί να ταξιδέψει ο αναγνώστης ως εκείνη, έρχεται
     εκείνη σε αυτόν: το κλικ στο όνομα τη μεταφέρει στην αρχή της λίστας και η
     σελίδα στήνεται ακριβώς από πάνω της, χωρίς κύλιση.

     Η μετακίνηση είναι αληθινή αλλαγή σειράς στο DOM, όχι οπτικό τέχνασμα με
     order: έτσι το nth-child του ζιγκ-ζαγκ ρυθμού βλέπει τη νέα σειρά και
     ξαναδένει μόνο του.

     Χωρίς JavaScript ο σύνδεσμος μένει ένα απλό #anchor και κατεβάζει τη
     σελίδα όπως πάντα — τίποτα δεν χάνεται, απλώς δεν κερδίζεται. */
  function initServiceOrder() {
    var list = document.getElementById('svc-list');
    if (!list) return;

    var links = Array.prototype.slice.call(document.querySelectorAll('.jump-list a[href^="#"]'));
    if (!links.length) return;

    /* Μόνο ζώνη αυτής της λίστας μετακινείται· οτιδήποτε άλλο (π.χ. οι συχνές
       ερωτήσεις) μένει στον σύνδεσμο που ήξερε ο browser. */
    function bandFor(hash) {
      if (!hash || hash.length < 2) return null;
      var el = document.getElementById(decodeURIComponent(hash.slice(1)));
      return el && el.parentNode === list ? el : null;
    }

    var placedAt = -1;

    function bring(band, moveFocus) {
      if (list.firstElementChild !== band) list.insertBefore(band, list.firstElementChild);

      links.forEach(function (a) {
        var on = a.getAttribute('href') === '#' + band.id;
        a.classList.toggle('current', on);
        if (on) a.setAttribute('aria-current', 'true');
        else a.removeAttribute('aria-current');
      });

      /* Η ζώνη μπορεί να μην πέρασε ποτέ από την οθόνη, οπότε να κρατάει ακόμα
         το opacity: 0 της εισόδου. Αποκαλύπτουμε πριν και μετά το άλμα: πριν
         για εκείνη, μετά για όσες έφερε μαζί της. */
      checkReveals();
      /* 'instant' και όχι 'auto': το auto σημαίνει «ό,τι λέει το CSS», και το
         CSS εδώ λέει scroll-behavior: smooth — δηλαδή ακριβώς το ταξίδι που
         ήρθαμε να καταργήσουμε. */
      band.scrollIntoView({ behavior: 'instant', block: 'start' });
      if (moveFocus) band.focus({ preventScroll: true });
      checkReveals();

      placedAt = Math.round(window.scrollY);
    }

    links.forEach(function (a) {
      a.addEventListener('click', function (e) {
        var band = bandFor(a.getAttribute('href'));
        if (!band) return;
        e.preventDefault();
        bring(band, true);
        /* replace και όχι push: δεκατέσσερις επιλογές δεν γίνονται δεκατέσσερα
           πατήματα του «πίσω» για να φύγει κανείς από τη σελίδα. */
        if (window.history && window.history.replaceState) {
          window.history.replaceState(null, '', '#' + band.id);
        }
      });
    });

    /* Ένας μοιρασμένος σύνδεσμος φέρνει την ίδια σελίδα με τον ίδιο τρόπο. */
    var atLoad = bandFor(window.location.hash);
    if (!atLoad) return;

    bring(atLoad, false);
    window.addEventListener('load', function () {
      /* Αν κάτι που φόρτωσε αργότερα μετακίνησε τη σελίδα από κάτω του,
         ξαναστήσου — εκτός αν ο αναγνώστης κύλισε στο μεταξύ μόνος του. */
      if (Math.abs(window.scrollY - placedAt) < 4) bring(atLoad, false);
    });
  }

  initProfiles();
  initAuthorSelect();
  initCarousels();
  initShare();
  initServiceOrder();
})();
