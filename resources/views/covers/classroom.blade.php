{{-- Θρανίο — ένα παιδί καθισμένο πίσω του, μια δασκάλα δίπλα, ο πίνακας πίσω.
     Η σειρά σχεδίασης είναι η σκηνή: πίνακας, παιδί, θρανίο από μπροστά του,
     και πάνω στο θρανίο το βιβλίο. --}}
<svg aria-hidden="true" viewBox="0 0 1600 1000" class="cv-svg">
    <rect x="0" y="0" width="1600" height="1000" fill="var(--off-white)"></rect>
    <circle class="br" cx="700" cy="440" r="430" fill="var(--soft-yellow)" opacity="0.8" style="transform-origin: 700px 440px"></circle>
    <circle class="br d2" cx="1460" cy="840" r="240" fill="var(--soft-green)" style="transform-origin: 1460px 840px"></circle>
    <circle class="br d1" cx="140" cy="190" r="180" fill="var(--soft-coral)" style="transform-origin: 140px 190px"></circle>

    {{-- Ο πίνακας. --}}
    <g class="board" style="transform-origin: 610px 340px">
      <rect x="220" y="120" width="780" height="420" rx="30" fill="var(--white)"></rect>
      <rect x="220" y="120" width="780" height="420" rx="30" fill="none" stroke="var(--green)" stroke-width="10" opacity="0.7"></rect>
      <g fill="none" stroke-linecap="round" stroke-width="16">
        <path class="chalk w1" d="M 292 230 L 692 230" stroke="var(--green)" stroke-dasharray="400" opacity="0.45"></path>
        <path class="chalk w2" d="M 292 310 L 852 310" stroke="var(--coral)" stroke-dasharray="560" opacity="0.4"></path>
        <path class="chalk w3" d="M 292 390 L 612 390" stroke="var(--yellow)" stroke-dasharray="320" opacity="0.55"></path>
      </g>
      {{-- Ένα τικ στη γωνιά, για το «σωστό». --}}
      <path class="tick" d="M 776 412 L 836 472 L 936 352" fill="none" stroke="var(--green)" stroke-width="20"
            stroke-linecap="round" stroke-dasharray="260" opacity="0.85"></path>
    </g>
    <rect x="250" y="546" width="720" height="14" rx="7" fill="var(--green)" opacity="0.3"></rect>

    {{-- Η δασκάλα, όρθια δεξιά, με το χέρι προς τον πίνακα. --}}
    <g class="f-b" style="transform-origin: 1250px 640px">
      <path class="point" d="M 1186 566 Q 1098 534 1030 480" fill="none" stroke="var(--coral)"
            stroke-width="24" stroke-linecap="round" style="transform-origin: 1186px 566px"></path>
      <rect x="1186" y="492" width="132" height="330" rx="66" fill="var(--coral)"></rect>
      <circle cx="1252" cy="416" r="64" fill="var(--coral)"></circle>
      <circle class="halo" cx="1252" cy="416" r="94" fill="none" stroke="var(--coral)" stroke-width="5" opacity="0.26" style="transform-origin: 1252px 416px"></circle>
    </g>

    {{-- Το παιδί — καθισμένο, ώστε το θρανίο να κρύψει τα μισά. --}}
    <g class="child" style="transform-origin: 566px 760px">
      <rect x="500" y="596" width="132" height="190" rx="66" fill="var(--yellow)"></rect>
      <circle cx="566" cy="524" r="60" fill="var(--yellow)"></circle>
      <circle class="tw" cx="566" cy="524" r="88" fill="none" stroke="var(--yellow)" stroke-width="5" opacity="0.35"></circle>
    </g>

    {{-- Το θρανίο, μπροστά από το παιδί. --}}
    <g class="desk" style="transform-origin: 730px 800px">
      <rect x="360" y="704" width="740" height="30" rx="15" fill="var(--green)"></rect>
      <rect x="392" y="732" width="676" height="58" rx="12" fill="var(--soft-green)"></rect>
      <rect x="392" y="732" width="676" height="58" rx="12" fill="none" stroke="var(--green)" stroke-width="4" opacity="0.35"></rect>
      <rect x="418" y="788" width="26" height="120" rx="13" fill="var(--green)" opacity="0.75"></rect>
      <rect x="1016" y="788" width="26" height="120" rx="13" fill="var(--green)" opacity="0.75"></rect>
    </g>

    {{-- Το ανοιχτό βιβλίο πάνω στο θρανίο. --}}
    <g class="book" style="transform-origin: 820px 704px">
      <path d="M 820 704 Q 720 668 640 684 L 640 630 Q 720 614 820 650 Z" fill="var(--white)"></path>
      <path d="M 820 704 Q 920 668 1000 684 L 1000 630 Q 920 614 820 650 Z" fill="var(--white)"></path>
      <path d="M 820 704 Q 720 668 640 684 L 640 630 Q 720 614 820 650 Z" fill="none" stroke="var(--green)" stroke-width="5" opacity="0.45"></path>
      <path d="M 820 704 Q 920 668 1000 684 L 1000 630 Q 920 614 820 650 Z" fill="none" stroke="var(--green)" stroke-width="5" opacity="0.45"></path>
      <path d="M 820 650 L 820 704" fill="none" stroke="var(--green)" stroke-width="7" opacity="0.6"></path>
      <rect class="mark" x="810" y="606" width="20" height="70" rx="10" fill="var(--yellow)" style="transform-origin: 820px 606px"></rect>
    </g>

    {{-- Ένα μολύβι που γράφει, ακουμπισμένο στο θρανίο. --}}
    <g class="pencil" style="transform-origin: 1074px 700px">
      <rect x="1062" y="604" width="26" height="96" rx="8" fill="var(--coral)"></rect>
      <path d="M 1062 700 L 1088 700 L 1075 730 Z" fill="var(--green)"></path>
    </g>

    <circle class="tw d1" cx="1130" cy="180" r="17" fill="var(--green)"></circle>
    <circle class="tw d2" cx="1420" cy="320" r="12" fill="var(--yellow)"></circle>
    <circle class="tw d3" cx="180" cy="680" r="15" fill="var(--coral)"></circle>
    <circle class="tw" cx="270" cy="880" r="11" fill="var(--green)"></circle>
</svg>
