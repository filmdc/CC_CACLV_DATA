<?php

/**
 * Builds the CACLV Housing favicon set: the SVG, the PNG sizes, and a
 * PNG-in-ICO container. Geometry is declared once here and the SVG is
 * written from it, so the vector and raster versions cannot drift.
 *
 * Colours are the CALV primary palette (resources/css/homes.css):
 * teal #006269 badge, red #D14124 roof, white wall, door knocked out in
 * the badge teal rather than introducing a fourth colour.
 *
 * Run:  php bin/build-homes-favicon.php
 *
 * Writes to BOTH the project root and public/. This deployment's Apache
 * DocumentRoot is the project root (see CLAUDE.md), so only the root copies
 * are actually served today; the public/ copies keep the tree correct for a
 * conventional Laravel docroot. Rendered with GD because ImageMagick's
 * policy on this box refuses to rasterize SVG.
 */
const DESIGN = 64;   // design/viewBox units
const SS = 1024; // supersample canvas

// ── Geometry (design units) ──────────────────────────────────────────
const RADIUS = 14;                    // badge corner
const ROOF = [32, 11, 57, 34, 7, 34]; // apex, right eave, left eave
const WALL = [15, 34, 49, 51];      // x1 y1 x2 y2
const DOOR = [28, 40, 36, 51];
const TEAL = '#006269';
const RED = '#D14124';
const WHITE = '#FFFFFF';

/** Every location the built files are written to. */
function outDirs(): array
{
    $root = dirname(__DIR__);

    return [$root, $root.'/public'];
}

function putBoth(string $name, string $data): void
{
    foreach (outDirs() as $dir) {
        if (is_dir($dir)) {
            file_put_contents($dir.'/'.$name, $data);
        }
    }
}

function px(float $u): int
{
    return (int) round($u / DESIGN * SS);
}

function roundedRect($im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
{
    imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
    imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
    $d = $r * 2;
    foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
        imagefilledellipse($im, $cx, $cy, $d, $d, $color);
    }
}

function rgb($im, string $hex): int
{
    return imagecolorallocate($im, hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)));
}

// ── SVG ──────────────────────────────────────────────────────────────
[$ax, $ay, $rx, $ry, $lx, $ly] = ROOF;
[$wx1, $wy1, $wx2, $wy2] = WALL;
[$dx1, $dy1, $dx2, $dy2] = DOOR;

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="CACLV Housing">
  <!--
    CACLV Housing portal mark. Three shapes only — badge, roof, wall — so it
    still reads as a house at 16px in a browser tab. CALV primary palette.
    Generated alongside the PNG/ICO set; edit the geometry in the build
    script rather than here so the raster versions stay in step.
  -->
  <rect width="64" height="64" rx="{RADIUS}" fill="{TEAL}"/>
  <path d="M{$ax} {$ay} L{$rx} {$ry} H{$lx} Z" fill="{RED}"/>
  <rect x="{$wx1}" y="{$wy1}" width="{width}" height="{height}" fill="{WHITE}"/>
  <rect x="{$dx1}" y="{$dy1}" width="{dwidth}" height="{dheight}" rx="1" fill="{TEAL}"/>
</svg>

SVG;
$svg = strtr($svg, [
    '{RADIUS}' => RADIUS, '{TEAL}' => TEAL, '{RED}' => RED, '{WHITE}' => WHITE,
    '{width}' => $wx2 - $wx1, '{height}' => $wy2 - $wy1,
    '{dwidth}' => $dx2 - $dx1, '{dheight}' => $dy2 - $dy1,
]);
putBoth('favicon-homes.svg', $svg);

// ── Raster ───────────────────────────────────────────────────────────
$im = imagecreatetruecolor(SS, SS);
imagealphablending($im, false);
imagesavealpha($im, true);
imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
imagealphablending($im, true);

roundedRect($im, 0, 0, SS - 1, SS - 1, px(RADIUS), rgb($im, TEAL));
imagefilledpolygon($im, [px($ax), px($ay), px($rx), px($ry), px($lx), px($ly)], rgb($im, RED));
imagefilledrectangle($im, px($wx1), px($wy1), px($wx2), px($wy2), rgb($im, WHITE));
imagefilledrectangle($im, px($dx1), px($dy1), px($dx2), px($dy2), rgb($im, TEAL));

$icoSizes = [16, 32, 48];
$pngs = [];
foreach ([180, 48, 32, 16] as $size) {
    $out = imagescale($im, $size, $size, IMG_BICUBIC);
    imagesavealpha($out, true);
    ob_start();
    imagepng($out);
    $pngs[$size] = ob_get_clean();
}

// Shipped PNGs: 180 for apple-touch, 32 as the fallback for browsers that
// will not take an SVG icon.
putBoth('favicon-homes-180.png', $pngs[180]);
putBoth('favicon-homes-32.png', $pngs[32]);

// ── ICO (PNG-compressed entries; universally supported since Vista) ──
$n = count($icoSizes);
$ico = pack('vvv', 0, 1, $n);
$offset = 6 + 16 * $n;
foreach ($icoSizes as $s) {
    $ico .= pack('CCCCvvVV', $s === 256 ? 0 : $s, $s === 256 ? 0 : $s, 0, 0, 1, 32, strlen($pngs[$s]), $offset);
    $offset += strlen($pngs[$s]);
}
foreach ($icoSizes as $s) {
    $ico .= $pngs[$s];
}
putBoth('favicon-homes.ico', $ico);

echo 'Built favicon-homes.{svg,ico}, favicon-homes-{32,180}.png in: '.implode(', ', array_filter(outDirs(), 'is_dir')).PHP_EOL;
