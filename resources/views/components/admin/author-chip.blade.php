@props(['author'])

{{-- Name, role and face: a list of bare names asks whoever is signing an
     article to remember which Μαρία is which. --}}
<span class="a-signer-face">
    @php $photo = $author->photoUrl('circle'); @endphp
    @if ($photo)
        <img src="{{ $photo }}" alt="" width="28" height="28" loading="lazy" decoding="async">
    @else
        <span aria-hidden="true">{{ $author->initials() }}</span>
    @endif
</span>
<span>
    <strong>{{ $author->name }}</strong>
    @if ($author->role_title)
        <span class="a-signer-role">{{ $author->role_title }}</span>
    @endif
</span>
