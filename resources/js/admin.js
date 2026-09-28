/* ============================================================
   ΑΡΩΓΗ — admin panel scripts

   Alpine lives ONLY here. resources/js/app.js (the public site)
   is a dependency-free IIFE and stays that way.
   ============================================================ */

import Alpine from 'alpinejs';
import { motif } from './motifs.js';
import Trix from 'trix';
import 'trix/dist/trix.css';

window.Alpine = Alpine;

/**
 * Copy-to-clipboard, used for invitation links.
 *
 * Production still runs MAIL_MAILER=log, so the owner needs to be able to
 * hand an invitee their link by other means.
 */
Alpine.data('copyable', (text) => ({
    copied: false,
    async copy() {
        try {
            await navigator.clipboard.writeText(text);
        } catch {
            // Clipboard API needs a secure context; fall back to a selection.
            const el = document.createElement('textarea');
            el.value = text;
            el.setAttribute('readonly', '');
            el.style.position = 'fixed';
            el.style.opacity = '0';
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
        }
        this.copied = true;
        setTimeout(() => (this.copied = false), 2000);
    },
}));

/** Confirm-before-submit for destructive forms. */
Alpine.data('confirmable', (message) => ({
    confirm(event) {
        if (!window.confirm(message)) {
            event.preventDefault();
        }
    },
}));


/**
 * x-motif="expression" — draws a branded placeholder motif into the element.
 *
 * Uses the very generator the public site uses (resources/js/motifs.js), so
 * the preview in the category panel is what a coverless article really shows,
 * not a second drawing that could drift from it.
 */
Alpine.directive('motif', (el, { expression }, { effect, evaluateLater }) => {
    const getKind = evaluateLater(expression);

    effect(() => {
        getKind((kind) => {
            el.innerHTML = motif(kind || 'figures');
        });
    });
});

/**
 * Inline category management, mounted beside the article form's category
 * picker.
 *
 * Talks JSON so the half-written article around it survives: a normal form
 * post would reload the page and throw away the body, cover and everything
 * else the author had typed.
 */
Alpine.data('categoryPicker', (config) => ({
    open: false,
    busy: false,
    message: '',
    error: false,
    editing: null,
    selected: config.selected || '',
    options: config.options || [],
    colors: config.colors || {},
    motifs: config.motifs || {},
    form: { label: '', color: 'green', motif: 'book' },
    newForm: { label: '', color: 'green', motif: 'book' },

    async toggle() {
        this.open = !this.open;
        // The server-rendered list carries no article counts; fetch them the
        // first time the panel is actually opened.
        if (this.open) await this.refresh();
    },

    csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    },

    async request(url, method, body) {
        this.busy = true;
        this.message = '';
        this.error = false;

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body ? JSON.stringify(body) : undefined,
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok) {
                // Laravel validation errors come back under `errors`.
                const first = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                this.error = true;
                this.message = first || data.message || 'Κάτι πήγε στραβά. Δοκιμάστε ξανά.';
                return null;
            }

            if (Array.isArray(data.categories)) this.options = data.categories;
            if (data.message) this.message = data.message;

            return data;
        } catch {
            this.error = true;
            this.message = 'Δεν ήταν δυνατή η σύνδεση. Ελέγξτε τη σύνδεσή σας.';
            return null;
        } finally {
            this.busy = false;
        }
    },

    async refresh() {
        await this.request(config.endpoint, 'GET');
    },

    async create() {
        const label = this.newForm.label.trim();
        if (!label) return;

        const data = await this.request(config.endpoint, 'POST', { ...this.newForm, label });

        if (data?.created) {
            // Select what was just added — almost always the intent.
            this.selected = data.created;
            this.newForm = { label: '', color: 'green', motif: 'book' };
        }
    },

    startEdit(option) {
        this.editing = option.key;
        this.form = { label: option.label, color: option.color, motif: option.motif };
    },

    cancelEdit() {
        this.editing = null;
    },

    async save(option) {
        const label = this.form.label.trim();
        if (!label) return;

        const data = await this.request(`${config.endpoint}/${option.key}`, 'PUT', {
            ...this.form,
            label,
        });

        if (data) this.editing = null;
    },

    async remove(option) {
        const plural = option.articles === 1 ? 'άρθρο' : 'άρθρα';

        // Say plainly which of the two things is about to happen — the
        // distinction (delete vs withdraw) is the whole point.
        const warning = option.articles > 0
            ? `Η κατηγορία «${option.label}» χρησιμοποιείται από ${option.articles} ${plural}.

Δεν θα διαγραφεί και τα άρθρα δεν θα αλλάξουν — θα αποσυρθεί, ώστε να μην προσφέρεται σε νέα άρθρα.

Συνέχεια;`
            : `Διαγραφή της κατηγορίας «${option.label}»;`;

        if (!window.confirm(warning)) return;

        await this.request(`${config.endpoint}/${option.key}`, 'DELETE');

        // If the selected one was deleted outright, clear the field.
        if (!this.options.some((o) => o.key === this.selected)) this.selected = '';
    },

    async restore(option) {
        await this.request(`${config.endpoint}/${option.key}/restore`, 'POST');
    },
}));


/**
 * Live Google-result preview for the SEO fields.
 *
 * Authors could previously type a twelve-character title with no way of
 * knowing it was too short, or a 400-character description that Google would
 * simply cut. This shows the snippet as it will appear, counts against the
 * windows Google actually displays, and says when a field is falling back to
 * the article title or excerpt.
 */
Alpine.data('seoPreview', (config) => ({
    shownTitle: '',
    shownDescription: '',
    titleLength: 0,
    descriptionLength: 0,
    usingFallbackTitle: false,
    usingFallbackDescription: false,

    init() {
        const names = [config.title, config.description, config.fallbackTitle, config.fallbackDescription];

        names.filter(Boolean).forEach((name) => {
            const el = this.field(name);
            if (el) el.addEventListener('input', () => this.refresh());
        });

        this.refresh();
    },

    field(name) {
        return name ? document.querySelector(`[name="${CSS.escape(name)}"]`) : null;
    },

    value(name) {
        return (this.field(name)?.value ?? '').trim();
    },

    refresh() {
        const title = this.value(config.title);
        const fallbackTitle = this.value(config.fallbackTitle);

        this.usingFallbackTitle = !title && !!fallbackTitle;
        this.shownTitle = title || (fallbackTitle ? fallbackTitle + (config.suffix || '') : '');
        this.titleLength = this.shownTitle.length;

        const description = this.value(config.description);
        const fallbackDescription = this.value(config.fallbackDescription);

        this.usingFallbackDescription = !description && !!fallbackDescription;
        this.shownDescription = description || fallbackDescription;
        this.descriptionLength = this.shownDescription.length;
    },

    verdict(length, min, max) {
        if (length === 0) return 'is-empty';
        if (length < min) return 'is-short';
        if (length > max) return 'is-long';

        return 'is-good';
    },
}));


/**
 * A list of things somebody builds: add, remove, and drag into order.
 *
 * The row indexes in the field names are renumbered after every change, so
 * what arrives at the server is a plain 0..n array in the order shown. Any
 * other scheme — keeping ids, sending a separate order list — puts two
 * sources of truth in one form.
 *
 * Dragging is armed by the handle rather than making the whole row
 * draggable: a row full of inputs that is itself draggable cannot be
 * text-selected, and selecting text is most of what happens in here.
 */
Alpine.data('collectionField', () => ({
    dragging: null,

    rows() {
        return Array.from(this.$refs.list.children);
    },

    title(el) {
        const name = el.closest('.a-collection-row').querySelector('input[name$="[name]"]');

        return (name && name.value.trim()) || 'Χωρίς όνομα';
    },

    /* ---- add / remove ---- */

    add() {
        const row = this.$refs.blank.content.firstElementChild.cloneNode(true);
        this.$refs.list.appendChild(row);
        this.renumber();

        const first = row.querySelector('input, textarea');
        if (first) first.focus();
    },

    remove(event) {
        const row = event.target.closest('.a-collection-row');
        const name = this.title(row.querySelector('.a-collection-head'));

        if (!window.confirm(`Να αφαιρεθεί «${name}»; Θα φύγει και από το site.`)) return;

        row.remove();
        this.renumber();
    },

    /* ---- ordering ---- */

    arm(event) {
        event.target.closest('.a-collection-row').setAttribute('draggable', 'true');
    },

    disarm(event) {
        const row = event.target.closest('.a-collection-row');
        if (row) row.setAttribute('draggable', 'false');
    },

    dragStart(event) {
        this.dragging = event.currentTarget;
        this.dragging.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        // Firefox will not start a drag without something on the transfer.
        event.dataTransfer.setData('text/plain', '');
    },

    dragOver(event) {
        if (!this.dragging) return;

        const over = event.currentTarget;
        if (over === this.dragging) return;

        const box = over.getBoundingClientRect();
        const below = event.clientY > box.top + box.height / 2;

        this.$refs.list.insertBefore(this.dragging, below ? over.nextSibling : over);
    },

    drop() {
        this.renumber();
    },

    dragEnd() {
        if (!this.dragging) return;

        this.dragging.classList.remove('is-dragging');
        this.dragging.setAttribute('draggable', 'false');
        this.dragging = null;
        this.renumber();
    },

    /* Keyboard: the handle takes the arrow keys, so ordering does not need a
       mouse. */
    move(event, by) {
        const row = event.target.closest('.a-collection-row');
        const rows = this.rows();
        const to = rows.indexOf(row) + by;

        if (to < 0 || to >= rows.length) return;

        if (by < 0) {
            this.$refs.list.insertBefore(row, rows[to]);
        } else {
            this.$refs.list.insertBefore(row, rows[to].nextSibling);
        }

        this.renumber();
        event.target.focus();
    },

    renumber() {
        this.rows().forEach((row, i) => {
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/\[(?:\d+|__NEW__)\]/, `[${i}]`);
            });
            row.querySelectorAll('[id]').forEach((el) => {
                el.removeAttribute('id');
            });
            row.querySelectorAll('label[for]').forEach((el) => {
                el.removeAttribute('for');
            });
        });
    },
}));

/* Rows of a table put in order by hand.
 *
 * Same drag as the collection field, but the rows are database records rather
 * than inputs in a form, so nothing is renumbered — the order is read off the
 * DOM when the save bar is submitted. The bar only appears once the order
 * actually differs from the one that was loaded, so a stray drag that lands
 * back where it started does not ask to be saved.
 */
Alpine.data('sortableRows', () => ({
    dragging: null,
    saved: [],
    dirty: false,

    init() {
        this.saved = this.ids();
    },

    rows() {
        return Array.from(this.$refs.list.children);
    },

    ids() {
        return this.rows().map((row) => row.dataset.id);
    },

    check() {
        const now = this.ids();
        this.dirty = now.length !== this.saved.length || now.some((id, i) => id !== this.saved[i]);
    },

    reset() {
        const list = this.$refs.list;
        const byId = new Map(this.rows().map((row) => [row.dataset.id, row]));

        this.saved.forEach((id) => {
            const row = byId.get(id);
            if (row) list.appendChild(row);
        });

        this.check();
    },

    /* ---- dragging ---- */

    arm(event) {
        event.target.closest('tr').setAttribute('draggable', 'true');
    },

    disarm(event) {
        const row = event.target.closest('tr');
        if (row) row.setAttribute('draggable', 'false');
    },

    dragStart(event) {
        this.dragging = event.currentTarget;
        this.dragging.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
        // Firefox will not start a drag without something on the transfer.
        event.dataTransfer.setData('text/plain', '');
    },

    dragOver(event) {
        if (!this.dragging) return;

        const over = event.currentTarget;
        if (over === this.dragging) return;

        const box = over.getBoundingClientRect();
        const below = event.clientY > box.top + box.height / 2;

        this.$refs.list.insertBefore(this.dragging, below ? over.nextSibling : over);
    },

    drop() {
        this.check();
    },

    dragEnd() {
        if (!this.dragging) return;

        this.dragging.classList.remove('is-dragging');
        this.dragging.setAttribute('draggable', 'false');
        this.dragging = null;
        this.check();
    },

    /* Keyboard: the handle takes the arrow keys, so ordering does not need a
       mouse. */
    move(event, by) {
        const row = event.target.closest('tr');
        const rows = this.rows();
        const to = rows.indexOf(row) + by;

        if (to < 0 || to >= rows.length) return;

        if (by < 0) {
            this.$refs.list.insertBefore(row, rows[to]);
        } else {
            this.$refs.list.insertBefore(row, rows[to].nextSibling);
        }

        this.check();
        event.target.focus();
    },
}));

Alpine.start();

/* ------------------------------------------------------------------ Trix */

/* Greek toolbar. Trix ships English only. */
Object.assign(Trix.config.lang, {
    bold: 'Έντονα',
    bullets: 'Κουκκίδες',
    byte: 'byte',
    bytes: 'bytes',
    captionPlaceholder: 'Λεζάντα…',
    code: 'Κώδικας',
    heading1: 'Επικεφαλίδα',
    indent: 'Αύξηση εσοχής',
    italic: 'Πλάγια',
    link: 'Σύνδεσμος',
    numbers: 'Αρίθμηση',
    outdent: 'Μείωση εσοχής',
    quote: 'Παράθεση',
    redo: 'Επανάληψη',
    strike: 'Διαγραφή',
    undo: 'Αναίρεση',
    unlink: 'Αφαίρεση συνδέσμου',
    urlPlaceholder: 'Εισαγάγετε διεύθυνση…',
});

/* The design has no styling for <h1> inside prose, and the sanitiser
   allowlists h2/h3/h4 — so map Trix's single heading button to <h2>. */
Trix.config.blockAttributes.heading1.tagName = 'h2';

/* Attachments off.

   By default Trix converts a pasted or dropped image into a blob: URL that
   dies with the page, and App\Support\HtmlSanitizer strips <img> on save
   anyway. Silently losing a user's image is worse than refusing it, so refuse
   it and say why. Images belong in the dedicated image fields. */
document.addEventListener('trix-file-accept', (event) => {
    event.preventDefault();
    window.alert(
        'Οι εικόνες δεν μπαίνουν μέσα στο κείμενο. ' +
        'Χρησιμοποιήστε το πεδίο «Εξώφυλλο» ή τη Gallery.'
    );
});
