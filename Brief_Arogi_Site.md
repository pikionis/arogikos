# Brief Ανάπτυξης — Site ΑΡΩΓΗ (Laravel app + Frontend)

## Τι χτίζουμε

Το πλήρες website της **ΑΡΩΓΗ** — κέντρου μαθησιακής υποστήριξης, συμβουλευτικής και επαγγελματικού προσανατολισμού στην Κω. Laravel application που:

1. Καταναλώνει το package **`pikionis/cms-core`** (χτίζεται παράλληλα στον φάκελο `../cms-core`) για όλο το διαχειριστικό και τα content models.
2. Υλοποιεί το **frontend** βάσει του σχεδιαστικού brief και του HTML/CSS που παράγεται από το Claude Design (θα δοθεί ως input — μετατρέπεται σε Blade templates, ΔΕΝ ξανασχεδιάζεται από το μηδέν).

Γλώσσα site: **μόνο ελληνικά**. Κοινό: γονείς (κυρίως), έφηβοι, εκπαιδευτικοί.

## Stack

- Τελευταίο stable Laravel (ίδια έκδοση με το cms-core), Vite + Tailwind.
- Blade components για το frontend — όχι SPA, όχι React/Vue. Server-rendered, γρήγορο, SEO-friendly.
- **Σύνδεση με το package στην ανάπτυξη** μέσω composer path repository:

```json
"repositories": [{ "type": "path", "url": "../cms-core" }]
```

και `composer require pikionis/cms-core:@dev`. (Σε production θα γυρίσει σε VCS + tagged version.)

## Δομή Σελίδων & Routes

| Route | Σελίδα | Πηγή δεδομένων |
|---|---|---|
| `/` | Αρχική | Articles (3 τελευταία), Events (επερχόμενα), Settings |
| `/sxetika-me-emas` | Σχετικά με εμάς | Page + Authors (η ομάδα) |
| `/ypiresies` | Υπηρεσίες | Page(s) |
| `/blog` | Λίστα άρθρων | Articles (published, paginated, φίλτρο κατηγορίας) |
| `/blog/{slug}` | Άρθρο | Article + σχετικά άρθρα |
| `/ekdiloseis` | Εκδηλώσεις | Events (επερχόμενες/προηγούμενες) |
| `/gallery` | Gallery | Albums + Photos (lightbox) |
| `/epikoinonia` | Επικοινωνία | Φόρμα + Settings + Google Maps embed |

SEO-friendly ελληνικά slugs (όπως δεσμεύεται η προσφορά: μορφή `/category/page`). Κάθε σελίδα με σωστά `<title>`/meta description από τα SEO πεδία των models. 404 σελίδα στο ύφος του brand.

## Frontend — Κανόνες Υλοποίησης

- Πιστή μεταφορά του Claude Design output σε Blade: layouts (`app.blade.php`), components (header, footer, article-card, event-card, section-cta), pages.
- **Τα animations του σχεδιαστικού brief διατηρούνται ως έχουν** (SVG/CSS): hero με τις φιγούρες και την αψίδα-πόρτα, animated εικονίδια υπηρεσιών, success animation φόρμας, fade-up στο scroll. Υποχρεωτικός σεβασμός `prefers-reduced-motion`.
- Χρωματική παλέτα/τυπογραφία ΑΚΡΙΒΩΣ όπως στο σχεδιαστικό brief (πράσινο #1E9E58 κύριο, κοραλί #E26D5A, κίτρινο #F4B62A, off-white φόντο). Ορίζονται ως Tailwind theme tokens — πουθενά αυθαίρετα χρώματα.
- Mobile-first. Εικόνες responsive μέσω των media conversions του package (`thumb`/`web`), lazy loading, σωστά alt.

## Λειτουργικότητα

- **Φόρμα επικοινωνίας:** validation server-side, honeypot anti-spam, αποθήκευση ως `ContactMessage` + αποστολή email στη διεύθυνση από Settings. Μετά την επιτυχία παίζει το success animation («το παιδάκι παραδίδει το μήνυμα»).
- **Settings-driven:** τηλέφωνα, διεύθυνση, ωράριο, social links, Google Analytics ID, email φόρμας — όλα από τον πίνακα Settings, τίποτα hard-coded.
- Sitemap.xml (spatie/laravel-sitemap) και βασικά OG meta tags.
- Caching: route/view cache σε production· οι λίστες άρθρων/events με eager loading (όχι N+1).

## Seeders

Demo περιεχόμενο για ανάπτυξη και παρουσίαση στον πελάτη: 4 συγγραφείς-εκπαιδευτικοί, 6 άρθρα με εξώφυλλα (placeholder εικόνες), 3 events, 2 άλμπουμ, βασικά settings.

## Deployment Στόχος

PHP hosting εντός ΕΕ (όρος GDPR της προσφοράς). Προετοίμασε: `.env.example` πλήρες, build script για assets (`npm run build`), οδηγίες μετάβασης του composer από path σε VCS repo με tagged version, και `php artisan cms:install` ως βήμα εγκατάστασης.

## Definition of Done

- [ ] Όλες οι σελίδες λειτουργούν με δεδομένα από το CMS (όχι static κείμενα)
- [ ] Νέο άρθρο στο /admin εμφανίζεται αμέσως στο /blog με σωστό συγγραφέα
- [ ] Φόρμα: αποθηκεύει, στέλνει email, παίζει animation
- [ ] Lighthouse: Performance & Accessibility ≥ 90 σε κινητό
- [ ] Όλα τα animations λειτουργούν και απενεργοποιούνται με prefers-reduced-motion
- [ ] Μηδέν hard-coded στοιχεία επικοινωνίας/branding

## Σειρά Υλοποίησης

1. Φρέσκο Laravel app + σύνδεση path repo με cms-core + `cms:install` τρέχει καθαρά.
2. Layout + header/footer + Tailwind tokens από το design.
3. Αρχική σελίδα πλήρης (με hero animation) πάνω σε seeded δεδομένα.
4. Blog (λίστα + άρθρο) → Events → Gallery → Σχετικά/Υπηρεσίες.
5. Επικοινωνία (φόρμα + email + animation).
6. SEO/sitemap/caching + Definition of Done έλεγχος.
