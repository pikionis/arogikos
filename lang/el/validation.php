<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Greek validation messages
|--------------------------------------------------------------------------
| Partial on purpose. APP_FALLBACK_LOCALE=en, so any key missing here falls
| back to Laravel's English string — we only translate the rules this app
| actually uses (the contact form and the whole admin panel).
*/

return [

    'accepted' => 'Το πεδίο :attribute πρέπει να γίνει αποδεκτό.',
    'active_url' => 'Το πεδίο :attribute δεν είναι έγκυρη διεύθυνση.',
    'after' => 'Το πεδίο :attribute πρέπει να είναι ημερομηνία μετά τις :date.',
    'after_or_equal' => 'Το πεδίο :attribute πρέπει να είναι ημερομηνία μετά ή ίση με :date.',
    'alpha' => 'Το πεδίο :attribute επιτρέπεται να περιέχει μόνο γράμματα.',
    'alpha_dash' => 'Το πεδίο :attribute επιτρέπεται να περιέχει μόνο γράμματα, αριθμούς, παύλες και κάτω παύλες.',
    'alpha_num' => 'Το πεδίο :attribute επιτρέπεται να περιέχει μόνο γράμματα και αριθμούς.',
    'array' => 'Το πεδίο :attribute πρέπει να είναι λίστα.',
    'before' => 'Το πεδίο :attribute πρέπει να είναι ημερομηνία πριν τις :date.',
    'before_or_equal' => 'Το πεδίο :attribute πρέπει να είναι ημερομηνία πριν ή ίση με :date.',
    'boolean' => 'Το πεδίο :attribute πρέπει να είναι ναι ή όχι.',
    'confirmed' => 'Η επιβεβαίωση του πεδίου :attribute δεν ταιριάζει.',
    'current_password' => 'Ο κωδικός πρόσβασης δεν είναι σωστός.',
    'date' => 'Το πεδίο :attribute δεν είναι έγκυρη ημερομηνία.',
    'date_format' => 'Το πεδίο :attribute δεν ταιριάζει με τη μορφή :format.',
    'different' => 'Τα πεδία :attribute και :other πρέπει να διαφέρουν.',

    'dimensions' => 'Η εικόνα :attribute έχει μη έγκυρες διαστάσεις.',

    'email' => 'Το πεδίο :attribute πρέπει να είναι έγκυρη διεύθυνση email.',
    'ends_with' => 'Το πεδίο :attribute πρέπει να τελειώνει σε ένα από τα: :values.',
    'exists' => 'Η επιλογή για το πεδίο :attribute δεν είναι έγκυρη.',
    'file' => 'Το πεδίο :attribute πρέπει να είναι αρχείο.',
    'filled' => 'Το πεδίο :attribute δεν μπορεί να είναι κενό.',
    'image' => 'Το πεδίο :attribute πρέπει να είναι εικόνα.',
    'in' => 'Η επιλογή για το πεδίο :attribute δεν είναι έγκυρη.',
    'integer' => 'Το πεδίο :attribute πρέπει να είναι ακέραιος αριθμός.',
    'mimes' => 'Το πεδίο :attribute πρέπει να είναι αρχείο τύπου: :values.',
    'mimetypes' => 'Το πεδίο :attribute πρέπει να είναι αρχείο τύπου: :values.',
    'not_in' => 'Η επιλογή για το πεδίο :attribute δεν είναι έγκυρη.',
    'numeric' => 'Το πεδίο :attribute πρέπει να είναι αριθμός.',
    'present' => 'Το πεδίο :attribute πρέπει να υπάρχει.',
    'prohibited' => 'Το πεδίο :attribute δεν επιτρέπεται.',
    'regex' => 'Η μορφή του πεδίου :attribute δεν είναι έγκυρη.',
    'required' => 'Το πεδίο :attribute είναι υποχρεωτικό.',
    'required_if' => 'Το πεδίο :attribute είναι υποχρεωτικό όταν το :other είναι :value.',
    'required_with' => 'Το πεδίο :attribute είναι υποχρεωτικό όταν υπάρχει :values.',
    'required_without' => 'Το πεδίο :attribute είναι υποχρεωτικό όταν δεν υπάρχει :values.',
    'same' => 'Τα πεδία :attribute και :other πρέπει να ταυτίζονται.',
    'string' => 'Το πεδίο :attribute πρέπει να είναι κείμενο.',
    'timezone' => 'Το πεδίο :attribute πρέπει να είναι έγκυρη ζώνη ώρας.',
    'unique' => 'Αυτό το :attribute χρησιμοποιείται ήδη.',
    'uploaded' => 'Η μεταφόρτωση του :attribute απέτυχε. Δοκιμάστε μικρότερο αρχείο.',
    'url' => 'Το πεδίο :attribute πρέπει να είναι έγκυρη διεύθυνση.',

    'min' => [
        'array' => 'Το πεδίο :attribute πρέπει να έχει τουλάχιστον :min στοιχεία.',
        'file' => 'Το αρχείο :attribute πρέπει να είναι τουλάχιστον :min kilobytes.',
        'numeric' => 'Το πεδίο :attribute πρέπει να είναι τουλάχιστον :min.',
        'string' => 'Το πεδίο :attribute πρέπει να έχει τουλάχιστον :min χαρακτήρες.',
    ],

    'max' => [
        'array' => 'Το πεδίο :attribute δεν μπορεί να έχει πάνω από :max στοιχεία.',
        'file' => 'Το αρχείο :attribute δεν μπορεί να ξεπερνά τα :max kilobytes.',
        'numeric' => 'Το πεδίο :attribute δεν μπορεί να είναι μεγαλύτερο από :max.',
        'string' => 'Το πεδίο :attribute δεν μπορεί να ξεπερνά τους :max χαρακτήρες.',
    ],

    'between' => [
        'array' => 'Το πεδίο :attribute πρέπει να έχει από :min έως :max στοιχεία.',
        'file' => 'Το αρχείο :attribute πρέπει να είναι από :min έως :max kilobytes.',
        'numeric' => 'Το πεδίο :attribute πρέπει να είναι από :min έως :max.',
        'string' => 'Το πεδίο :attribute πρέπει να έχει από :min έως :max χαρακτήρες.',
    ],

    'size' => [
        'array' => 'Το πεδίο :attribute πρέπει να έχει :size στοιχεία.',
        'file' => 'Το αρχείο :attribute πρέπει να είναι :size kilobytes.',
        'numeric' => 'Το πεδίο :attribute πρέπει να είναι :size.',
        'string' => 'Το πεδίο :attribute πρέπει να έχει :size χαρακτήρες.',
    ],

    'password' => [
        'letters' => 'Ο κωδικός πρέπει να περιέχει τουλάχιστον ένα γράμμα.',
        'mixed' => 'Ο κωδικός πρέπει να περιέχει τουλάχιστον ένα κεφαλαίο και ένα πεζό γράμμα.',
        'numbers' => 'Ο κωδικός πρέπει να περιέχει τουλάχιστον έναν αριθμό.',
        'symbols' => 'Ο κωδικός πρέπει να περιέχει τουλάχιστον ένα σύμβολο.',
        'uncompromised' => 'Αυτός ο κωδικός έχει εμφανιστεί σε διαρροή δεδομένων. Επιλέξτε διαφορετικό.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom attribute names
    |--------------------------------------------------------------------------
    | Field names as they read inside a Greek sentence. The contact form
    | overrides its own in app/Http/Requests/ContactRequest.php.
    */

    'attributes' => [
        'name' => 'όνομα',
        'email' => 'email',
        'password' => 'κωδικός πρόσβασης',
        'password_confirmation' => 'επιβεβαίωση κωδικού',
        'current_password' => 'τρέχων κωδικός',
        'phone' => 'τηλέφωνο',
        'message' => 'μήνυμα',
        'role' => 'ρόλος',
        'title' => 'τίτλος',
        'slug' => 'slug',
        'excerpt' => 'περίληψη',
        'body' => 'κείμενο',
        'category' => 'κατηγορία',
        'author_id' => 'συντάκτης',
        'published_at' => 'ημερομηνία δημοσίευσης',
        'is_published' => 'δημοσιευμένο',
        'is_active' => 'ενεργό',
        'seo_title' => 'τίτλος SEO',
        'seo_description' => 'περιγραφή SEO',
        'cover' => 'εξώφυλλο',
        'photo' => 'φωτογραφία',
        'image' => 'εικόνα',
        'caption' => 'λεζάντα',
        'starts_at' => 'ημερομηνία έναρξης',
        'location' => 'τοποθεσία',
        'description' => 'περιγραφή',
        'sort_order' => 'σειρά',
        'role_title' => 'ιδιότητα',
        'bio' => 'βιογραφικό',
        'album_id' => 'άλμπουμ',
    ],

    'custom' => [],

];
