<?php

namespace Pterodactyl\Support;

/**
 * Generates a full 50-900 Tailwind-style color ramp from a single hex color by
 * extracting its hue/saturation and applying a fixed lightness curve. This lets
 * an admin pick one color (e.g. via a color picker) and get a usable, legible
 * palette rather than having to hand-pick 10 individual shades.
 */
class ThemeColorGenerator
{
    public const SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];

    // Mirrors the lightness curve already used by the panel's default gray
    // scale, so the "default" theme renders identically to before this
    // feature existed, and any custom color stays legible at every shade.
    public const LIGHTNESS_CURVE = [97, 91, 82, 65, 53, 43, 37, 30, 25, 16];

    public const PRESETS = [
        'default' => [
            'label' => 'Default (Blue)',
            'neutral' => '#64748b',
            'accent' => '#2563eb',
        ],
        'mono' => [
            'label' => 'Black & White',
            'neutral' => '#808080',
            'accent' => '#e5e7eb',
        ],
        'green_black' => [
            'label' => 'Green & Black',
            'neutral' => '#173a26',
            'accent' => '#16a34a',
        ],
        'crimson' => [
            'label' => 'Crimson Night',
            'neutral' => '#64748b',
            'accent' => '#dc2626',
        ],
        'purple_haze' => [
            'label' => 'Purple Haze',
            'neutral' => '#64748b',
            'accent' => '#7c3aed',
        ],
        'sunset' => [
            'label' => 'Sunset Orange',
            'neutral' => '#64748b',
            'accent' => '#ea580c',
        ],
    ];

    /**
     * Returns an assoc array of CSS custom property name => "r g b" triple
     * (space-separated, no wrapping rgb()) for both the neutral and accent
     * ramps, ready to be echoed inside a :root {} block.
     */
    public static function variables(string $neutralHex, string $accentHex): array
    {
        $variables = [];

        foreach (self::ramp($neutralHex) as $shade => $rgb) {
            $variables["--color-neutral-{$shade}"] = $rgb;
        }

        foreach (self::ramp($accentHex) as $shade => $rgb) {
            $variables["--color-primary-{$shade}"] = $rgb;
        }

        return $variables;
    }

    /**
     * Returns [50 => "r g b", 100 => "r g b", ..., 900 => "r g b"] for the
     * given base hex color.
     *
     * @return array<int, string>
     */
    public static function ramp(string $hex): array
    {
        [$hue, $saturation] = self::hexToHueSat($hex);

        $ramp = [];
        foreach (self::SHADES as $index => $shade) {
            $lightness = self::LIGHTNESS_CURVE[$index];
            $ramp[$shade] = self::hslToRgbTriple($hue, $saturation, $lightness);
        }

        return $ramp;
    }

    public static function isValidHex(?string $hex): bool
    {
        return is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1;
    }

    /**
     * @return array{0: float, 1: float} [hue (0-360), saturation (0-100)]
     */
    protected static function hexToHueSat(string $hex): array
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            // Achromatic (pure gray) input — no hue, no saturation.
            return [0.0, 0.0];
        }

        $delta = $max - $min;
        $saturation = $lightness > 0.5 ? $delta / (2 - $max - $min) : $delta / ($max + $min);

        if ($max === $r) {
            $hue = fmod(($g - $b) / $delta, 6);
        } elseif ($max === $g) {
            $hue = ($b - $r) / $delta + 2;
        } else {
            $hue = ($r - $g) / $delta + 4;
        }

        $hue *= 60;
        if ($hue < 0) {
            $hue += 360;
        }

        return [$hue, $saturation * 100];
    }

    protected static function hslToRgbTriple(float $hue, float $saturationPct, float $lightnessPct): string
    {
        $s = $saturationPct / 100;
        $l = $lightnessPct / 100;

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($hue / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($hue < 60) {
            [$r, $g, $b] = [$c, $x, 0];
        } elseif ($hue < 120) {
            [$r, $g, $b] = [$x, $c, 0];
        } elseif ($hue < 180) {
            [$r, $g, $b] = [0, $c, $x];
        } elseif ($hue < 240) {
            [$r, $g, $b] = [0, $x, $c];
        } elseif ($hue < 300) {
            [$r, $g, $b] = [$x, 0, $c];
        } else {
            [$r, $g, $b] = [$c, 0, $x];
        }

        $r = (int) round(($r + $m) * 255);
        $g = (int) round(($g + $m) * 255);
        $b = (int) round(($b + $m) * 255);

        return "{$r} {$g} {$b}";
    }
}
