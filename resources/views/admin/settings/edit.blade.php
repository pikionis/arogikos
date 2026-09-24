@extends('admin.layouts.app')

@section('title', 'Ρυθμίσεις')
@section('heading', 'Ρυθμίσεις')
@section('subheading', 'Στοιχεία επικοινωνίας και branding — διαβάζονται από όλο το site.')

@section('content')

    @if ($errors->any())
        <div class="a-alert a-alert-err">
            <strong>Ελέγξτε τα παρακάτω:</strong>
            <ul style="margin:8px 0 0;padding-left:18px;font-weight:500">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf @method('PUT')

        <div style="display:grid;gap:18px;max-width:760px">
            @foreach ($groups as $groupKey => $group)
                <section class="a-card">
                    <h2 style="font-size:1rem;margin:0 0 4px">{{ $group['label'] }}</h2>
                    @isset($group['hint'])
                        <p style="color:var(--color-ink-400);font-size:.84rem;margin:0 0 16px">
                            {{ $group['hint'] }}
                        </p>
                    @else
                        <div style="height:12px"></div>
                    @endisset

                    @foreach ($group['fields'] as $i => $field)
                        @php
                            $key = $field['key'];
                            $id = 'setting-'.$key;
                            $value = old("settings.{$key}", $values[$key] ?? '');
                            $isLast = $i === count($group['fields']) - 1;
                        @endphp

                        <div class="a-field" @if ($isLast) style="margin-bottom:0" @endif>
                            <label class="a-label" for="{{ $id }}">{{ $field['label'] }}</label>

                            @if ($field['type'] === 'textarea')
                                <textarea class="a-textarea" id="{{ $id }}" name="settings[{{ $key }}]"
                                          style="min-height:80px"
                                          @error("settings.{$key}") aria-invalid="true" @enderror>{{ $value }}</textarea>
                            @else
                                <input class="a-input" id="{{ $id }}" name="settings[{{ $key }}]"
                                       type="{{ $field['type'] === 'text' ? 'text' : $field['type'] }}"
                                       value="{{ $value }}"
                                       @error("settings.{$key}") aria-invalid="true" @enderror>
                            @endif

                            @isset($field['hint'])
                                <span class="a-hint">{{ $field['hint'] }}</span>
                            @endisset

                            @error("settings.{$key}")<span class="a-error">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                </section>
            @endforeach

            <div>
                <button class="a-btn a-btn-primary" type="submit">Αποθήκευση ρυθμίσεων</button>
            </div>
        </div>
    </form>

@endsection
