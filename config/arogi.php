<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Arogi — site-specific configuration
|--------------------------------------------------------------------------
| Per the cms-core rules, nothing client-specific lives in the package.
| Article categories (their Greek labels, brand pill colour, and the
| branded SVG motif used as a cover fallback) are an Arogi concern, so
| they live here. The blog filter chips and the category pills both read
| from this map, keeping them in sync.
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Το stylesheet μέσα στη σελίδα
    |--------------------------------------------------------------------------
    | Στην παραγωγή ναι: το κινητό ζωγραφίζει χωρίς να περιμένει αίτημα.
    | Στα τεστ όχι, γιατί 120 KB CSS μέσα σε κάθε απάντηση κάνει κάθε έλεγχο
    | «η σελίδα δεν περιέχει X» να πέφτει πάνω σε ένα όνομα κλάσης.
    */

    'inline_css' => (bool) env('AROGI_INLINE_CSS', true),

    'categories' => [
        'learning' => [
            'label' => 'Μαθησιακές δυσκολίες',
            'color' => 'green',
            'motif' => 'book',
        ],
        'counseling' => [
            'label' => 'Συμβουλευτική',
            'color' => 'coral',
            'motif' => 'one',
        ],
        'parents' => [
            'label' => 'Γονείς',
            'color' => 'yellow',
            'motif' => 'group',
        ],
        'career' => [
            'label' => 'Προσανατολισμός',
            'color' => 'green',
            'motif' => 'sun',
        ],
    ],

];
