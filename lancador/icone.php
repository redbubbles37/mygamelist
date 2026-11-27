<?php
// Gera icone.ico (o mesmo desenho do ícone da aba) em vários tamanhos. Uso: ..\php\php.exe -c ..\php\php.ini icone.php
$render = function (int $s): string {
    $k = 4; $w = $s * $k;                       // desenha 4x maior e reduz, para suavizar as bordas
    $big = imagecreatetruecolor($w, $w);
    imagealphablending($big, false);
    imagefill($big, 0, 0, imagecolorallocatealpha($big, 0, 0, 0, 127));
    $r = (int) ($w * 0.22);
    for ($y = 0; $y < $w; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $cx = max($r - $x, $x - ($w - 1 - $r), 0); $cy = max($r - $y, $y - ($w - 1 - $r), 0);
            if ($cx * $cx + $cy * $cy > $r * $r) continue;
            $t = ($x + $y) / (2 * $w);
            imagesetpixel($big, $x, $y, imagecolorallocate($big, (int) (139 + (192 - 139) * $t), (int) (92 + (38 - 92) * $t), (int) (246 + (211 - 246) * $t)));
        }
    }
    imagealphablending($big, true);
    $white = imagecolorallocate($big, 255, 255, 255);
    $accent = imagecolorallocate($big, 150, 70, 225);
    $p = fn(float $v) => (int) round($v / 64 * $w);
    // controle: corpo e as duas "pegas"
    imagefilledellipse($big, $p(20), $p(33), $p(20), $p(22), $white);
    imagefilledellipse($big, $p(44), $p(33), $p(20), $p(22), $white);
    imagefilledrectangle($big, $p(20), $p(22), $p(44), $p(39), $white);
    imagefilledellipse($big, $p(15), $p(41), $p(13), $p(15), $white);
    imagefilledellipse($big, $p(49), $p(41), $p(13), $p(15), $white);
    imagefilledrectangle($big, $p(17), $p(29), $p(29), $p(33), $accent);
    imagefilledrectangle($big, $p(21), $p(25), $p(25), $p(37), $accent);
    imagefilledellipse($big, $p(40), $p(34), $p(5), $p(5), $accent);
    imagefilledellipse($big, $p(46), $p(28), $p(5), $p(5), $accent);
    $out = imagecreatetruecolor($s, $s);
    imagealphablending($out, false); imagesavealpha($out, true);
    imagecopyresampled($out, $big, 0, 0, 0, 0, $s, $s, $w, $w);
    ob_start(); imagepng($out); return (string) ob_get_clean();
};
$sizes = [16, 24, 32, 48, 64, 128, 256];
$images = array_map($render, $sizes);
$ico = pack('vvv', 0, 1, count($sizes));
$offset = 6 + 16 * count($sizes);
foreach ($sizes as $i => $s) {
    $ico .= pack('CCCCvvVV', $s % 256, $s % 256, 0, 0, 1, 32, strlen($images[$i]), $offset);
    $offset += strlen($images[$i]);
}
file_put_contents(__DIR__ . '/icone.ico', $ico . implode('', $images));
echo "icone.ico gerado\n";
