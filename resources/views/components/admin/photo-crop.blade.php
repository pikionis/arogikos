@props([
    // The author whose photo is being framed.
    'author',
])

@php $media = $author->exists ? $author->getFirstMedia('photo') : null; @endphp

@if ($media)
    <div class="a-field">
        <label class="a-label">Ποιο κομμάτι θα φαίνεται</label>
        <span class="a-hint" style="margin-bottom:14px">
            Το site δείχνει τη φωτογραφία σε δύο σχήματα, οπότε διαλέγετε
            ξεχωριστά για το καθένα. Σύρετε τη φωτογραφία μέσα στο πλαίσιο —
            ή με τα βελάκια — και μεγεθύνετε με τη μπάρα.
        </span>

        <div class="a-crops">
            @foreach (App\Models\Cms\Author::FRAMES as $frame => $spec)
                {{-- The window stays put and the picture moves behind it: what
                     is inside the window is what the site shows. --}}
                <div class="a-crop" x-data="photoCrop({{ $spec['ratio'] }})">
                    <p class="a-crop-title">{{ $spec['label'] }}</p>

                    <div class="a-crop-stage {{ $frame === 'circle' ? 'is-round' : 'is-arch' }}"
                         x-ref="stage"
                         style="aspect-ratio:{{ $spec['ratio'] }}"
                         tabindex="0" role="application"
                         aria-label="Σύρετε τη φωτογραφία: {{ $spec['label'] }}"
                         @pointerdown="grab($event)"
                         @pointermove="drag($event)"
                         @pointerup="release($event)"
                         @pointercancel="release($event)"
                         @keydown.arrow-left.prevent="nudge(-1, 0)"
                         @keydown.arrow-right.prevent="nudge(1, 0)"
                         @keydown.arrow-up.prevent="nudge(0, -1)"
                         @keydown.arrow-down.prevent="nudge(0, 1)">
                        <img x-ref="image" src="{{ $media->getUrl() }}" alt="" draggable="false">
                    </div>

                    <label class="a-crop-zoom">
                        <span class="a-sr">Μεγέθυνση: {{ $spec['label'] }}</span>
                        <input type="range" min="100" max="300" step="1"
                               x-model.number="zoom" @input="clampAndSync()">
                    </label>

                    <button type="button" class="a-btn a-btn-ghost a-btn-sm" @click="reset()">
                        Επαναφορά
                    </button>

                    <input type="hidden" name="{{ $frame }}_x" x-ref="x" value="{{ (int) $author->{$frame.'_x'} }}">
                    <input type="hidden" name="{{ $frame }}_y" x-ref="y" value="{{ (int) $author->{$frame.'_y'} }}">
                    <input type="hidden" name="{{ $frame }}_w" x-ref="w" value="{{ (int) $author->{$frame.'_w'} }}">
                    <input type="hidden" name="{{ $frame }}_h" x-ref="h" value="{{ (int) $author->{$frame.'_h'} }}">
                </div>
            @endforeach
        </div>

        <span class="a-hint">
            Η φωτογραφία μένει ολόκληρη όπως την ανεβάσατε — αλλάζει μόνο ποιο
            κομμάτι της δείχνει το site, και μπορείτε να το αλλάξετε όποτε θέλετε.
        </span>
    </div>
@endif
