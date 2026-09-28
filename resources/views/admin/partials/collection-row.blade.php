{{-- One row of a collection. Included both for the real rows and, once, as
     the hidden template the add button clones. --}}
<fieldset class="a-collection-row" draggable="false"
          @dragstart="dragStart($event)" @dragover.prevent="dragOver($event)"
          @drop.prevent="drop($event)" @dragend="dragEnd()">

    <div class="a-collection-head">
        {{-- The handle carries the drag, not the whole row: a row full of
             inputs that is itself draggable cannot be selected with the
             mouse, and selecting text is what people do here most. --}}
        <button type="button" class="a-drag" aria-label="Μετακίνηση"
                @mousedown="arm($event)" @mouseup="disarm($event)"
                @keydown.arrow-up.prevent="move($event, -1)"
                @keydown.arrow-down.prevent="move($event, 1)">
            <span aria-hidden="true">⠿</span>
        </button>

        <strong x-text="title($el)">{{ $item['name'] ?? '' }}</strong>

        <button type="button" class="a-collection-remove" @click="remove($event)">
            Αφαίρεση
        </button>
    </div>

    <div class="a-collection-body">
        @foreach ($sub as $s)
            @php
                $value = $item[$s['key']] ?? '';
                if ($s['type'] === 'lines' && is_array($value)) {
                    $value = implode("\n", $value);
                }
            @endphp

            <x-admin.content-field
                :field="$s"
                :value="$value"
                :name="'fields['.$field['key'].']['.$index.']['.$s['key'].']'"
                :id="$isTemplate ? null : 'f-'.Str::slug($field['key'].'-'.$index.'-'.$s['key'], '_')" />
        @endforeach
    </div>
</fieldset>
