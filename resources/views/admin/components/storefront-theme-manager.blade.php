@php
    $theme = old('storefront_theme', $storefrontTheme ?? []);
    $themeDefaults = $storefrontThemeDefaults ?? [];
    $themeGroups = $storefrontThemeGroups ?? [];
    $themePresets = $storefrontThemePresets ?? [];
    $themePresetPalettes = $storefrontThemePresetPalettes ?? [];
    $themeContrast = $storefrontThemeContrast ?? [];

    $themeSchema = [];
    foreach ($themeGroups as $groupKey => $group) {
        $fields = [];
        foreach (($group['fields'] ?? []) as $field) {
            $fields[] = [
                'key' => $field['key'],
                'label' => $field['label'] ?? $field['key'],
                'type' => $field['type'] ?? 'text',
                'default' => $field['default'] ?? '',
                'options' => $field['options'] ?? [],
                'help' => $field['help'] ?? '',
            ];
        }

        $themeSchema[$groupKey] = [
            'label' => $group['label'] ?? $groupKey,
            'description' => $group['description'] ?? '',
            'use_global' => !empty($group['use_global']),
            'fields' => $fields,
        ];
    }

    $themePresetValue = old('storefront_theme.preset', $theme['preset'] ?? ($themeDefaults['preset'] ?? 'lucent-tech-bd'));
@endphp

<style>
.ws-theme-manager{display:grid;gap:18px}
.ws-theme-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;padding:18px 20px;border:1px solid #dfe8ed;border-radius:12px;background:linear-gradient(135deg,#0b3d62,#123e59);color:#fff;box-shadow:0 10px 28px rgba(16,61,85,.12)}
.ws-theme-hero small{display:block;margin:0 0 6px;color:#9bd3ff;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}
.ws-theme-hero h2{margin:0 0 6px;color:#fff;font-size:22px;line-height:1.2}
.ws-theme-hero p{margin:0;max-width:760px;color:rgba(255,255,255,.84);font-size:13px;line-height:1.5}
.ws-theme-hero-actions{flex:0 0 auto}
.ws-theme-hero-actions .btn{border:0;border-radius:7px;padding:9px 14px}
.ws-theme-layout{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,.9fr);gap:18px;align-items:start}
.ws-theme-main{display:grid;gap:16px}
.ws-theme-side{display:grid;gap:16px;position:sticky;top:72px}
.ws-theme-card{background:#fff;border:1px solid #dfe8ed;border-radius:12px;box-shadow:0 4px 16px rgba(30,58,76,.06);overflow:hidden}
.ws-theme-card-head{padding:16px 18px;border-bottom:1px solid #e7eef2;display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.ws-theme-card-head h3{margin:0;color:#173f56;font-size:17px;line-height:1.25}
.ws-theme-card-head p{margin:4px 0 0;color:#71828c;font-size:12px;line-height:1.45}
.ws-theme-card-actions{flex:0 0 auto;display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}
.ws-theme-card-body{padding:18px}
.ws-theme-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 16px}
.ws-theme-grid--single{grid-template-columns:1fr}
.ws-theme-grid--compact .ws-theme-field{min-width:0}
.ws-theme-field{border:1px solid #e3eaee;border-radius:10px;padding:12px;background:#fdfefe}
.ws-theme-field.has-error{border-color:#d9534f;background:#fff8f8}
.ws-theme-field.has-error .ws-theme-field-head label{color:#b52b27}
.ws-theme-field-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}
.ws-theme-field-head label{margin:0;color:#264559;font-size:12px;font-weight:700;line-height:1.35}
.ws-theme-field-head .btn-mini{padding:4px 8px;font-size:11px;line-height:1.2}
.ws-theme-field input[type="text"],.ws-theme-field select{box-sizing:border-box;width:100%;min-height:38px;border:1px solid #cfdbe1;border-radius:8px;padding:8px 10px;background:#fff;box-shadow:none}
.ws-theme-color-inputs{display:grid;grid-template-columns:56px minmax(0,1fr);gap:8px;align-items:center}
.ws-theme-color-inputs input[type="color"]{width:56px;height:38px;padding:2px;border:1px solid #cfdbe1;border-radius:8px;background:#fff;box-shadow:none}
.ws-theme-color-meta{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:7px;font-size:11px;line-height:1.35;color:#748391}
.ws-theme-field .ws-help{display:block;margin-top:6px;color:#748391;font-size:11px;line-height:1.45}
.ws-theme-field .ws-resize-toggle{margin:0;cursor:pointer}
.ws-theme-field .ws-resize-toggle strong,.ws-theme-field .ws-resize-toggle small{display:block}
.ws-theme-field .ws-resize-toggle strong{color:#264559;font-size:12px}
.ws-theme-field .ws-resize-toggle small{margin-top:2px;color:#748391;font-size:11px;font-weight:400}
.ws-theme-field.is-muted{opacity:.55}
.ws-theme-toggle-row{grid-column:1/-1}
.ws-theme-toggle-row .ws-theme-field{background:#f7fafb}
.ws-theme-note-row{display:flex;gap:12px;align-items:center;justify-content:space-between;padding:0 2px 2px}
.ws-theme-note-row strong{display:block;color:#173f56;font-size:12px}
.ws-theme-note-row small{display:block;color:#748391;font-size:11px;line-height:1.4}
.ws-theme-presets{display:flex;flex-wrap:wrap;gap:8px}
.ws-theme-preset-button{display:inline-flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid #d9e4ea;border-radius:999px;background:#fff;color:#355164;font-size:12px;font-weight:700}
.ws-theme-preset-button.is-active{background:#eaf6fa;border-color:#b9dce9;color:#116381}
.ws-theme-preset-swatch{width:10px;height:10px;border-radius:999px;display:inline-block;flex:0 0 10px;border:1px solid rgba(0,0,0,.08)}
.ws-theme-summary{margin-top:10px;padding:10px 12px;border-radius:8px;background:#f6fbfd;border:1px solid #dcebf1;color:#355164;font-size:12px;line-height:1.45}
.ws-theme-summary strong{color:#173f56}
.ws-theme-contrast-list{display:grid;gap:10px}
.ws-theme-contrast-row{padding:12px;border:1px solid #e3eaee;border-radius:10px;background:#fdfefe;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:2px 10px;align-items:start}
.ws-theme-contrast-row strong{color:#173f56;font-size:12px}
.ws-theme-contrast-row span{justify-self:end;font-weight:800;font-size:12px}
.ws-theme-contrast-row small,.ws-theme-contrast-row em{grid-column:1/-1;font-style:normal;color:#748391;font-size:11px;line-height:1.35}
.ws-theme-contrast-row.is-good{background:#f3fbf5;border-color:#c1e6cb}
.ws-theme-contrast-row.is-acceptable{background:#fdfaf2;border-color:#f0dbac}
.ws-theme-contrast-row.is-poor{background:#fef5f5;border-color:#efc3c3}
.ws-theme-status-list{display:grid;gap:8px}
.ws-theme-status-row{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border:1px solid #e3eaee;border-radius:10px;background:#fdfefe}
.ws-theme-status-row strong{color:#173f56;font-size:12px}
.ws-theme-status-row span{color:#748391;font-size:11px;font-weight:700}
.ws-theme-status-row.is-active{border-color:#b9dce9;background:#eef8fb}
.ws-theme-status-row.is-active span{color:#116381}
.ws-theme-preview{--preview-shadow:0 18px 35px rgba(7,20,32,.10);overflow:hidden;border-radius:16px;background:var(--theme-page-bg,#fff);border:1px solid var(--theme-card-border,#dfe8ed);box-shadow:var(--preview-shadow)}
.ws-theme-preview-topbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 14px;background:var(--tb-bg,#073451);color:var(--tb-text,#fff);font-size:11px;font-weight:700}
.ws-theme-preview-topbar a{color:var(--tb-link,#fff);text-decoration:none}
.ws-theme-preview-topbar a:hover{color:var(--tb-link-hover,#f5821f)}
.ws-theme-preview-header{display:grid;grid-template-columns:minmax(140px,1fr) minmax(0,1.45fr) auto;gap:12px;align-items:center;padding:14px;background:var(--theme-header-bg,#0b2742);color:var(--theme-header-text,#fff)}
.ws-theme-preview-brand strong{display:block;color:inherit;font-size:19px;line-height:1.1}
.ws-theme-preview-brand small{display:block;margin-top:2px;color:rgba(255,255,255,.78);font-size:11px;line-height:1.35}
.ws-theme-preview-search{display:grid;grid-template-columns:minmax(0,1fr) 44px;align-items:stretch;border:1px solid var(--theme-search-border,#0b3d62);border-radius:8px;overflow:hidden;background:var(--theme-search-bg,#fff)}
.ws-theme-preview-search input{border:0;background:transparent;color:var(--theme-search-text,#152536);padding:10px 12px;font-size:12px;min-height:42px}
.ws-theme-preview-search input::placeholder{color:var(--theme-search-placeholder,#7b8a97)}
.ws-theme-preview-search button{border:0;background:var(--theme-search-button-bg,#0b3d62);color:var(--theme-search-button-icon,#fff)}
.ws-theme-preview-search button:hover{background:var(--theme-search-button-hover,#f5821f)}
.ws-theme-preview-actions{display:flex;align-items:center;justify-content:flex-end;gap:12px;color:var(--theme-header-link,#fff);font-size:11px;font-weight:700;flex-wrap:wrap}
.ws-theme-preview-actions span{display:inline-flex;align-items:center;gap:6px}
.ws-theme-preview-actions i{color:var(--theme-actions-icon,#f5821f)}
.ws-theme-preview-badge{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:var(--theme-badge-bg,#f5821f);color:var(--theme-badge-text,#fff);font-size:10px;line-height:1;font-weight:800}
.ws-theme-preview-cta{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 14px;border-radius:7px;background:var(--theme-pc-builder-bg,#f5821f);color:var(--theme-pc-builder-text,#fff);font-size:12px;font-weight:800;text-decoration:none;border:1px solid var(--theme-pc-builder-border,#f5821f)}
.ws-theme-preview-cta:hover{background:var(--theme-pc-builder-hover-bg,#ff9b43);color:var(--theme-pc-builder-hover-text,#fff)}
.ws-theme-preview-nav{display:flex;gap:14px;align-items:center;overflow-x:auto;padding:10px 14px;background:var(--theme-nav-bg,#0b3d62);color:var(--theme-nav-text,#fff);border-top:1px solid var(--theme-nav-border,#f5821f);border-bottom:1px solid var(--theme-nav-border,#f5821f);font-size:11px;font-weight:700;white-space:nowrap}
.ws-theme-preview-nav span{padding:4px 0;border-bottom:2px solid transparent}
.ws-theme-preview-nav span:first-child{border-bottom-color:var(--theme-nav-active-bg,#f5821f);color:var(--theme-nav-active-text,#fff)}
.ws-theme-preview-body{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:14px;background:var(--theme-page-bg,#fff)}
.ws-theme-preview-card{position:relative;padding:14px;border:1px solid var(--theme-card-border,#dfe8ed);border-radius:12px;background:var(--theme-card-bg,#fff)}
.ws-theme-preview-chip{display:inline-flex;align-items:center;padding:4px 8px;border-radius:999px;background:var(--theme-card-discount-badge,#f5821f);color:var(--theme-badge-text,#fff);font-size:10px;font-weight:800}
.ws-theme-preview-chip--secondary{background:var(--theme-button-secondary-bg,#123f61)}
.ws-theme-preview-card h4{margin:12px 0 4px;color:var(--theme-card-title,#152536);font-size:15px;line-height:1.25}
.ws-theme-preview-card p{margin:0 0 10px;color:var(--theme-card-price,#f5821f);font-size:18px;font-weight:800}
.ws-theme-preview-button{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:0 12px;border-radius:6px;border:1px solid var(--theme-button-primary-border,#0b3d62);background:var(--theme-button-primary-bg,#0b3d62);color:var(--theme-button-primary-text,#fff);font-size:12px;font-weight:800}
.ws-theme-preview-button--secondary{background:var(--theme-button-secondary-bg,#123f61);border-color:var(--theme-button-secondary-border,#123f61)}
.ws-theme-preview-footer{padding:14px;background:var(--theme-footer-bg,#072b47);color:var(--theme-footer-text,#b9ccdc);border-top:1px solid var(--theme-footer-border,rgba(255,255,255,.12))}
.ws-theme-preview-footer strong{display:block;color:var(--theme-footer-heading,#fff);font-size:13px}
.ws-theme-preview-footer p{margin:4px 0 0;font-size:11px;line-height:1.45}
.ws-theme-preview{font-family:var(--theme-body-font-family,system-ui,sans-serif);font-size:var(--theme-body-font-size,14px)}
.ws-theme-preview-topbar{font-family:var(--theme-topbar-font-family,system-ui,sans-serif);font-size:var(--theme-topbar-font-size,13px)}
.ws-theme-preview-header{font-family:var(--theme-header-font-family,system-ui,sans-serif);font-size:var(--theme-header-font-size,14px)}
.ws-theme-preview-search,.ws-theme-preview-search input{font-family:var(--theme-search-font-family,system-ui,sans-serif);font-size:var(--theme-search-font-size,14px)}
.ws-theme-preview-actions{font-family:var(--theme-actions-font-family,system-ui,sans-serif);font-size:var(--theme-actions-font-size,12px)}
.ws-theme-preview-badge,.ws-theme-preview-chip{font-family:var(--theme-badges-font-family,system-ui,sans-serif);font-size:var(--theme-badges-font-size,11px)}
.ws-theme-preview-cta{font-family:var(--theme-pc-builder-font-family,system-ui,sans-serif);font-size:var(--theme-pc-builder-font-size,13px)}
.ws-theme-preview-nav{font-family:var(--theme-navigation-font-family,system-ui,sans-serif);font-size:var(--theme-navigation-font-size,14px)}
.ws-theme-preview-card{font-family:var(--theme-cards-font-family,system-ui,sans-serif);font-size:var(--theme-cards-font-size,14px)}
.ws-theme-preview-button{font-family:var(--theme-buttons-font-family,system-ui,sans-serif);font-size:var(--theme-buttons-font-size,14px)}
.ws-theme-preview-footer{font-family:var(--theme-footer-font-family,system-ui,sans-serif);font-size:var(--theme-footer-font-size,14px)}
.ws-theme-preview-note{margin-top:10px;padding:10px 12px;border-radius:8px;background:#f6fbfd;border:1px solid #dcebf1;color:#597080;font-size:11px;line-height:1.45}
@media(max-width:1200px){.ws-theme-layout{grid-template-columns:1fr}.ws-theme-side{position:static}}
@media(max-width:900px){.ws-theme-grid{grid-template-columns:1fr}.ws-theme-preview-header{grid-template-columns:1fr}.ws-theme-preview-actions{justify-content:flex-start}}
@media(max-width:680px){.ws-theme-hero{align-items:flex-start;flex-direction:column}.ws-theme-card-head{flex-direction:column}.ws-theme-card-actions{justify-content:flex-start}.ws-theme-preview-body{grid-template-columns:1fr}}
</style>

<div class="ws-theme-manager" data-theme-manager>
    <div class="ws-theme-hero">
        <div>
            <small>Storefront visual system</small>
            <h2>Website Colors, Fonts and Text Sizes</h2>
            <p>Choose a ready-made style or change the colors, fonts, and text sizes used on each part of the website.</p>
        </div>
        <div class="ws-theme-hero-actions">
            <button type="button" class="btn btn-warning" data-theme-reset-all><i class="icon-refresh"></i> Restore all defaults</button>
        </div>
    </div>

    <div class="ws-theme-layout">
        <div class="ws-theme-main">
            <div class="ws-theme-card">
                <div class="ws-theme-card-head">
                    <div>
                        <h3>Quick Style</h3>
                        <p>Choose a ready-made color style, then adjust individual website sections below.</p>
                    </div>
                    <div class="ws-theme-card-actions">
                        <button type="button" class="btn btn-mini" data-theme-reset-preset>Restore quick style</button>
                    </div>
                </div>
                <div class="ws-theme-card-body">
                    <div class="ws-theme-grid ws-theme-grid--single">
                        <div class="ws-theme-field" data-theme-field-row="preset" data-theme-field-type="select" data-theme-default="{{ $themeDefaults['preset'] ?? 'lucent-tech-bd' }}">
                            <div class="ws-theme-field-head">
                                <label for="storefront-theme-preset">Choose a quick style</label>
                                <button type="button" class="btn btn-mini" data-theme-reset-field="preset" data-theme-default="{{ $themeDefaults['preset'] ?? 'lucent-tech-bd' }}">Use default</button>
                            </div>
                            <select id="storefront-theme-preset" name="storefront_theme[preset]" data-theme-input="preset">
                                @foreach($themePresets as $presetKey => $presetLabel)
                                    <option value="{{ $presetKey }}" {{ (string) $themePresetValue === (string) $presetKey ? 'selected' : '' }}>{{ $presetLabel }}</option>
                                @endforeach
                            </select>
                            <div class="ws-theme-presets" style="margin-top:10px">
                                @foreach($themePresets as $presetKey => $presetLabel)
                                    <button type="button" class="ws-theme-preset-button {{ (string) $themePresetValue === (string) $presetKey ? 'is-active' : '' }}" data-theme-apply-preset="{{ $presetKey }}">
                                        <span class="ws-theme-preset-swatch" style="background: {{ $themePresetPalettes[$presetKey]['global_primary'] ?? '#0b3d62' }}"></span>
                                        <span>{{ $presetLabel }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div class="ws-theme-summary" data-theme-preset-summary>
                                <strong data-theme-preset-name>{{ $themePresets[$themePresetValue] ?? ($themePresets[$themeDefaults['preset'] ?? 'lucent-tech-bd'] ?? 'Current preset') }}</strong>
                                <span>Use this shortcut for a quick website color refresh before editing individual colors.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ws-theme-card">
                <div class="ws-theme-card-head">
                    <div>
                        <h3>Global brand palette</h3>
                        <p>These values drive the shared storefront colors and every section that uses the global theme.</p>
                    </div>
                </div>
                <div class="ws-theme-card-body">
                    <div class="ws-theme-grid">
                        @foreach(($themeGroups['global']['fields'] ?? []) as $field)
                            @include('admin.components.theme-color-field', ['field' => $field, 'sectionKey' => 'global', 'storefrontTheme' => $theme, 'storefrontThemeDefaults' => $themeDefaults])
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="ws-theme-card">
                <div class="ws-theme-card-head">
                    <div>
                        <h3>Logo variants</h3>
                        <p>Choose which logo path should be preferred on light or dark surfaces.</p>
                    </div>
                </div>
                <div class="ws-theme-card-body">
                    <div class="ws-theme-grid">
                        @foreach(($themeGroups['branding']['fields'] ?? []) as $field)
                            @include('admin.components.theme-color-field', ['field' => $field, 'sectionKey' => 'branding', 'storefrontTheme' => $theme, 'storefrontThemeDefaults' => $themeDefaults])
                        @endforeach
                    </div>
                </div>
            </div>

            @foreach($themeSchema as $sectionKey => $group)
                @if(!in_array($sectionKey, ['global', 'branding'], true))
                    @php
                        $sectionFields = [];
                        $sectionToggle = null;
                        foreach ($group['fields'] as $field) {
                            if (($field['key'] ?? '') && substr($field['key'], -11) === '_use_global') {
                                $sectionToggle = $field;
                                continue;
                            }
                            $sectionFields[] = $field;
                        }
                    @endphp
                    <section class="ws-theme-card ws-theme-section" data-theme-section-card="{{ $sectionKey }}">
                        <div class="ws-theme-card-head">
                            <div>
                                <h3>{{ $group['label'] }}</h3>
                                <p>{{ $group['description'] }}</p>
                            </div>
                            <div class="ws-theme-card-actions">
                                <button type="button" class="btn btn-mini" data-theme-reset-section="{{ $sectionKey }}">Restore section defaults</button>
                            </div>
                        </div>
                        <div class="ws-theme-card-body">
                            @if($sectionToggle)
                                <div class="ws-theme-toggle-row">
                                    @include('admin.components.theme-color-field', ['field' => $sectionToggle, 'sectionKey' => $sectionKey, 'storefrontTheme' => $theme, 'storefrontThemeDefaults' => $themeDefaults])
                                </div>
                            @endif
                            <div class="ws-theme-grid" data-theme-section-fields="{{ $sectionKey }}">
                                @foreach($sectionFields as $field)
                                    @include('admin.components.theme-color-field', ['field' => $field, 'sectionKey' => $sectionKey, 'storefrontTheme' => $theme, 'storefrontThemeDefaults' => $themeDefaults])
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            @endforeach
        </div>

        <aside class="ws-theme-side">
            <div class="ws-theme-card">
                <div class="ws-theme-card-head">
                    <div>
                        <h3>Live storefront preview</h3>
                        <p>The header, menu, cards, and footer update to match the currently selected theme values.</p>
                    </div>
                </div>
                <div class="ws-theme-card-body">
                    <div class="ws-theme-preview" data-theme-preview-root>
                        <div class="ws-theme-preview-topbar">
                            <span>Free Delivery</span>
                            <a href="#">Support</a>
                        </div>
                        <header class="ws-theme-preview-header">
                            <div class="ws-theme-preview-brand">
                                <strong>{{ $brandName ?? 'LucentTech BD' }}</strong>
                                <small>{{ $brandTagline ?? 'Technology that works for you' }}</small>
                            </div>
                            <div class="ws-theme-preview-search">
                                <input type="text" value="Search by product name or model" aria-hidden="true" disabled>
                                <button type="button" aria-label="Search preview"><i class="icon-search"></i></button>
                            </div>
                            <div class="ws-theme-preview-actions">
                                <span><i class="icon-heart"></i> Wishlist</span>
                                <span><i class="icon-user"></i> Account</span>
                                <span><i class="icon-sort-by-alphabet"></i> Compare <span class="ws-theme-preview-badge">0</span></span>
                                <a class="ws-theme-preview-cta" href="#">PC Builder</a>
                            </div>
                        </header>
                        <nav class="ws-theme-preview-nav" aria-label="Preview categories">
                            <span>Desktop</span>
                            <span>Laptop</span>
                            <span>Component</span>
                            <span>Monitor</span>
                            <span>Power</span>
                            <span>Phone</span>
                            <span>More</span>
                        </nav>
                        <div class="ws-theme-preview-body">
                            <article class="ws-theme-preview-card">
                                <div class="ws-theme-preview-chip">-15%</div>
                                <h4>Gaming Laptop</h4>
                                <p>৳ 82,500</p>
                                <button type="button" class="ws-theme-preview-button">Add to cart</button>
                            </article>
                            <article class="ws-theme-preview-card">
                                <div class="ws-theme-preview-chip ws-theme-preview-chip--secondary">Bestseller</div>
                                <h4>Wi-Fi Router</h4>
                                <p>৳ 4,250</p>
                                <button type="button" class="ws-theme-preview-button ws-theme-preview-button--secondary">View product</button>
                            </article>
                        </div>
                        <footer class="ws-theme-preview-footer">
                            <strong>Store footer</strong>
                            <p>Footer colors, links, and notices respond to the same saved theme settings.</p>
                        </footer>
                    </div>
                    <div class="ws-theme-preview-note">The preview mirrors the storefront structure so you can see the menu, CTA buttons, and footer treatment before saving.</div>
                </div>
            </div>

            <div class="ws-theme-card">
                <div class="ws-theme-card-head">
                    <div>
                        <h3>Contrast snapshot</h3>
                        <p>Helpful checks for the most visible text/background pairs.</p>
                    </div>
                </div>
                <div class="ws-theme-card-body">
                    <div class="ws-theme-contrast-list">
                        @foreach($themeContrast as $label => $item)
                            @php
                                $contrastKey = [
                                    'Top Bar' => 'top-bar',
                                    'Header' => 'header',
                                    'Search' => 'search',
                                    'Buttons' => 'buttons',
                                    'Footer' => 'footer',
                                ][$label] ?? \Illuminate\Support\Str::slug($label);
                            @endphp
                            <div class="ws-theme-contrast-row {{ strtolower((string) ($item['level'] ?? '')) === 'good' ? 'is-good' : (strtolower((string) ($item['level'] ?? '')) === 'acceptable' ? 'is-acceptable' : 'is-poor') }}" data-theme-contrast-row="{{ $contrastKey }}">
                                <strong>{{ $label }}</strong>
                                <span data-theme-contrast-level>{{ $item['level'] ?? 'Poor' }}</span>
                                <small data-theme-contrast-ratio>{{ number_format((float) ($item['ratio'] ?? 0), 1) }}:1</small>
                                <em data-theme-contrast-suggestion>{{ $item['suggested_text'] ?? '#ffffff' }}</em>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="ws-theme-card">
                <div class="ws-theme-card-head">
                    <div>
                        <h3>Section status</h3>
                        <p>Shows which parts of the theme are still using the shared global palette.</p>
                    </div>
                </div>
                <div class="ws-theme-card-body">
                    <div class="ws-theme-status-list">
                        @foreach($themeSchema as $sectionKey => $group)
                            <div class="ws-theme-status-row" data-theme-status-row="{{ $sectionKey }}">
                                <strong>{{ $group['label'] }}</strong>
                                <span data-theme-status-copy>{{ !empty($group['use_global']) ? 'Using global palette' : 'Custom values active' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
(function () {
    var manager = document.querySelector('[data-theme-manager]');
    if (!manager) {
        return;
    }

    var form = document.getElementById('website-settings-form');
    var themeDefaults = @json($themeDefaults);
    var themeGroups = @json($themeSchema);
    var themePresets = @json($themePresets);
    var themePresetPalettes = @json($themePresetPalettes);
    var themeFontStacks = @json(app(\App\Services\StorefrontThemeService::class)->fontFamilyStacks());
    var defaultPreset = themeDefaults.preset || 'lucent-tech-bd';
    var presetSelect = manager.querySelector('[data-theme-preset-select]');
    var presetName = manager.querySelector('[data-theme-preset-name]');
    var presetButtons = manager.querySelectorAll('[data-theme-apply-preset]');
    var previewRoot = manager.querySelector('[data-theme-preview-root]');
    var resetAllButton = manager.querySelector('[data-theme-reset-all]');
    var resetPresetButton = manager.querySelector('[data-theme-reset-preset]');
    var sectionResetButtons = manager.querySelectorAll('[data-theme-reset-section]');
    var fieldResetButtons = manager.querySelectorAll('[data-theme-reset-field]');
    var themeFieldSelector = '[data-theme-input], [data-theme-picker], [data-theme-toggle]';
    var themeSections = Object.keys(themeGroups);

    function fontStack(key) {
        return themeFontStacks[key] || themeFontStacks.system || 'system-ui, sans-serif';
    }

    function isTruthy(value) {
        return value === true || value === 1 || value === '1' || value === 'true' || value === 'on';
    }

    function isHexColor(value) {
        return /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/.test(String(value || '').trim());
    }

    function normalizeHex(value, fallback) {
        value = String(value || '').trim();
        if (!isHexColor(value)) {
            return fallback;
        }
        return value.toLowerCase();
    }

    function hexToRgb(color) {
        color = String(color || '').trim();
        if (!isHexColor(color)) {
            return null;
        }
        color = color.replace('#', '');
        if (color.length === 3) {
            color = color.replace(/(.)/g, '$1$1');
        } else if (color.length === 8) {
            color = color.slice(0, 6);
        }
        return {
            r: parseInt(color.slice(0, 2), 16),
            g: parseInt(color.slice(2, 4), 16),
            b: parseInt(color.slice(4, 6), 16),
        };
    }

    function rgbaFromHex(color, alpha) {
        var rgb = hexToRgb(color);
        if (!rgb) {
            return 'rgba(11,61,98,' + alpha + ')';
        }
        return 'rgba(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ',' + alpha + ')';
    }

    function blendForHover(color, direction) {
        var rgb = hexToRgb(color);
        if (!rgb) {
            return color;
        }
        var ratio = 0.12;
        var target = direction === 'light' ? 255 : 0;
        function mix(channel) {
            return Math.round(channel + ((target - channel) * ratio));
        }
        return '#' + [mix(rgb.r), mix(rgb.g), mix(rgb.b)].map(function (value) {
            return value.toString(16).padStart(2, '0');
        }).join('');
    }

    function contrastRatio(foreground, background) {
        function relativeLuminance(color) {
            var rgb = hexToRgb(color);
            if (!rgb) {
                return null;
            }
            function transform(channel) {
                channel = channel / 255;
                return channel <= 0.03928 ? channel / 12.92 : Math.pow((channel + 0.055) / 1.055, 2.4);
            }
            return 0.2126 * transform(rgb.r) + 0.7152 * transform(rgb.g) + 0.0722 * transform(rgb.b);
        }
        var fg = relativeLuminance(foreground);
        var bg = relativeLuminance(background);
        if (fg === null || bg === null) {
            return 0;
        }
        var lighter = Math.max(fg, bg);
        var darker = Math.min(fg, bg);
        return (lighter + 0.05) / (darker + 0.05);
    }

    function suggestTextColor(background) {
        return contrastRatio('#ffffff', background) >= contrastRatio('#111827', background) ? '#ffffff' : '#111827';
    }

    function getFieldMeta(key) {
        for (var sectionKey in themeGroups) {
            if (!Object.prototype.hasOwnProperty.call(themeGroups, sectionKey)) {
                continue;
            }
            var fields = themeGroups[sectionKey].fields || [];
            for (var i = 0; i < fields.length; i++) {
                if (fields[i].key === key) {
                    return fields[i];
                }
            }
        }
        return null;
    }

    function getValue(key) {
        var text = manager.querySelector('[data-theme-input="' + key + '"]');
        var picker = manager.querySelector('[data-theme-picker="' + key + '"]');
        var toggle = manager.querySelector('[data-theme-toggle="' + key + '"]');
        if (toggle) {
            return toggle.checked ? '1' : '0';
        }
        if (text) {
            return text.value;
        }
        if (picker) {
            return picker.value;
        }
        return '';
    }

    function setValue(key, value, silent) {
        var meta = getFieldMeta(key);
        var text = manager.querySelector('[data-theme-input="' + key + '"]');
        var picker = manager.querySelector('[data-theme-picker="' + key + '"]');
        var toggle = manager.querySelector('[data-theme-toggle="' + key + '"]');
        var normalized = value;

        if (meta && meta.type === 'boolean') {
            normalized = isTruthy(value);
            if (toggle) {
                toggle.checked = normalized;
                if (!silent) {
                    toggle.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            return;
        }

        if (meta && meta.type === 'color') {
            normalized = normalizeHex(value, text ? text.value : (picker ? picker.value : '#000000'));
            if (picker) {
                picker.value = normalized;
            }
            if (text) {
                text.value = normalized;
            }
            if (text && !silent) {
                text.dispatchEvent(new Event('input', { bubbles: true }));
            }
            return;
        }

        if (meta && meta.type === 'select') {
            if (text) {
                text.value = value;
                if (!silent) {
                    text.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            return;
        }

        if (text) {
            text.value = value;
            if (!silent) {
                text.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }

    function collectValues() {
        var values = {};
        for (var sectionKey in themeGroups) {
            if (!Object.prototype.hasOwnProperty.call(themeGroups, sectionKey)) {
                continue;
            }
            var fields = themeGroups[sectionKey].fields || [];
            for (var i = 0; i < fields.length; i++) {
                var field = fields[i];
                values[field.key] = getValue(field.key);
            }
        }
        return values;
    }

    function buildResolved(values) {
        var resolved = {};
        Object.keys(themeDefaults).forEach(function (key) {
            resolved[key] = themeDefaults[key];
        });
        Object.keys(values).forEach(function (key) {
            var meta = getFieldMeta(key);
            if (!meta) {
                return;
            }
            if (meta.type === 'boolean') {
                resolved[key] = isTruthy(values[key]) ? 1 : 0;
                return;
            }
            if (meta.type === 'color') {
                resolved[key] = normalizeHex(values[key], resolved[key]);
                return;
            }
            if (meta.type === 'select') {
                resolved[key] = values[key];
                return;
            }
            resolved[key] = String(values[key] || '').trim();
        });

        if (!themePresets[resolved.preset]) {
            resolved.preset = defaultPreset;
        }

        var global = {
            global_primary: resolved.global_primary,
            global_secondary: resolved.global_secondary,
            global_accent: resolved.global_accent,
            global_success: resolved.global_success,
            global_warning: resolved.global_warning,
            global_danger: resolved.global_danger,
            global_info: resolved.global_info,
            global_neutral_dark: resolved.global_neutral_dark,
            global_neutral_light: resolved.global_neutral_light,
            global_page_background: resolved.global_page_background,
            global_body_text: resolved.global_body_text,
            global_body_muted: resolved.global_body_muted,
            global_heading: resolved.global_heading,
            global_link: resolved.global_link,
            global_link_hover: resolved.global_link_hover,
            global_border: resolved.global_border,
        };

        var derived = {
            topbar: {
                topbar_background: global.global_secondary,
                topbar_text: global.global_page_background,
                topbar_link: global.global_page_background,
                topbar_link_hover: global.global_accent,
            },
            header: {
                header_background: global.global_secondary,
                header_text: global.global_page_background,
                header_link: global.global_page_background,
                header_link_hover: global.global_accent,
                header_icon: global.global_accent,
                header_icon_hover: global.global_page_background,
            },
            search: {
                search_background: global.global_page_background,
                search_text: global.global_body_text,
                search_placeholder: global.global_body_muted,
                search_border: global.global_primary,
                search_focus_border: global.global_accent,
                search_button_background: global.global_primary,
                search_button_icon: global.global_page_background,
                search_button_hover: global.global_accent,
            },
            actions: {
                actions_icon: global.global_accent,
                actions_text: global.global_page_background,
                actions_hover: global.global_accent,
                actions_active: global.global_page_background,
            },
            badges: {
                badges_background: global.global_accent,
                badges_text: global.global_page_background,
                badges_border: global.global_accent,
            },
            pc_builder: {
                pc_builder_background: global.global_accent,
                pc_builder_text: global.global_page_background,
                pc_builder_icon: global.global_page_background,
                pc_builder_border: global.global_accent,
                pc_builder_hover_background: blendForHover(global.global_accent, 'light'),
                pc_builder_hover_text: global.global_page_background,
            },
            navigation: {
                navigation_background: global.global_primary,
                navigation_text: global.global_page_background,
                navigation_hover_background: blendForHover('#0b3d62', 'light'),
                navigation_hover_text: global.global_page_background,
                navigation_active_background: global.global_accent,
                navigation_active_text: global.global_page_background,
                navigation_dropdown_background: global.global_page_background,
                navigation_dropdown_text: global.global_body_text,
                navigation_dropdown_hover: global.global_neutral_light,
                navigation_border: global.global_accent,
            },
            body: {
                body_background: global.global_page_background,
                body_text: global.global_body_text,
                body_muted: global.global_body_muted,
                body_heading: global.global_heading,
                body_link: global.global_link,
                body_link_hover: global.global_link_hover,
            },
            cards: {
                cards_background: global.global_page_background,
                cards_border: global.global_border,
                cards_title: global.global_body_text,
                cards_title_hover: global.global_accent,
                cards_price: global.global_accent,
                cards_old_price: '#8996a1',
                cards_discount_badge: global.global_accent,
                cards_stock: global.global_success,
                cards_rating: global.global_warning,
                cards_hover_border: global.global_accent,
                cards_hover_shadow: '0 10px 30px rgba(11,61,98,.1)',
            },
            buttons: {
                button_primary_background: global.global_primary,
                button_primary_text: global.global_page_background,
                button_primary_border: global.global_primary,
                button_primary_hover_background: global.global_accent,
                button_primary_hover_text: global.global_page_background,
                button_primary_disabled_background: '#a8b7c4',
                button_primary_disabled_text: global.global_page_background,
                button_secondary_background: global.global_secondary,
                button_secondary_text: global.global_page_background,
                button_secondary_border: global.global_secondary,
                button_secondary_hover_background: global.global_primary,
                button_secondary_hover_text: global.global_page_background,
                button_accent_background: global.global_accent,
                button_accent_text: global.global_page_background,
                button_accent_border: global.global_accent,
                button_accent_hover_background: blendForHover(global.global_accent, 'light'),
                button_accent_hover_text: global.global_page_background,
                button_danger_background: global.global_danger,
                button_danger_text: global.global_page_background,
                button_danger_border: global.global_danger,
                button_danger_hover_background: '#b91c1c',
                button_danger_hover_text: global.global_page_background,
            },
            forms: {
                form_input_background: global.global_page_background,
                form_input_text: global.global_body_text,
                form_placeholder: global.global_body_muted,
                form_border: '#ccd8e0',
                form_focus_border: global.global_accent,
                form_focus_ring: rgbaFromHex(global.global_accent, 0.15),
                form_label: global.global_body_text,
                form_required: '#db4b4b',
            },
            footer: {
                footer_background: global.global_secondary,
                footer_heading: global.global_page_background,
                footer_text: '#b9ccdc',
                footer_link: '#b9ccdc',
                footer_link_hover: global.global_accent,
                footer_border: 'rgba(255,255,255,.12)',
                footer_icon: global.global_accent,
                footer_bottom_background: '#061b2c',
                footer_bottom_text: '#b9ccdc',
            },
            breadcrumbs: {
                breadcrumbs_background: '#f7fbfe',
                breadcrumbs_text: global.global_body_muted,
                breadcrumbs_link: global.global_primary,
                breadcrumbs_active_text: global.global_body_text,
                breadcrumbs_separator: '#a7b4bf',
            },
        };

        Object.keys(derived).forEach(function (sectionKey) {
            var toggleKey = sectionKey + '_use_global';
            var useGlobal = true;
            if (themeGroups[sectionKey] && themeGroups[sectionKey].use_global) {
                var toggle = manager.querySelector('[data-theme-toggle="' + toggleKey + '"]');
                useGlobal = toggle ? toggle.checked : isTruthy(resolved[toggleKey]);
            }
            if (useGlobal && derived[sectionKey]) {
                Object.keys(derived[sectionKey]).forEach(function (key) {
                    resolved[key] = derived[sectionKey][key];
                });
            }
        });

        return resolved;
    }

    function applyPreviewTheme(resolved) {
        if (!previewRoot) {
            return;
        }

        var vars = {
            '--navy': resolved.global_primary,
            '--navy-dark': resolved.global_secondary,
            '--orange': resolved.global_accent,
            '--ink': resolved.global_body_text,
            '--muted': resolved.global_body_muted,
            '--line': resolved.global_border,
            '--soft': resolved.global_neutral_light,
            '--white': resolved.global_page_background,
            '--tb-bg': resolved.topbar_background,
            '--tb-text': resolved.topbar_text,
            '--tb-link': resolved.topbar_link,
            '--tb-link-hover': resolved.topbar_link_hover,
            '--theme-topbar-font-family': fontStack(resolved.topbar_font_family),
            '--theme-topbar-font-size': resolved.topbar_font_size + 'px',
            '--theme-header-font-family': fontStack(resolved.header_font_family),
            '--theme-header-font-size': resolved.header_font_size + 'px',
            '--theme-search-font-family': fontStack(resolved.search_font_family),
            '--theme-search-font-size': resolved.search_font_size + 'px',
            '--theme-actions-font-family': fontStack(resolved.actions_font_family),
            '--theme-actions-font-size': resolved.actions_font_size + 'px',
            '--theme-badges-font-family': fontStack(resolved.badges_font_family),
            '--theme-badges-font-size': resolved.badges_font_size + 'px',
            '--theme-pc-builder-font-family': fontStack(resolved.pc_builder_font_family),
            '--theme-pc-builder-font-size': resolved.pc_builder_font_size + 'px',
            '--theme-navigation-font-family': fontStack(resolved.navigation_font_family),
            '--theme-navigation-font-size': resolved.navigation_font_size + 'px',
            '--theme-body-font-family': fontStack(resolved.body_font_family),
            '--theme-body-font-size': resolved.body_font_size + 'px',
            '--theme-cards-font-family': fontStack(resolved.cards_font_family),
            '--theme-cards-font-size': resolved.cards_font_size + 'px',
            '--theme-buttons-font-family': fontStack(resolved.buttons_font_family),
            '--theme-buttons-font-size': resolved.buttons_font_size + 'px',
            '--theme-forms-font-family': fontStack(resolved.forms_font_family),
            '--theme-forms-font-size': resolved.forms_font_size + 'px',
            '--theme-footer-font-family': fontStack(resolved.footer_font_family),
            '--theme-footer-font-size': resolved.footer_font_size + 'px',
            '--theme-breadcrumbs-font-family': fontStack(resolved.breadcrumbs_font_family),
            '--theme-breadcrumbs-font-size': resolved.breadcrumbs_font_size + 'px',
            '--theme-page-bg': resolved.body_background,
            '--theme-body-text': resolved.body_text,
            '--theme-muted': resolved.body_muted,
            '--theme-heading': resolved.body_heading,
            '--theme-link': resolved.body_link,
            '--theme-link-hover': resolved.body_link_hover,
            '--theme-header-bg': resolved.header_background,
            '--theme-header-text': resolved.header_text,
            '--theme-header-link': resolved.header_link,
            '--theme-header-link-hover': resolved.header_link_hover,
            '--theme-header-icon': resolved.header_icon,
            '--theme-header-icon-hover': resolved.header_icon_hover,
            '--theme-search-bg': resolved.search_background,
            '--theme-search-text': resolved.search_text,
            '--theme-search-placeholder': resolved.search_placeholder,
            '--theme-search-border': resolved.search_border,
            '--theme-search-focus-border': resolved.search_focus_border,
            '--theme-search-focus-ring': resolved.form_focus_ring,
            '--theme-search-button-bg': resolved.search_button_background,
            '--theme-search-button-icon': resolved.search_button_icon,
            '--theme-search-button-hover': resolved.search_button_hover,
            '--theme-actions-icon': resolved.actions_icon,
            '--theme-actions-text': resolved.actions_text,
            '--theme-actions-hover': resolved.actions_hover,
            '--theme-actions-active': resolved.actions_active,
            '--theme-badge-bg': resolved.badges_background,
            '--theme-badge-text': resolved.badges_text,
            '--theme-badge-border': resolved.badges_border,
            '--theme-pc-builder-bg': resolved.pc_builder_background,
            '--theme-pc-builder-text': resolved.pc_builder_text,
            '--theme-pc-builder-icon': resolved.pc_builder_icon,
            '--theme-pc-builder-border': resolved.pc_builder_border,
            '--theme-pc-builder-hover-bg': resolved.pc_builder_hover_background,
            '--theme-pc-builder-hover-text': resolved.pc_builder_hover_text,
            '--theme-nav-bg': resolved.navigation_background,
            '--theme-nav-text': resolved.navigation_text,
            '--theme-nav-hover-bg': resolved.navigation_hover_background,
            '--theme-nav-hover-text': resolved.navigation_hover_text,
            '--theme-nav-active-bg': resolved.navigation_active_background,
            '--theme-nav-active-text': resolved.navigation_active_text,
            '--theme-dropdown-bg': resolved.navigation_dropdown_background,
            '--theme-dropdown-text': resolved.navigation_dropdown_text,
            '--theme-dropdown-hover': resolved.navigation_dropdown_hover,
            '--theme-nav-border': resolved.navigation_border,
            '--theme-card-bg': resolved.cards_background,
            '--theme-card-border': resolved.cards_border,
            '--theme-card-title': resolved.cards_title,
            '--theme-card-title-hover': resolved.cards_title_hover,
            '--theme-card-price': resolved.cards_price,
            '--theme-card-old-price': resolved.cards_old_price,
            '--theme-card-discount-badge': resolved.cards_discount_badge,
            '--theme-card-stock': resolved.cards_stock,
            '--theme-card-rating': resolved.cards_rating,
            '--theme-card-hover-border': resolved.cards_hover_border,
            '--theme-card-hover-shadow': resolved.cards_hover_shadow,
            '--theme-button-primary-bg': resolved.button_primary_background,
            '--theme-button-primary-text': resolved.button_primary_text,
            '--theme-button-primary-border': resolved.button_primary_border,
            '--theme-button-primary-hover-bg': resolved.button_primary_hover_background,
            '--theme-button-primary-hover-text': resolved.button_primary_hover_text,
            '--theme-button-primary-disabled-bg': resolved.button_primary_disabled_background,
            '--theme-button-primary-disabled-text': resolved.button_primary_disabled_text,
            '--theme-button-secondary-bg': resolved.button_secondary_background,
            '--theme-button-secondary-text': resolved.button_secondary_text,
            '--theme-button-secondary-border': resolved.button_secondary_border,
            '--theme-button-secondary-hover-bg': resolved.button_secondary_hover_background,
            '--theme-button-secondary-hover-text': resolved.button_secondary_hover_text,
            '--theme-button-accent-bg': resolved.button_accent_background,
            '--theme-button-accent-text': resolved.button_accent_text,
            '--theme-button-accent-border': resolved.button_accent_border,
            '--theme-button-accent-hover-bg': resolved.button_accent_hover_background,
            '--theme-button-accent-hover-text': resolved.button_accent_hover_text,
            '--theme-button-danger-bg': resolved.button_danger_background,
            '--theme-button-danger-text': resolved.button_danger_text,
            '--theme-button-danger-border': resolved.button_danger_border,
            '--theme-button-danger-hover-bg': resolved.button_danger_hover_background,
            '--theme-button-danger-hover-text': resolved.button_danger_hover_text,
            '--theme-form-input-bg': resolved.form_input_background,
            '--theme-form-input-text': resolved.form_input_text,
            '--theme-form-placeholder': resolved.form_placeholder,
            '--theme-form-border': resolved.form_border,
            '--theme-form-focus-border': resolved.form_focus_border,
            '--theme-form-focus-ring': resolved.form_focus_ring,
            '--theme-form-label': resolved.form_label,
            '--theme-form-required': resolved.form_required,
            '--theme-footer-bg': resolved.footer_background,
            '--theme-footer-heading': resolved.footer_heading,
            '--theme-footer-text': resolved.footer_text,
            '--theme-footer-link': resolved.footer_link,
            '--theme-footer-link-hover': resolved.footer_link_hover,
            '--theme-footer-border': resolved.footer_border,
            '--theme-footer-icon': resolved.footer_icon,
            '--theme-footer-bottom-bg': resolved.footer_bottom_background,
            '--theme-footer-bottom-text': resolved.footer_bottom_text,
            '--theme-breadcrumb-bg': resolved.breadcrumbs_background,
            '--theme-breadcrumb-text': resolved.breadcrumbs_text,
            '--theme-breadcrumb-link': resolved.breadcrumbs_link,
            '--theme-breadcrumb-active': resolved.breadcrumbs_active_text,
            '--theme-breadcrumb-separator': resolved.breadcrumbs_separator,
        };

        Object.keys(vars).forEach(function (name) {
            previewRoot.style.setProperty(name, vars[name]);
        });
    }

    function updatePresetSummary(resolved) {
        var preset = resolved.preset || defaultPreset;
        var label = themePresets[preset] || preset;
        if (presetName) {
            presetName.textContent = label;
        }
        if (presetSelect) {
            presetSelect.value = preset;
        }
        presetButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.getAttribute('data-theme-apply-preset') === preset);
        });
    }

    function updateSectionStates() {
        themeSections.forEach(function (sectionKey) {
            var group = themeGroups[sectionKey];
            var card = manager.querySelector('[data-theme-section-card="' + sectionKey + '"]');
            var toggle = manager.querySelector('[data-theme-toggle="' + sectionKey + '_use_global"]');
            var status = manager.querySelector('[data-theme-status-row="' + sectionKey + '"]');
            var usingGlobal = !!(group && group.use_global && toggle && toggle.checked);

            if (card) {
                card.classList.toggle('is-using-global', usingGlobal);
            }

            if (status) {
                status.classList.toggle('is-active', usingGlobal);
                var copy = status.querySelector('[data-theme-status-copy]');
                if (copy) {
                    copy.textContent = usingGlobal ? 'Using global palette' : 'Custom values active';
                }
            }

            var fieldRows = manager.querySelectorAll('[data-theme-section-row="' + sectionKey + '"]');
            fieldRows.forEach(function (row) {
                var key = row.getAttribute('data-theme-field-row');
                var isToggleRow = key === sectionKey + '_use_global';
                row.classList.toggle('is-muted', usingGlobal && !isToggleRow);
                row.querySelectorAll('input, select, textarea').forEach(function (control) {
                    if (control.getAttribute('data-theme-toggle') === sectionKey + '_use_global' || control.type === 'hidden') {
                        return;
                    }
                    if (control.getAttribute('data-theme-reset-field') !== null) {
                        return;
                    }
                    control.disabled = usingGlobal && !isToggleRow;
                });
            });
        });
    }

    function updateColorPairs() {
        manager.querySelectorAll('[data-theme-picker]').forEach(function (picker) {
            var key = picker.getAttribute('data-theme-picker');
            var text = manager.querySelector('[data-theme-input="' + key + '"]');
            if (!text) {
                return;
            }
            if (isHexColor(text.value)) {
                picker.value = normalizeHex(text.value, picker.value);
            }
        });
    }

    function updateContrast(resolved) {
        var rows = {
            'top-bar': {
                label: 'Top Bar',
                text: resolved.topbar_text,
                background: resolved.topbar_background
            },
            header: {
                label: 'Header',
                text: resolved.header_text,
                background: resolved.header_background
            },
            search: {
                label: 'Search',
                text: resolved.search_text,
                background: resolved.search_background
            },
            buttons: {
                label: 'Buttons',
                text: resolved.button_primary_text,
                background: resolved.button_primary_background
            },
            footer: {
                label: 'Footer',
                text: resolved.footer_text,
                background: resolved.footer_background
            }
        };

        Object.keys(rows).forEach(function (key) {
            var row = manager.querySelector('[data-theme-contrast-row="' + key + '"]');
            if (!row) {
                return;
            }
            var item = rows[key];
            var ratio = contrastRatio(item.text, item.background);
            var level = ratio >= 7 ? 'Good' : (ratio >= 4.5 ? 'Acceptable' : 'Poor');
            row.classList.remove('is-good', 'is-acceptable', 'is-poor');
            row.classList.add(level === 'Good' ? 'is-good' : (level === 'Acceptable' ? 'is-acceptable' : 'is-poor'));
            var levelNode = row.querySelector('[data-theme-contrast-level]');
            var ratioNode = row.querySelector('[data-theme-contrast-ratio]');
            var suggestionNode = row.querySelector('[data-theme-contrast-suggestion]');
            if (levelNode) {
                levelNode.textContent = level;
            }
            if (ratioNode) {
                ratioNode.textContent = ratio.toFixed(1) + ':1';
            }
            if (suggestionNode) {
                suggestionNode.textContent = suggestTextColor(item.background);
            }
        });
    }

    function updatePreviewSummary(resolved) {
        var summary = manager.querySelector('.ws-theme-preview-note');
        if (!summary) {
            return;
        }
        summary.textContent = 'The preview is currently using ' + (themePresets[resolved.preset] || resolved.preset || defaultPreset) + ' with live colors from the form.';
    }

    function updateThemeUI() {
        updateColorPairs();
        var values = collectValues();
        var resolved = buildResolved(values);
        updatePresetSummary(resolved);
        applyPreviewTheme(resolved);
        updateContrast(resolved);
        updateSectionStates();
        updatePreviewSummary(resolved);
    }

    function resetField(key, silent) {
        if (key === 'preset') {
            setValue(key, themeDefaults.preset || defaultPreset, silent);
            return;
        }

        var meta = getFieldMeta(key);
        if (!meta) {
            return;
        }
        var fieldValue = themeDefaults[key];
        if (typeof fieldValue === 'undefined' || fieldValue === null) {
            fieldValue = '';
        }
        setValue(key, fieldValue, silent);
    }

    function resetSection(sectionKey, quiet) {
        var group = themeGroups[sectionKey];
        if (!group) {
            return;
        }
        (group.fields || []).forEach(function (field) {
            resetField(field.key, true);
        });
        if (!quiet) {
            updateThemeUI();
        }
    }

    function resetAllTheme() {
        themeSections.forEach(function (sectionKey) {
            resetSection(sectionKey, true);
        });
        resetField('preset', true);
        updateThemeUI();
    }

    function applyPreset(presetKey) {
        var palette = themePresetPalettes[presetKey];
        if (!palette) {
            return;
        }
        Object.keys(palette).forEach(function (key) {
            setValue(key, palette[key], true);
        });
        setValue('preset', presetKey, true);
        updateThemeUI();
    }

    manager.addEventListener('input', function (event) {
        if (!event.target.matches(themeFieldSelector)) {
            return;
        }
        if (event.target.matches('[data-theme-input], [data-theme-picker], [data-theme-toggle]')) {
            updateThemeUI();
        }
    });

    manager.addEventListener('change', function (event) {
        if (!event.target.matches(themeFieldSelector)) {
            return;
        }
        if (event.target.matches('[data-theme-input], [data-theme-picker], [data-theme-toggle]')) {
            updateThemeUI();
        }
    });

    manager.querySelectorAll('[data-theme-picker]').forEach(function (picker) {
        picker.addEventListener('input', function () {
            var key = this.getAttribute('data-theme-picker');
            var text = manager.querySelector('[data-theme-input="' + key + '"]');
            if (text) {
                text.value = this.value;
            }
            updateThemeUI();
        });
    });

    manager.querySelectorAll('[data-theme-input]').forEach(function (input) {
        input.addEventListener('input', function () {
            var key = this.getAttribute('data-theme-input');
            var picker = manager.querySelector('[data-theme-picker="' + key + '"]');
            if (picker && isHexColor(this.value)) {
                picker.value = normalizeHex(this.value, picker.value);
            }
            updateThemeUI();
        });
        input.addEventListener('change', function () {
            var key = this.getAttribute('data-theme-input');
            var picker = manager.querySelector('[data-theme-picker="' + key + '"]');
            if (picker && isHexColor(this.value)) {
                picker.value = normalizeHex(this.value, picker.value);
            }
            updateThemeUI();
        });
    });

    manager.querySelectorAll('[data-theme-toggle]').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            updateThemeUI();
        });
    });

    presetSelect && presetSelect.addEventListener('change', function () {
        applyPreset(this.value);
    });

    presetButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            applyPreset(this.getAttribute('data-theme-apply-preset'));
        });
    });

    fieldResetButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var key = this.getAttribute('data-theme-reset-field');
            var field = manager.querySelector('[data-theme-field-row="' + key + '"]');
            var defaultValue = field ? field.getAttribute('data-theme-default') : '';
            setValue(key, defaultValue, true);
            updateThemeUI();
        });
    });

    sectionResetButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            resetSection(this.getAttribute('data-theme-reset-section'));
        });
    });

    if (resetPresetButton) {
        resetPresetButton.addEventListener('click', function () {
            setValue('preset', defaultPreset);
            updateThemeUI();
        });
    }

    if (resetAllButton) {
        resetAllButton.addEventListener('click', function () {
            if (!window.confirm('Reset the storefront theme back to its default palette?')) {
                return;
            }
            resetAllTheme();
        });
    }

    window.storefrontThemeManager = {
        refresh: updateThemeUI,
        applyPreset: applyPreset,
        resetSection: resetSection,
        resetAll: resetAllTheme
    };

    if (form) {
        form.addEventListener('reset', function () {
            window.setTimeout(updateThemeUI, 0);
        });
    }

    updateThemeUI();
})();
</script>
