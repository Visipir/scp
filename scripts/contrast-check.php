<?php

/**
 * Contrast Checker for SCP Auth & Guest Design Tokens (WCAG 2.1 AA)
 * Verifies that text, UI components, borders, and overlay elements satisfy contrast requirements.
 */

function srgbToLinear(float $val): float {
    $c = $val / 255.0;
    return $c <= 0.04045 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
}

function hexToRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function relativeLuminance(array $rgb): float {
    return 0.2126 * srgbToLinear($rgb[0]) + 0.7152 * srgbToLinear($rgb[1]) + 0.0722 * srgbToLinear($rgb[2]);
}

function contrastRatio(array|string $c1, array|string $c2): float {
    $rgb1 = is_string($c1) ? hexToRgb($c1) : $c1;
    $rgb2 = is_string($c2) ? hexToRgb($c2) : $c2;

    $l1 = relativeLuminance($rgb1);
    $l2 = relativeLuminance($rgb2);

    $lighter = max($l1, $l2);
    $darker = min($l1, $l2);

    return ($lighter + 0.05) / ($darker + 0.05);
}

function blendRgb(array $fg, float $alpha, array $bg): array {
    return [
        (int) round($alpha * $fg[0] + (1.0 - $alpha) * $bg[0]),
        (int) round($alpha * $fg[1] + (1.0 - $alpha) * $bg[1]),
        (int) round($alpha * $fg[2] + (1.0 - $alpha) * $bg[2]),
    ];
}

$themes = [
    'Dark Theme' => [
        'bg'                => '#1a1a1e',
        'surface'           => '#1D1D21',
        'text'              => '#EDEDF0',
        'muted'             => '#A1A1A6',
        'border'            => '#6E6E78',
        'input_bg'          => '#151518',
        'input_border'      => '#6E6E78',
        'input_text'        => '#EDEDF0',
        'input_placeholder' => '#9E9EA4',
        'link'              => '#38BDF8',
        'btn_bg'            => '#007BB5',
        'btn_text'          => '#FFFFFF',
        'btn_hover'         => '#006699',
        'btn_disabled'      => '#38383E',
        'ring'              => '#38BDF8',
        'error'             => '#F87171',
        'success'           => '#34D399',
    ],
    'Light Theme' => [
        'bg'                => '#F4F4F6',
        'surface'           => '#FFFFFF',
        'text'              => '#161619',
        'muted'             => '#52525B',
        'border'            => '#8C8C91',
        'input_bg'          => '#FFFFFF',
        'input_border'      => '#8C8C91',
        'input_text'        => '#161619',
        'input_placeholder' => '#71717A',
        'link'              => '#0273B2',
        'btn_bg'            => '#007BB5',
        'btn_text'          => '#FFFFFF',
        'btn_hover'         => '#006699',
        'btn_disabled'      => '#E4E4E7',
        'ring'              => '#0273B2',
        'error'             => '#DC2626',
        'success'           => '#059669',
    ],
];

$allPassed = true;

echo str_repeat('=', 80) . "\n";
echo " SCP AUTH DESIGN TOKENS WCAG 2.1 CONTRAST VERIFICATION\n";
echo str_repeat('=', 80) . "\n";

foreach ($themes as $themeName => $t) {
    echo "\n>>> {$themeName}\n";
    printf("%-38s | %-16s | %-8s | %-6s | %s\n", "Element Pair", "Colors", "Ratio", "Target", "Status");
    echo str_repeat('-', 80) . "\n";

    $checks = [
        ['body text on background', $t['text'], $t['bg'], 4.5],
        ['muted text on background', $t['muted'], $t['bg'], 4.5],
        ['label on surface', $t['text'], $t['surface'], 4.5],
        ['input text on input background', $t['input_text'], $t['input_bg'], 4.5],
        ['placeholder on input background', $t['input_placeholder'], $t['input_bg'], 4.5],
        ['link on surface', $t['link'], $t['surface'], 4.5],
        ['primary button text on button bg', $t['btn_text'], $t['btn_bg'], 4.5],
        ['primary button text on btn hover', $t['btn_text'], $t['btn_hover'], 4.5],
        ['error text on surface', $t['error'], $t['surface'], 4.5],
        ['focus ring against surface', $t['ring'], $t['surface'], 3.0],
        ['border against surface', $t['border'], $t['surface'], 3.0],
    ];

    foreach ($checks as [$label, $fg, $bg, $target]) {
        $ratio = contrastRatio($fg, $bg);
        $pass = $ratio >= $target;
        if (!$pass) $allPassed = false;
        $status = $pass ? "PASS" : "FAIL";
        printf("%-38s | %7s / %-7s | %6.2f:1 | %4.1f:1 | [%s]\n",
            $label, $fg, $bg, $ratio, $target, $status
        );
    }
}

// Overlay Worst-Case Image Checks (Common to both modes)
echo "\n>>> Image Panel Overlay Worst-Case Checks\n";
printf("%-38s | %-16s | %-8s | %-6s | %s\n", "Element Pair", "Underlying Image", "Ratio", "Target", "Status");
echo str_repeat('-', 80) . "\n";

// Dark overlay: rgba(10, 16, 26, 0.75) with white text #FFFFFF
$overlayCol = [10, 16, 26];
$alpha = 0.75;
$whiteText = [255, 255, 255];

$worstCases = [
    ['overlay text over pure white image', [255, 255, 255], 4.5],
    ['overlay text over bright yellow (#FFFF00)', [255, 255, 0], 4.5],
    ['overlay text over pure black image', [0, 0, 0], 4.5],
    ['overlay text over dark blue (#0A192F)', [10, 25, 47], 4.5],
];

foreach ($worstCases as [$label, $bgCol, $target]) {
    $blended = blendRgb($overlayCol, $alpha, $bgCol);
    $ratio = contrastRatio($whiteText, $blended);
    $pass = $ratio >= $target;
    if (!$pass) $allPassed = false;
    $status = $pass ? "PASS" : "FAIL";
    printf("%-38s | RGB(%3d,%3d,%3d)   | %6.2f:1 | %4.1f:1 | [%s]\n",
        $label, $bgCol[0], $bgCol[1], $bgCol[2], $ratio, $target, $status
    );
}

echo str_repeat('=', 80) . "\n";
if ($allPassed) {
    echo " RESULT: ALL 26 CONTRAST CHECKS PASSED (WCAG AA COMPLIANT)\n";
    echo str_repeat('=', 80) . "\n";
    exit(0);
} else {
    echo " RESULT: ONE OR MORE CONTRAST CHECKS FAILED\n";
    echo str_repeat('=', 80) . "\n";
    exit(1);
}
