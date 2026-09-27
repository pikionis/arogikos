<?php

declare(strict_types=1);

namespace App\Support\Brand;

use RuntimeException;

/**
 * Unpacks a .woff file back into the TrueType font inside it.
 *
 * GD's text drawing needs a .ttf, and the only Greek-capable copies of the
 * brand fonts in this project are the @fontsource .woff files that Vite
 * bundles. WOFF is not a different font format — it is an sfnt whose tables
 * have each been zlib-deflated and given a new directory. Putting it back
 * together is a header rewrite, which is far less trouble than adding a
 * binary toolchain to the build for the sake of two generated images.
 *
 * Build-time only: nothing in a web request calls this.
 *
 * @see https://www.w3.org/TR/WOFF/
 */
final class Woff
{
    private const HEADER_SIZE = 44;

    private const ENTRY_SIZE = 20;

    /** Writes the TrueType inside `$woffPath` to `$ttfPath`. */
    public static function toTtf(string $woffPath, string $ttfPath): void
    {
        $ttf = self::convert(
            @file_get_contents($woffPath) ?: throw new RuntimeException("Cannot read $woffPath")
        );

        if (@file_put_contents($ttfPath, $ttf) === false) {
            throw new RuntimeException("Cannot write $ttfPath");
        }
    }

    /** @return string the raw sfnt (.ttf) bytes */
    public static function convert(string $woff): string
    {
        if (strlen($woff) < self::HEADER_SIZE || substr($woff, 0, 4) !== 'wOFF') {
            throw new RuntimeException('Not a WOFF file.');
        }

        $flavor = self::u32($woff, 4);
        $numTables = self::u16($woff, 12);

        if ($numTables === 0) {
            throw new RuntimeException('WOFF declares no tables.');
        }

        $tables = [];
        for ($i = 0; $i < $numTables; $i++) {
            $at = self::HEADER_SIZE + $i * self::ENTRY_SIZE;

            $tag = substr($woff, $at, 4);
            $offset = self::u32($woff, $at + 4);
            $compLength = self::u32($woff, $at + 8);
            $origLength = self::u32($woff, $at + 12);
            $checksum = self::u32($woff, $at + 16);

            $data = substr($woff, $offset, $compLength);

            // Equal lengths mean the table was stored uncompressed.
            if ($compLength !== $origLength) {
                $data = @gzuncompress($data);

                if ($data === false) {
                    throw new RuntimeException("Table $tag failed to inflate.");
                }
            }

            if (strlen($data) !== $origLength) {
                throw new RuntimeException("Table $tag is the wrong length.");
            }

            $tables[] = ['tag' => $tag, 'checksum' => $checksum, 'data' => $data];
        }

        // The sfnt directory must be in tag order for a binary search to work.
        usort($tables, fn (array $a, array $b) => strcmp($a['tag'], $b['tag']));

        return self::assemble($flavor, $tables);
    }

    /**
     * @param  array<int, array{tag: string, checksum: int, data: string}>  $tables
     */
    private static function assemble(int $flavor, array $tables): string
    {
        $count = count($tables);
        $entrySelector = (int) floor(log($count, 2));
        $searchRange = (2 ** $entrySelector) * 16;

        $out = pack('Nnnnn', $flavor, $count, $searchRange, $entrySelector, $count * 16 - $searchRange);

        // Every table starts on a 4-byte boundary, so offsets are known only
        // once the padding is accounted for.
        $offset = 12 + $count * 16;
        $body = '';

        foreach ($tables as $table) {
            $length = strlen($table['data']);
            $out .= $table['tag'].pack('NNN', $table['checksum'], $offset, $length);

            $padded = $length + ((4 - $length % 4) % 4);
            $body .= str_pad($table['data'], $padded, "\0");
            $offset += $padded;
        }

        return $out.$body;
    }

    private static function u16(string $s, int $at): int
    {
        return unpack('n', substr($s, $at, 2))[1];
    }

    private static function u32(string $s, int $at): int
    {
        return unpack('N', substr($s, $at, 4))[1];
    }
}
