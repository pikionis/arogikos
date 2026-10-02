<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Brand\Canvas;
use App\Support\Brand\SvgRaster;
use App\Support\Brand\Woff;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Draws the two images the site cannot generate at request time: the share
 * card that social platforms show when somebody posts a link, and the favicon
 * set.
 *
 * Both are committed to the repo. This command exists so they can be redrawn
 * from the brand tokens rather than re-made by hand in a design tool, and so
 * the logo in them can never drift from <x-logo-mark>. It needs node_modules
 * for the fonts, so it is a build-time tool — nothing in a web request, and
 * nothing on the production server, ever calls it.
 */
class BuildBrandImages extends Command
{
    protected $signature = 'brand:images {--path=public : Where to write the files}';

    protected $description = 'Redraw the social share card and favicons from the brand tokens';

    /* Straight from resources/css/arogi.css — if those change, change these. */
    private const GREEN = '#1E9E58';

    private const CORAL = '#E26D5A';

    private const YELLOW = '#F4B62A';

    private const OFF_WHITE = '#FAF8F5';

    private const SOFT_GREEN = '#EAF6EF';

    private const INK = '#2B2B2B';

    private const INK_600 = '#555049';

    public function handle(): int
    {
        $out = rtrim($this->option('path'), '/\\');

        if (! is_dir($out)) {
            $this->error("No such directory: $out");

            return self::FAILURE;
        }

        try {
            $fonts = $this->fonts();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            $this->line('  Run npm install first — the fonts come from node_modules.');

            return self::FAILURE;
        }

        @mkdir("$out/images", 0755, true);

        $this->shareCard($fonts, "$out/images/share-default.png");
        $this->info("  wrote $out/images/share-default.png  (1200x630)");

        $this->favicons($out);
        $this->coverCards($out);

        return self::SUCCESS;
    }

    /* ---------------------------------------------------------- share card */

    /**
     * 1200x630 — the size every platform crops to 1.91:1.
     *
     * @param  array<string, string>  $fonts
     */
    private function shareCard(array $fonts, string $path): void
    {
        $c = new Canvas(1200, 630);

        $c->fill(self::OFF_WHITE);

        // A soft arch off the right edge. The brand's radii notes call the
        // arch the tone-setter, and it keeps the card from being a plain box.
        $c->disc(1180, 300, 340, self::SOFT_GREEN);

        $this->logoMark($c, 92, 66, 156);

        // "ΑΡΩΓΗ" — the Greek subset of Comfortaa, same as every heading.
        $c->text($fonts['head'], 100, 92, 366, self::INK, 'ΑΡΩΓΗ');

        $c->text($fonts['body'], 38, 96, 434, self::INK_600, 'Κέντρο Μάθησης και Συμβουλευτικής');

        // The subsets are split by script, so a Greek word and a Latin domain
        // cannot come out of the same file — measure the first, then continue.
        $width = $c->text($fonts['body'], 31, 96, 492, self::GREEN, 'Κως');
        $c->text($fonts['latin'], 31, 96 + $width + 14, 492, self::INK_600, '· arogikos.gr');

        // The three brand colours, in the logo's own order.
        $c->rect(0, 616, 600, 14, self::GREEN)
            ->rect(600, 616, 360, 14, self::CORAL)
            ->rect(960, 616, 240, 14, self::YELLOW);

        $c->save($path);
    }

    /**
     * The logo, in the same proportions as the logo-mark Blade component.
     *
     * That file is a 100x100 viewBox, so every number here is that coordinate
     * multiplied by $size/100.
     */
    private function logoMark(Canvas $c, float $x, float $y, float $size): void
    {
        $u = fn (float $n): float => $n * $size / 100;

        $c->ring($x + $u(50), $y + $u(27), $u(11.5), $u(5.5), self::YELLOW);
        $c->roundedBar($x + $u(29), $y + $u(47), $u(42), $u(5.5), self::GREEN);
        // d="M31 76 A19 19 0 0 1 69 76" — the top half of a circle at (50,76).
        $c->arc($x + $u(50), $y + $u(76), $u(19), 180, 360, $u(5.5), self::CORAL);
    }

    /* --------------------------------------------------------- cover cards

       The twenty scenes an author can pick, as PNGs. Facebook and the rest
       will not read an SVG, so without these every article shares the same
       generic card however carefully its cover was chosen. Read from the very
       files the site renders, so the picture posted to a timeline cannot
       drift from the one on the page. */

    private function coverCards(string $out): void
    {
        $palette = [
            '--green' => self::GREEN,
            '--coral' => self::CORAL,
            '--yellow' => self::YELLOW,
            '--off-white' => self::OFF_WHITE,
            '--soft-green' => self::SOFT_GREEN,
            '--soft-coral' => '#FCEEE9',
            '--soft-yellow' => '#FFF7E6',
            '--white' => '#FFFFFF',
        ];

        $raster = new SvgRaster($palette);
        @mkdir("$out/images/covers", 0755, true);

        $scenes = array_keys(config('cms-covers', []));

        foreach ($scenes as $key) {
            $source = resource_path("views/covers/$key.blade.php");

            if (! is_file($source)) {
                $this->warn("  missing scene: $key");

                continue;
            }

            // 1600x1000 is the scene's own frame; social wants 1.91:1, so the
            // card is that frame with the top and bottom trimmed evenly —
            // which is also what Facebook would crop to anyway.
            $canvas = new Canvas(1200, 630, 2);
            $canvas->fill(self::OFF_WHITE);
            $canvas->viewBox(1600, 1000, 838);

            $raster->render((string) file_get_contents($source), $canvas);

            $canvas->save("$out/images/covers/$key.png");
        }

        $this->info('  wrote '.count($scenes)." cover cards to $out/images/covers/");
    }

    /* ------------------------------------------------------------ favicons */

    /**
     * Sides Google accepts for a search result's icon: 48, or a multiple.
     *
     * @var array<int, int>
     */
    private const ICON_SIZES = [48, 96, 192, 480];

    private function favicons(string $out): void
    {
        // Apple ignores transparency and composites on black, so the touch
        // icon gets the paper background the rest of the site uses.
        $touch = new Canvas(180, 180, 4);
        $touch->fill(self::OFF_WHITE);
        $this->logoMark($touch, 18, 18, 144);
        $touch->save("$out/apple-touch-icon.png");
        $this->info("  wrote $out/apple-touch-icon.png  (180x180)");

        $icon = new Canvas(64, 64, 8);
        $icon->fill(self::OFF_WHITE);
        // Tighter margins than the touch icon: at 16px every pixel counts.
        $this->logoMark($icon, 4, 4, 56);

        file_put_contents("$out/favicon.ico", $this->ico($icon, [16, 32, 48]));
        $this->info("  wrote $out/favicon.ico  (16, 32, 48)");

        // Google wants the icon for a search result as a square PNG whose
        // side is a multiple of 48, and will not take 32 — which is all the
        // page offered it, so it drew nothing at all. 512 doubles as the
        // logo in the structured data.
        foreach (self::ICON_SIZES as $size) {
            $png = new Canvas($size, $size, $size < 128 ? 8 : 2);
            $png->fill(self::OFF_WHITE);
            $this->logoMark($png, $size * 0.0625, $size * 0.0625, $size * 0.875);
            $png->save("$out/icon-{$size}.png");
        }

        $this->info('  wrote '.implode(', ', array_map(
            fn (int $s) => "$out/icon-$s.png", self::ICON_SIZES
        )));

        file_put_contents("$out/favicon.svg", $this->svgIcon());
        $this->info("  wrote $out/favicon.svg");
    }

    /**
     * An .ico wrapping PNG frames.
     *
     * The PNG-inside-ICO form has been read by every browser since IE11, and
     * it avoids hand-rolling a BMP with an AND mask.
     *
     * @param  array<int, int>  $sizes
     */
    private function ico(Canvas $icon, array $sizes): string
    {
        $frames = array_map(fn (int $s) => ['size' => $s, 'png' => $icon->pngAt($s)], $sizes);

        $header = pack('vvv', 0, 1, count($frames));
        $offset = 6 + count($frames) * 16;

        $directory = '';
        $body = '';

        foreach ($frames as $frame) {
            $directory .= pack(
                'CCCCvvVV',
                $frame['size'],
                $frame['size'],
                0, 0, 1, 32,
                strlen($frame['png']),
                $offset,
            );

            $body .= $frame['png'];
            $offset += strlen($frame['png']);
        }

        return $header.$directory.$body;
    }

    /** The mark as vectors, for browsers that prefer an SVG favicon. */
    private function svgIcon(): string
    {
        return implode("\n", [
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">',
            '    <rect width="100" height="100" rx="22" fill="'.self::OFF_WHITE.'"/>',
            '    <circle cx="50" cy="27" r="11.5" fill="none" stroke="'.self::YELLOW.'" stroke-width="5.5"/>',
            '    <rect x="29" y="47" width="42" height="5.5" rx="2.75" fill="'.self::GREEN.'"/>',
            '    <path d="M31 76 A19 19 0 0 1 69 76" fill="none" stroke="'.self::CORAL.'" stroke-width="5.5" stroke-linecap="round"/>',
            '</svg>',
            '',
        ]);
    }

    /* --------------------------------------------------------------- fonts */

    /**
     * Unpacks the Greek subsets of the brand fonts into .ttf files GD can use.
     *
     * @return array<string, string>
     */
    private function fonts(): array
    {
        $dir = storage_path('app/brand-fonts');
        @mkdir($dir, 0755, true);

        $sources = [
            'head' => 'node_modules/@fontsource/comfortaa/files/comfortaa-greek-700-normal.woff',
            'body' => 'node_modules/@fontsource/source-sans-3/files/source-sans-3-greek-600-normal.woff',
            'latin' => 'node_modules/@fontsource/source-sans-3/files/source-sans-3-latin-600-normal.woff',
        ];

        $fonts = [];

        foreach ($sources as $name => $relative) {
            $source = base_path($relative);

            if (! is_file($source)) {
                throw new RuntimeException("Missing font: $relative");
            }

            Woff::toTtf($source, $ttf = "$dir/$name.ttf");
            $fonts[$name] = $ttf;
        }

        return $fonts;
    }
}
