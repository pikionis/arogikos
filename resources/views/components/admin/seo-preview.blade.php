@props([
    // Field NAMES, not ids — the page form generates ids from the field key,
    // and names are the one thing both forms agree on.
    'titleName',
    'descriptionName',
    // Optional fields to fall back to when the SEO ones are left blank, so the
    // preview shows what a searcher would really see.
    'fallbackTitleName' => null,
    'fallbackDescriptionName' => null,
    'titleSuffix' => '',
    'url' => null,
])

<div class="a-seo-preview"
     x-data="seoPreview({
        title: @js($titleName),
        description: @js($descriptionName),
        fallbackTitle: @js($fallbackTitleName),
        fallbackDescription: @js($fallbackDescriptionName),
        suffix: @js($titleSuffix),
     })">

    <div class="a-seo-serp">
        <div class="a-seo-serp-url">{{ $url ?? url('/') }}</div>
        <div class="a-seo-serp-title" x-text="shownTitle || 'Ο τίτλος της σελίδας'"></div>
        <div class="a-seo-serp-desc" x-text="shownDescription || 'Η περιγραφή που θα διαβάσει ο κόσμος στα αποτελέσματα.'"></div>
    </div>

    <div class="a-seo-meters">
        <div>
            <span class="a-seo-meter-label">Τίτλος</span>
            <span class="a-seo-meter" :class="verdict(titleLength, 50, 60)">
                <span x-text="titleLength"></span> / 50–60
            </span>
            <template x-if="usingFallbackTitle">
                <span class="a-seo-note">από τον τίτλο του άρθρου</span>
            </template>
        </div>
        <div>
            <span class="a-seo-meter-label">Περιγραφή</span>
            <span class="a-seo-meter" :class="verdict(descriptionLength, 150, 160)">
                <span x-text="descriptionLength"></span> / 150–160
            </span>
            <template x-if="usingFallbackDescription">
                <span class="a-seo-note">από την περίληψη</span>
            </template>
        </div>
    </div>

    <p class="a-seo-hint">
        Έτσι περίπου θα φαίνεται στη Google. Πιο μακρύ κείμενο <strong>κόβεται</strong> —
        δεν βοηθά στην κατάταξη, απλώς χάνεται. Γράψτε τι θα κάνει κάποιον να πατήσει,
        και αναφέρετε την <strong>Κω</strong>.
    </p>
</div>
