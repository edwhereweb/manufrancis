<?php

declare(strict_types=1);

/**
 * Hand-drawn inline SVG illustrations. Colours come from CSS (currentColor + .i-* classes),
 * motion from CSS classes (.spin, .pulse, .scan, .flow, .blink, .float). No style="" attributes:
 * the CSP forbids inline styles.
 */
function illo(string $name): string
{
    return match ($name) {
        // Hero: orbit rings and nodes around the portrait.
        'orbit' => <<<'SVG'
            <svg class="illo illo-orbit" viewBox="0 0 600 600" aria-hidden="true" focusable="false">
              <circle cx="300" cy="300" r="290" class="i-line i-faint"/>
              <circle cx="300" cy="300" r="230" class="i-line i-faint"/>
              <g class="spin slow"><circle cx="300" cy="300" r="260" class="i-line i-dash"/>
                <circle cx="300" cy="40" r="7" class="i-fill"/><circle cx="560" cy="300" r="5" class="i-fill-2"/></g>
              <g class="spin rev"><circle cx="300" cy="300" r="200" class="i-line i-dash-2"/>
                <circle cx="100" cy="300" r="6" class="i-fill-3"/><circle cx="441" cy="441" r="4" class="i-fill"/></g>
              <circle cx="78" cy="150" r="4" class="i-fill-2 pulse"/>
              <circle cx="530" cy="470" r="4" class="i-fill pulse d2"/>
            </svg>
            SVG,

        'shield' => <<<'SVG'
            <svg class="illo" viewBox="0 0 240 160" aria-hidden="true" focusable="false">
              <rect x="20" y="22" width="200" height="116" rx="14" class="i-card"/>
              <path d="M120 34 L156 46 V78 C156 102 140 118 120 126 C100 118 84 102 84 78 V46 Z" class="i-soft"/>
              <path d="M120 34 L156 46 V78 C156 102 140 118 120 126 C100 118 84 102 84 78 V46 Z" class="i-line i-strong"/>
              <rect x="106" y="74" width="28" height="22" rx="4" class="i-fill"/>
              <path d="M112 74 V67 a8 8 0 0 1 16 0 V74" class="i-line i-strong"/>
              <circle cx="120" cy="85" r="3" class="i-white"/>
              <rect x="84" y="40" width="72" height="3" rx="1.5" class="i-fill-2 scan"/>
              <circle cx="44" cy="44" r="4" class="i-ok pulse"/><rect x="54" y="41" width="22" height="6" rx="3" class="i-mute"/>
              <rect x="174" y="112" width="30" height="6" rx="3" class="i-mute"/>
            </svg>
            SVG,

        'network' => <<<'SVG'
            <svg class="illo" viewBox="0 0 240 160" aria-hidden="true" focusable="false">
              <path d="M120 80 L50 40 M120 80 L190 40 M120 80 L50 124 M120 80 L190 124 M50 40 L50 124 M190 40 L190 124" class="i-line i-faint"/>
              <path d="M120 80 L50 40 M120 80 L190 124 M50 124 L50 40" class="i-line i-strong flow"/>
              <path d="M120 80 L190 40 M120 80 L50 124 M190 40 L190 124" class="i-line i-strong flow d2"/>
              <rect x="98" y="62" width="44" height="36" rx="9" class="i-fill"/>
              <rect x="106" y="72" width="28" height="4" rx="2" class="i-white"/><rect x="106" y="82" width="18" height="4" rx="2" class="i-white"/>
              <g class="i-node"><rect x="34" y="28" width="32" height="24" rx="6"/><rect x="174" y="28" width="32" height="24" rx="6"/>
                <rect x="34" y="112" width="32" height="24" rx="6"/><rect x="174" y="112" width="32" height="24" rx="6"/></g>
              <circle cx="58" cy="40" r="2.5" class="i-ok pulse"/><circle cx="198" cy="40" r="2.5" class="i-ok pulse d2"/>
              <circle cx="58" cy="124" r="2.5" class="i-ok pulse d3"/><circle cx="198" cy="124" r="2.5" class="i-ok pulse"/>
            </svg>
            SVG,

        'code' => <<<'SVG'
            <svg class="illo" viewBox="0 0 240 160" aria-hidden="true" focusable="false">
              <rect x="24" y="20" width="192" height="120" rx="14" class="i-card"/>
              <path d="M24 46 H216" class="i-line i-faint"/>
              <circle cx="40" cy="33" r="4" class="i-mute"/><circle cx="53" cy="33" r="4" class="i-mute"/><circle cx="66" cy="33" r="4" class="i-mute"/>
              <rect x="42" y="60" width="34" height="7" rx="3.5" class="i-fill"/><rect x="82" y="60" width="62" height="7" rx="3.5" class="i-mute"/>
              <rect x="56" y="76" width="48" height="7" rx="3.5" class="i-fill-2"/><rect x="110" y="76" width="40" height="7" rx="3.5" class="i-mute"/>
              <rect x="56" y="92" width="26" height="7" rx="3.5" class="i-fill-3"/><rect x="88" y="92" width="70" height="7" rx="3.5" class="i-mute"/>
              <rect x="42" y="108" width="20" height="7" rx="3.5" class="i-fill"/>
              <rect x="68" y="106" width="3" height="11" class="i-fill blink"/>
              <path d="M176 92 l-10 10 l10 10 M194 92 l10 10 l-10 10" class="i-line i-strong float"/>
            </svg>
            SVG,

        'teach' => <<<'SVG'
            <svg class="illo" viewBox="0 0 240 160" aria-hidden="true" focusable="false">
              <rect x="48" y="18" width="144" height="80" rx="10" class="i-card"/>
              <path d="M66 80 L94 58 L114 70 L146 40 L172 52" class="i-line i-strong flow"/>
              <circle cx="146" cy="40" r="4" class="i-fill pulse"/>
              <path d="M120 98 V110" class="i-line i-faint"/>
              <g class="i-people"><circle cx="66" cy="124" r="9"/><path d="M50 150 a16 14 0 0 1 32 0"/>
                <circle cx="120" cy="122" r="10"/><path d="M102 150 a18 16 0 0 1 36 0"/>
                <circle cx="174" cy="124" r="9"/><path d="M158 150 a16 14 0 0 1 32 0"/></g>
              <path d="M104 112 L120 104 L136 112 L120 120 Z" class="i-fill-3 float"/>
            </svg>
            SVG,

        'laurel' => <<<'SVG'
            <svg class="illo illo-laurel" viewBox="0 0 120 120" aria-hidden="true" focusable="false">
              <path d="M60 104 C34 100 18 78 20 50" class="i-line i-gold"/><path d="M60 104 C86 100 102 78 100 50" class="i-line i-gold"/>
              <g class="i-gold-fill"><ellipse cx="22" cy="58" rx="5" ry="10" transform="rotate(-20 22 58)"/><ellipse cx="27" cy="74" rx="5" ry="10" transform="rotate(-40 27 74)"/>
                <ellipse cx="37" cy="88" rx="5" ry="10" transform="rotate(-60 37 88)"/><ellipse cx="98" cy="58" rx="5" ry="10" transform="rotate(20 98 58)"/>
                <ellipse cx="93" cy="74" rx="5" ry="10" transform="rotate(40 93 74)"/><ellipse cx="83" cy="88" rx="5" ry="10" transform="rotate(60 83 88)"/></g>
              <path d="M60 30 l7 14 15 2 -11 11 3 15 -14 -7 -14 7 3 -15 -11 -11 15 -2 Z" class="i-gold-fill pulse"/>
            </svg>
            SVG,

        default => '',
    };
}
