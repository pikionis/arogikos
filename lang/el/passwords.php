<?php

declare(strict_types=1);

return [

    'reset' => 'Ο κωδικός πρόσβασης άλλαξε.',
    'sent' => 'Αν υπάρχει λογαριασμός με αυτό το email, σας στείλαμε σύνδεσμο επαναφοράς.',
    'throttled' => 'Περιμένετε λίγο πριν δοκιμάσετε ξανά.',
    'token' => 'Ο σύνδεσμος επαναφοράς δεν είναι έγκυρος ή έχει λήξει.',

    // Same wording as 'sent' on purpose — a different message here would leak
    // which addresses have accounts.
    'user' => 'Αν υπάρχει λογαριασμός με αυτό το email, σας στείλαμε σύνδεσμο επαναφοράς.',

];
