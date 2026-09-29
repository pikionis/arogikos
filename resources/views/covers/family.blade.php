{{-- Γονείς και παιδί — δύο μεγάλοι κρατούν το χέρι ενός μικρού. --}}
<svg aria-hidden="true" viewBox="0 0 1600 1000" class="cv-svg">
    <rect x="0" y="0" width="1600" height="1000" fill="var(--off-white)"></rect>
    <circle class="br" cx="790" cy="520" r="410" fill="var(--soft-green)" opacity="0.72" style="transform-origin: 790px 520px"></circle>
    <circle class="br d2" cx="210" cy="830" r="250" fill="var(--soft-yellow)" style="transform-origin: 210px 830px"></circle>
    <circle class="br d1" cx="1420" cy="230" r="200" fill="var(--soft-coral)" style="transform-origin: 1420px 230px"></circle>

    {{-- Ο ήλιος πάνω από την οικογένεια. --}}
    <g class="sun" style="transform-origin: 1210px 210px">
      <circle cx="1210" cy="210" r="76" fill="var(--yellow)"></circle>
      <circle class="halo" cx="1210" cy="210" r="118" fill="none" stroke="var(--yellow)" stroke-width="6" opacity="0.4" style="transform-origin: 1210px 210px"></circle>
    </g>
    <circle cx="790" cy="520" r="340" fill="none" stroke="var(--green)" stroke-width="3" stroke-dasharray="6 24" opacity="0.24"></circle>

    {{-- Τα χέρια που ενώνουν: σχεδιάζονται πίσω από τα σώματα. --}}
    <g fill="none" stroke-width="22" stroke-linecap="round">
      <path class="arm a1" d="M 596 618 Q 664 700 726 706" stroke="var(--green)" opacity="0.85"></path>
      <path class="arm a2" d="M 1010 632 Q 944 706 878 708" stroke="var(--coral)" opacity="0.85"></path>
    </g>

    {{-- Μεγάλος αριστερά. --}}
    <g class="f-a" style="transform-origin: 520px 640px">
      <rect x="452" y="500" width="138" height="318" rx="69" fill="var(--green)"></rect>
      <circle cx="521" cy="420" r="66" fill="var(--green)"></circle>
      <circle cx="521" cy="420" r="94" fill="none" stroke="var(--green)" stroke-width="4" opacity="0.22"></circle>
    </g>

    {{-- Μεγάλος δεξιά. --}}
    <g class="f-b" style="transform-origin: 1078px 650px">
      <rect x="1014" y="516" width="130" height="302" rx="65" fill="var(--coral)"></rect>
      <circle cx="1079" cy="440" r="62" fill="var(--coral)"></circle>
      <circle cx="1079" cy="440" r="90" fill="none" stroke="var(--coral)" stroke-width="4" opacity="0.22"></circle>
    </g>

    {{-- Το παιδί, ανάμεσά τους και λίγο μπροστά. --}}
    <g class="child" style="transform-origin: 800px 780px">
      <rect x="748" y="660" width="104" height="158" rx="52" fill="var(--yellow)"></rect>
      <circle cx="800" cy="602" r="52" fill="var(--yellow)"></circle>
      <circle class="tw" cx="800" cy="602" r="76" fill="none" stroke="var(--yellow)" stroke-width="5" opacity="0.35"></circle>
    </g>

    {{-- Το έδαφος. --}}
    <rect x="300" y="812" width="1000" height="18" rx="9" fill="var(--green)" opacity="0.5"></rect>

    {{-- Καρδιές: δύο τόξα και μια μύτη, όχι κείμενο. --}}
    <g class="heart h1" style="transform-origin: 660px 300px">
      <path d="M 660 340 L 620 300 A 28 28 0 0 1 660 262 A 28 28 0 0 1 700 300 Z" fill="var(--coral)" opacity="0.75"></path>
    </g>
    <g class="heart h2" style="transform-origin: 950px 250px">
      <path d="M 950 282 L 918 250 A 22 22 0 0 1 950 220 A 22 22 0 0 1 982 250 Z" fill="var(--green)" opacity="0.6"></path>
    </g>

    <circle class="tw d1" cx="300" cy="260" r="16" fill="var(--coral)"></circle>
    <circle class="tw d2" cx="1380" cy="760" r="14" fill="var(--green)"></circle>
    <circle class="tw d3" cx="420" cy="180" r="11" fill="var(--yellow)"></circle>
    <circle class="tw" cx="1260" cy="880" r="18" fill="var(--yellow)"></circle>
    <circle class="tw d2" cx="180" cy="520" r="12" fill="var(--green)"></circle>
</svg>
