@props([
    // The article's current choice, or null.
    'selected' => null,
])

@php
    $scenes = config('cms-covers', []);
@endphp

<fieldset class="a-scenes">
    <legend class="a-scenes-legend">Ή διαλέξτε έτοιμο σχέδιο</legend>

    <p class="a-hint" style="margin:0 0 14px">
        Αν ανεβάσετε φωτογραφία, εκείνη υπερισχύει. Το σχέδιο κινείται στη σελίδα
        του άρθρου· στη λίστα του blog μένει ακίνητο.
    </p>

    <div class="a-scene-grid">
        <label @class(['a-scene', 'is-on' => blank($selected)])>
            <input type="radio" name="cover_preset" value="" @checked(blank($selected))>
            <span class="a-scene-art a-scene-none" aria-hidden="true"></span>
            <span class="a-scene-name">Κανένα</span>
            <span class="a-scene-for">Το σήμα της κατηγορίας</span>
        </label>

        @foreach ($scenes as $key => $scene)
            <label @class(['a-scene', 'is-on' => $selected === $key])>
                <input type="radio" name="cover_preset" value="{{ $key }}" @checked($selected === $key)>
                <span class="a-scene-art">
                    <x-article-cover :scene="$key" />
                </span>
                <span class="a-scene-name">{{ $scene['name'] }}</span>
                <span class="a-scene-for">{{ $scene['for'] }}</span>
            </label>
        @endforeach
    </div>

    @error('cover_preset')<span class="a-error">{{ $message }}</span>@enderror
</fieldset>
