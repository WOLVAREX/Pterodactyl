@extends('layouts.admin')
@include('partials/admin.settings.nav', ['activeTab' => 'theme'])

@section('title')
    Theme
@endsection

@section('content-header')
    <h1>Panel Theme<small>Pick a preset or build your own color scheme.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Settings</li>
    </ol>
@endsection

@section('content')
    @yield('settings::nav')
    <div class="row">
        <div class="col-xs-12">
            <form action="{{ route('admin.settings.theme') }}" method="POST" id="theme-form">
                {!! csrf_field() !!}
                <input type="hidden" name="_method" value="PATCH">
                <input type="hidden" name="preset" id="theme-preset-input" value="{{ old('preset', $currentPreset) }}">

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Presets</h3>
                    </div>
                    <div class="box-body">
                        <div class="row" id="theme-preset-grid">
                            @foreach($presets as $key => $preset)
                                <div class="col-xs-6 col-sm-4 col-md-2" style="margin-bottom: 15px;">
                                    <div
                                        class="theme-preset-card"
                                        data-preset="{{ $key }}"
                                        data-neutral="{{ $preset['neutral'] }}"
                                        data-accent="{{ $preset['accent'] }}"
                                        style="cursor: pointer; border-radius: 4px; overflow: hidden; border: 2px solid {{ $currentPreset === $key ? '#3c8dbc' : 'transparent' }};"
                                    >
                                        <div style="height: 50px; background: {{ $preset['neutral'] }};"></div>
                                        <div style="height: 12px; background: {{ $preset['accent'] }};"></div>
                                        <p class="text-center" style="margin: 6px 0; font-size: 12px;">{{ $preset['label'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-muted"><small>Click a preset to load it below, then adjust the colors if you want something custom, or just hit Save to use the preset as-is.</small></p>
                    </div>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Customize</h3>
                    </div>
                    <div class="box-body">
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label class="control-label">Base / Neutral Color</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="color" id="theme-neutral-picker" value="{{ old('neutral', $neutral) }}" style="width: 50px; height: 38px; padding: 2px; border: 1px solid #d2d6de;">
                                    <input type="text" class="form-control" name="neutral" id="theme-neutral-text" value="{{ old('neutral', $neutral) }}" maxlength="7">
                                </div>
                                <p class="text-muted"><small>Drives backgrounds, panels, sidebars, borders and text — this is the biggest driver of the overall look.</small></p>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="control-label">Accent Color</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="color" id="theme-accent-picker" value="{{ old('accent', $accent) }}" style="width: 50px; height: 38px; padding: 2px; border: 1px solid #d2d6de;">
                                    <input type="text" class="form-control" name="accent" id="theme-accent-text" value="{{ old('accent', $accent) }}" maxlength="7">
                                </div>
                                <p class="text-muted"><small>Drives buttons, links, active navigation states and highlights throughout the panel.</small></p>
                            </div>
                        </div>

                        <label class="control-label">Live Preview</label>
                        <div id="theme-preview" style="border-radius: 6px; padding: 20px; display: flex; gap: 16px;">
                            <div id="theme-preview-sidebar" style="width: 140px; border-radius: 6px; padding: 12px; flex-shrink: 0;">
                                <div id="theme-preview-nav-active" style="border-radius: 4px; padding: 8px; margin-bottom: 8px; font-size: 12px;">Console</div>
                                <div style="padding: 8px; font-size: 12px; opacity: 0.7;">Files</div>
                                <div style="padding: 8px; font-size: 12px; opacity: 0.7;">Databases</div>
                            </div>
                            <div id="theme-preview-panel" style="flex: 1; border-radius: 6px; padding: 16px;">
                                <p style="margin: 0 0 12px 0; font-size: 13px; opacity: 0.8;">Panel content and card surfaces look like this.</p>
                                <button type="button" id="theme-preview-button" style="border: none; border-radius: 4px; padding: 8px 16px; font-size: 12px; text-transform: uppercase;">Primary Button</button>
                            </div>
                        </div>
                    </div>
                    <div class="box-footer">
                        <button type="submit" class="btn btn-sm btn-primary pull-right">Save Theme</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('footer-scripts')
    @parent
    <script>
        (function () {
            var LIGHTNESS_CURVE = [97, 91, 82, 65, 53, 43, 37, 30, 25, 16];
            var SHADES = [50, 100, 200, 300, 400, 500, 600, 700, 800, 900];

            function hexToHueSat(hex) {
                hex = hex.replace('#', '');
                var r = parseInt(hex.substring(0, 2), 16) / 255;
                var g = parseInt(hex.substring(2, 4), 16) / 255;
                var b = parseInt(hex.substring(4, 6), 16) / 255;
                var max = Math.max(r, g, b), min = Math.min(r, g, b);
                var l = (max + min) / 2;
                if (max === min) {
                    return [0, 0];
                }
                var d = max - min;
                var s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
                var h;
                if (max === r) {
                    h = ((g - b) / d) % 6;
                } else if (max === g) {
                    h = (b - r) / d + 2;
                } else {
                    h = (r - g) / d + 4;
                }
                h *= 60;
                if (h < 0) h += 360;
                return [h, s * 100];
            }

            function hslToHex(h, sPct, lPct) {
                var s = sPct / 100, l = lPct / 100;
                var c = (1 - Math.abs(2 * l - 1)) * s;
                var x = c * (1 - Math.abs((h / 60) % 2 - 1));
                var m = l - c / 2;
                var r, g, b;
                if (h < 60) { r = c; g = x; b = 0; }
                else if (h < 120) { r = x; g = c; b = 0; }
                else if (h < 180) { r = 0; g = c; b = x; }
                else if (h < 240) { r = 0; g = x; b = c; }
                else if (h < 300) { r = x; g = 0; b = c; }
                else { r = c; g = 0; b = x; }
                var toHex = function (v) {
                    var n = Math.round((v) * 255).toString(16);
                    return n.length === 1 ? '0' + n : n;
                };
                return '#' + toHex(r + m) + toHex(g + m) + toHex(b + m);
            }

            function ramp(hex) {
                var hs = hexToHueSat(hex);
                var out = {};
                SHADES.forEach(function (shade, i) {
                    out[shade] = hslToHex(hs[0], hs[1], LIGHTNESS_CURVE[i]);
                });
                return out;
            }

            var neutralPicker = document.getElementById('theme-neutral-picker');
            var neutralText = document.getElementById('theme-neutral-text');
            var accentPicker = document.getElementById('theme-accent-picker');
            var accentText = document.getElementById('theme-accent-text');
            var presetInput = document.getElementById('theme-preset-input');

            function updatePreview() {
                var neutral = ramp(neutralText.value);
                var accent = ramp(accentText.value);

                document.getElementById('theme-preview').style.background = neutral[900];
                document.getElementById('theme-preview-sidebar').style.background = neutral[900];
                document.getElementById('theme-preview-sidebar').style.border = '1px solid ' + neutral[700];
                document.getElementById('theme-preview-panel').style.background = neutral[800];
                document.getElementById('theme-preview-panel').style.color = neutral[100];
                document.getElementById('theme-preview-nav-active').style.background = accent[600];
                document.getElementById('theme-preview-nav-active').style.color = '#ffffff';
                document.getElementById('theme-preview-button').style.background = accent[500];
                document.getElementById('theme-preview-button').style.color = '#ffffff';

                document.querySelectorAll('.theme-preset-card').forEach(function (card) {
                    card.style.borderColor = 'transparent';
                });
            }

            neutralPicker.addEventListener('input', function () {
                neutralText.value = neutralPicker.value;
                presetInput.value = 'custom';
                updatePreview();
            });
            accentPicker.addEventListener('input', function () {
                accentText.value = accentPicker.value;
                presetInput.value = 'custom';
                updatePreview();
            });
            neutralText.addEventListener('input', function () {
                if (/^#[0-9a-fA-F]{6}$/.test(neutralText.value)) {
                    neutralPicker.value = neutralText.value;
                    presetInput.value = 'custom';
                    updatePreview();
                }
            });
            accentText.addEventListener('input', function () {
                if (/^#[0-9a-fA-F]{6}$/.test(accentText.value)) {
                    accentPicker.value = accentText.value;
                    presetInput.value = 'custom';
                    updatePreview();
                }
            });

            document.querySelectorAll('.theme-preset-card').forEach(function (card) {
                card.addEventListener('click', function () {
                    var neutral = card.getAttribute('data-neutral');
                    var accent = card.getAttribute('data-accent');
                    var preset = card.getAttribute('data-preset');

                    neutralPicker.value = neutral;
                    neutralText.value = neutral;
                    accentPicker.value = accent;
                    accentText.value = accent;
                    presetInput.value = preset;

                    document.querySelectorAll('.theme-preset-card').forEach(function (c) {
                        c.style.borderColor = 'transparent';
                    });
                    card.style.borderColor = '#3c8dbc';

                    updatePreview();
                });
            });

            updatePreview();
        })();
    </script>
@endsection
