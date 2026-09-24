@props([
    'categories' => [],
    'selected' => null,
])

@php
    use App\Models\Cms\Category;

    // Shape the server-rendered list the same way the JSON endpoint does, so
    // the Alpine component can take over without a reload.
    $initial = collect($categories)->map(fn (array $meta, string $key) => [
        'key' => $key,
        'label' => $meta['label'],
        'color' => $meta['color'],
        'motif' => $meta['motif'],
        'archived' => $meta['archived'] ?? false,
    ])->values()->all();
@endphp

<div class="a-field" style="margin-bottom:0"
     x-data="categoryPicker({
        endpoint: @js(route('admin.categories.index')),
        options: @js($initial),
        selected: @js((string) old('category', $selected)),
        colors: @js(Category::COLORS),
        motifs: @js(Category::MOTIFS),
     })">

    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:8px">
        <label class="a-label" for="category">Κατηγορία</label>
        <button type="button" class="a-btn a-btn-ghost a-btn-sm"
                style="padding:3px 9px;font-size:.78rem"
                @click="toggle()"
                :aria-expanded="open.toString()">
            <span x-text="open ? 'Κλείσιμο' : 'Διαχείριση'"></span>
        </button>
    </div>

    {{-- The picker itself. Options are re-rendered by Alpine after any edit. --}}
    <select class="a-select" id="category" name="category" x-model="selected">
        <option value="">— Χωρίς κατηγορία —</option>
        <template x-for="option in options" :key="option.key">
            <option :value="option.key"
                    x-text="option.label + (option.archived ? ' (αποσυρμένη)' : '')"></option>
        </template>
    </select>

    @error('category')<span class="a-error">{{ $message }}</span>@enderror

    {{-- ---------------------------------------------------- manage panel --}}
    <div x-show="open" x-cloak x-transition.opacity
         style="margin-top:12px;border:1px solid var(--admin-border);border-radius:9px;background:var(--admin-bg);padding:14px">

        <template x-if="message">
            <div class="a-alert" :class="error ? 'a-alert-err' : 'a-alert-ok'"
                 style="margin-bottom:12px;font-size:.84rem" x-text="message"></div>
        </template>

        {{-- existing categories --}}
        <div style="display:grid;gap:8px;margin-bottom:14px">
            <template x-for="option in options" :key="option.key">
                <div style="background:#fff;border:1px solid var(--admin-border);border-radius:8px;padding:10px">

                    {{-- read mode --}}
                    <div x-show="editing !== option.key"
                         style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                        <div style="flex:none;width:44px;height:29px;border-radius:4px;background:var(--color-soft-green);display:grid;place-items:center;padding:2px"
                             x-motif="option.motif"></div>

                        <span class="a-badge"
                              :class="{
                                'a-badge-green': option.color === 'green',
                                'a-badge-coral': option.color === 'coral',
                                'a-badge-yellow': option.color === 'yellow',
                              }"
                              x-text="option.label"></span>

                        <span style="color:var(--color-ink-400);font-size:.78rem"
                              x-text="option.articles + (option.articles === 1 ? ' άρθρο' : ' άρθρα')"></span>

                        <template x-if="option.archived">
                            <span class="a-badge a-badge-grey">αποσυρμένη</span>
                        </template>

                        <span style="margin-left:auto;display:flex;gap:6px">
                            <button type="button" class="a-btn a-btn-ghost a-btn-sm"
                                    style="padding:3px 9px;font-size:.78rem"
                                    @click="startEdit(option)">Επεξεργασία</button>

                            <template x-if="!option.archived">
                                <button type="button" class="a-btn a-btn-danger a-btn-sm"
                                        style="padding:3px 9px;font-size:.78rem"
                                        :disabled="busy"
                                        @click="remove(option)">Διαγραφή</button>
                            </template>

                            <template x-if="option.archived">
                                <button type="button" class="a-btn a-btn-ghost a-btn-sm"
                                        style="padding:3px 9px;font-size:.78rem"
                                        :disabled="busy"
                                        @click="restore(option)">Επαναφορά</button>
                            </template>
                        </span>
                    </div>

                    {{-- edit mode --}}
                    <div x-show="editing === option.key" x-cloak style="display:grid;gap:8px">
                        <input class="a-input" type="text" maxlength="60" x-model="form.label"
                               placeholder="Όνομα κατηγορίας" style="font-size:.86rem">

<div style="display:flex;align-items:center;gap:10px">
                            <div style="flex:none;width:72px;height:47px;border-radius:6px;background:var(--color-soft-green);display:grid;place-items:center;padding:4px"
                                 x-motif="form.motif"></div>
                            <div style="flex:1;display:grid;gap:8px">
                                <select class="a-select" x-model="form.color" style="font-size:.86rem">
                                    <template x-for="(name, value) in colors" :key="value">
                                        <option :value="value" x-text="name"></option>
                                    </template>
                                </select>
                                <select class="a-select" x-model="form.motif" style="font-size:.86rem">
                                    <template x-for="(name, value) in motifs" :key="value">
                                        <option :value="value" x-text="name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div style="display:flex;gap:6px">
                            <button type="button" class="a-btn a-btn-primary a-btn-sm"
                                    :disabled="busy" @click="save(option)">Αποθήκευση</button>
                            <button type="button" class="a-btn a-btn-ghost a-btn-sm"
                                    @click="cancelEdit()">Άκυρο</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- add new --}}
        <div style="border-top:1px solid var(--admin-border);padding-top:12px">
            <div style="font-weight:700;font-size:.84rem;margin-bottom:8px">Νέα κατηγορία</div>

            <div style="display:grid;gap:8px">
                <input class="a-input" type="text" maxlength="60" x-model="newForm.label"
                       placeholder="π.χ. Εφηβεία" style="font-size:.86rem"
                       @keydown.enter.prevent="create()">

                <div style="display:flex;align-items:center;gap:10px">
                    <div style="flex:none;width:72px;height:47px;border-radius:6px;background:var(--color-soft-green);display:grid;place-items:center;padding:4px"
                         x-motif="newForm.motif"
                         title="Έτσι θα φαίνεται ένα άρθρο χωρίς εξώφυλλο"></div>
                    <div style="flex:1;display:grid;gap:8px">
                        <select class="a-select" x-model="newForm.color" style="font-size:.86rem">
                            <template x-for="(name, value) in colors" :key="value">
                                <option :value="value" x-text="name"></option>
                            </template>
                        </select>
                        <select class="a-select" x-model="newForm.motif" style="font-size:.86rem">
                            <template x-for="(name, value) in motifs" :key="value">
                                <option :value="value" x-text="name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <button type="button" class="a-btn a-btn-primary a-btn-sm"
                        :disabled="busy || !newForm.label.trim()"
                        @click="create()">+ Προσθήκη</button>
            </div>

            <p style="color:var(--color-ink-400);font-size:.78rem;margin:10px 0 0;line-height:1.5">
                Το <strong>χρώμα</strong> βάφει την ετικέτα της κατηγορίας στην κάρτα του άρθρου.
                Το <strong>σχέδιο</strong> μπαίνει στη θέση της φωτογραφίας όταν το άρθρο
                <strong>δεν έχει εξώφυλλο</strong> — δείτε το δίπλα στις επιλογές.<br>
                Κατηγορία που <strong>χρησιμοποιείται από άρθρα</strong> δεν διαγράφεται —
                αποσύρεται: παύει να προσφέρεται σε νέα άρθρα, αλλά όσα την έχουν ήδη
                μένουν ακριβώς όπως είναι.
            </p>
        </div>
    </div>
</div>
