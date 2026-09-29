{{-- Φιλίες — τρία παιδιά σε κύκλο, με ό,τι μοιράζονται στη μέση. --}}
<svg aria-hidden="true" viewBox="0 0 1600 1000" class="cv-svg">
    <rect x="0" y="0" width="1600" height="1000" fill="var(--off-white)"></rect>
    <circle class="br" cx="800" cy="510" r="420" fill="var(--soft-coral)" opacity="0.75" style="transform-origin: 800px 510px"></circle>
    <circle class="br d1" cx="1430" cy="200" r="210" fill="var(--soft-green)" style="transform-origin: 1430px 200px"></circle>
    <circle class="br d2" cx="180" cy="840" r="220" fill="var(--soft-yellow)" style="transform-origin: 180px 840px"></circle>

    <circle cx="800" cy="510" r="330" fill="none" stroke="var(--coral)" stroke-width="3" stroke-dasharray="6 22" opacity="0.3"></circle>
    <g class="orbit" style="transform-origin: 800px 510px">
      <circle cx="800" cy="180" r="15" fill="var(--yellow)" opacity="0.6"></circle>
      <circle cx="800" cy="840" r="12" fill="var(--green)" opacity="0.5"></circle>
    </g>

    {{-- Οι δεσμοί ανάμεσά τους, που ανάβουν με τη σειρά. --}}
    <g fill="none" stroke-width="16" stroke-linecap="round">
      <path class="link k1" d="M 520 460 Q 800 356 1080 460" stroke="var(--green)" opacity="0.25"></path>
      <path class="link k2" d="M 1080 460 Q 1010 700 800 760" stroke="var(--yellow)" opacity="0.25"></path>
      <path class="link k3" d="M 800 760 Q 590 700 520 460" stroke="var(--coral)" opacity="0.25"></path>
    </g>

    {{-- Ό,τι μοιράζονται στη μέση: μια μπάλα που αναπηδά. --}}
    <g class="ball" style="transform-origin: 800px 540px">
      <circle cx="800" cy="540" r="70" fill="var(--white)"></circle>
      <circle cx="800" cy="540" r="70" fill="none" stroke="var(--coral)" stroke-width="9"></circle>
      <path d="M 730 540 Q 800 486 870 540" fill="none" stroke="var(--coral)" stroke-width="7" opacity="0.7"></path>
      <path d="M 730 540 Q 800 594 870 540" fill="none" stroke="var(--coral)" stroke-width="7" opacity="0.7"></path>
    </g>

    {{-- Τρία παιδιά: κεφάλι, ώμοι σε τόξο, τίποτα άλλο. --}}
    <g class="pal p1" style="transform-origin: 520px 470px">
      <path d="M 428 560 A 92 92 0 0 1 612 560 Z" fill="var(--green)"></path>
      <circle cx="520" cy="410" r="64" fill="var(--green)"></circle>
      <circle class="halo" cx="520" cy="410" r="92" fill="none" stroke="var(--green)" stroke-width="5" opacity="0.28" style="transform-origin: 520px 410px"></circle>
    </g>

    <g class="pal p2" style="transform-origin: 1080px 470px">
      <path d="M 992 556 A 88 88 0 0 1 1168 556 Z" fill="var(--yellow)"></path>
      <circle cx="1080" cy="412" r="60" fill="var(--yellow)"></circle>
      <circle class="halo" cx="1080" cy="412" r="88" fill="none" stroke="var(--yellow)" stroke-width="5" opacity="0.32" style="transform-origin: 1080px 412px"></circle>
    </g>

    <g class="pal p3" style="transform-origin: 800px 790px">
      <path d="M 712 860 A 88 88 0 0 1 888 860 Z" fill="var(--coral)"></path>
      <circle cx="800" cy="716" r="62" fill="var(--coral)"></circle>
      <circle class="halo" cx="800" cy="716" r="90" fill="none" stroke="var(--coral)" stroke-width="5" opacity="0.28" style="transform-origin: 800px 716px"></circle>
    </g>

    {{-- Χαρτοπόλεμο γύρω γύρω. --}}
    <rect class="bit b1" x="290" y="250" width="26" height="12" rx="6" fill="var(--yellow)" style="transform-origin: 303px 256px"></rect>
    <rect class="bit b2" x="1290" y="700" width="24" height="12" rx="6" fill="var(--green)" style="transform-origin: 1302px 706px"></rect>
    <rect class="bit b3" x="1230" y="180" width="22" height="11" rx="6" fill="var(--coral)" style="transform-origin: 1241px 186px"></rect>
    <circle class="tw d1" cx="360" cy="740" r="15" fill="var(--coral)"></circle>
    <circle class="tw d2" cx="1400" cy="520" r="13" fill="var(--yellow)"></circle>
    <circle class="tw" cx="230" cy="420" r="17" fill="var(--green)"></circle>
    <circle class="tw d3" cx="1180" cy="880" r="12" fill="var(--coral)"></circle>
</svg>
