@props([
    // The author whose photo is being framed.
    'author',
])

@php $media = $author->exists ? $author->getFirstMedia('photo') : null; @endphp

@if ($media)
    {{-- The window stays put and the picture moves behind it: what is inside
         the window is what the site shows, at every size. --}}
    <div class="a-field" x-data="photoCrop()" x-ref="root"
         data-crop-x="{{ (int) $author->crop_x }}"
         data-crop-y="{{ (int) $author->crop_y }}"
         data-crop-w="{{ (int) $author->crop_w }}"
         data-crop-h="{{ (int) $author->crop_h }}">

        <label class="a-label">Ποιο κομμάτι θα φαίνεται</label>

        <div class="a-crop">
            <div class="a-crop-stage" x-ref="stage"
                 tabindex="0" role="application"
                 aria-label="Σύρετε τη φωτογραφία για να διαλέξετε τι θα φαίνεται"
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

            <div class="a-crop-side">
                <p class="a-crop-note">Σύρετέ την με το ποντίκι — ή με τα βελάκια.</p>

                <label class="a-crop-zoom">
                    <span>Μεγέθυνση</span>
                    <input type="range" min="100" max="300" step="1"
                           x-model.number="zoom" @input="clampAndSync()">
                </label>

                <div class="a-crop-previews">
                    <figure>
                        <span class="a-crop-preview is-round" x-ref="roundPreview"></span>
                        <figcaption>Δίπλα σε άρθρο</figcaption>
                    </figure>
                    <figure>
                        <span class="a-crop-preview is-arch" x-ref="archPreview"></span>
                        <figcaption>Στην ομάδα</figcaption>
                    </figure>
                </div>

                <button type="button" class="a-btn a-btn-ghost a-btn-sm" @click="reset()">
                    Επαναφορά
                </button>
            </div>
        </div>

        <input type="hidden" name="crop_x" x-ref="x" value="{{ (int) $author->crop_x }}">
        <input type="hidden" name="crop_y" x-ref="y" value="{{ (int) $author->crop_y }}">
        <input type="hidden" name="crop_w" x-ref="w" value="{{ (int) $author->crop_w }}">
        <input type="hidden" name="crop_h" x-ref="h" value="{{ (int) $author->crop_h }}">

        <span class="a-hint">
            Η φωτογραφία μένει ολόκληρη όπως την ανεβάσατε — αλλάζει μόνο ποιο
            κομμάτι της δείχνει το site, και μπορείτε να το αλλάξετε όποτε θέλετε.
        </span>
    </div>
@endif
