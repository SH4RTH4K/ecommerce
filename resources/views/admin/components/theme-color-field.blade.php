@php
    $fieldKey = $field['key'];
    $fieldType = $field['type'] ?? 'text';
    $fieldId = 'theme-'.$fieldKey;
    $fieldValue = old('storefront_theme.'.$fieldKey, data_get($storefrontTheme ?? [], $fieldKey, $field['default'] ?? ''));
    $fieldLabel = $field['label'] ?? ucwords(str_replace('_', ' ', $fieldKey));
    $fieldHelp = $field['help'] ?? '';
    $sectionKey = $sectionKey ?? null;
    $fieldError = $errors->first('storefront_theme.'.$fieldKey);
@endphp
<div class="ws-theme-field {{ $fieldError ? 'has-error' : '' }}" data-theme-field-row="{{ $fieldKey }}" data-theme-field-type="{{ $fieldType }}" data-theme-default="{{ $field['default'] ?? '' }}" @if($sectionKey) data-theme-section-row="{{ $sectionKey }}" @endif>
    <div class="ws-theme-field-head">
        <label for="{{ $fieldId }}{{ $fieldType === 'color' ? '-picker' : '' }}">{{ $fieldLabel }}</label>
        @if($fieldType !== 'boolean')
            <button type="button" class="btn btn-mini" data-theme-reset-field="{{ $fieldKey }}" data-theme-default="{{ $field['default'] ?? '' }}">Use default</button>
        @endif
    </div>

    @if($fieldType === 'color')
        <div class="ws-theme-color-inputs">
            <input id="{{ $fieldId }}-picker" type="color" value="{{ $fieldValue }}" data-theme-picker="{{ $fieldKey }}">
            <input id="{{ $fieldId }}-text" type="text" name="storefront_theme[{{ $fieldKey }}]" value="{{ $fieldValue }}" maxlength="9" autocomplete="off" placeholder="#rrggbb" data-theme-input="{{ $fieldKey }}">
        </div>
        <div class="ws-theme-color-meta"><span data-theme-rgb="{{ $fieldKey }}"></span><span data-theme-contrast="{{ $fieldKey }}"></span></div>
        @if($fieldError)<small class="ws-upload-error" role="alert">{{ $fieldError }}</small>@endif
    @elseif($fieldType === 'boolean')
        <input type="hidden" name="storefront_theme[{{ $fieldKey }}]" value="0">
        <label class="ws-resize-toggle" for="{{ $fieldId }}">
            <input id="{{ $fieldId }}" type="checkbox" name="storefront_theme[{{ $fieldKey }}]" value="1" data-theme-toggle="{{ $fieldKey }}" @if($sectionKey && substr($fieldKey, -11) === '_use_global') data-theme-use-global="{{ $sectionKey }}" @endif {{ in_array($fieldValue, [true, 1, '1', 'true', 'on'], true) ? 'checked' : '' }}>
            <span>
                <strong>{{ $fieldLabel }}</strong>
                @if($fieldHelp)<small>{{ $fieldHelp }}</small>@endif
            </span>
        </label>
        @if($fieldError)<small class="ws-upload-error" role="alert">{{ $fieldError }}</small>@endif
    @elseif($fieldType === 'select')
        <select id="{{ $fieldId }}" name="storefront_theme[{{ $fieldKey }}]" data-theme-input="{{ $fieldKey }}">
            @foreach(($field['options'] ?? []) as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" {{ (string)$fieldValue === (string)$optionValue ? 'selected' : '' }}>{{ $optionLabel }}</option>
            @endforeach
        </select>
        @if($fieldHelp)<small class="ws-help">{{ $fieldHelp }}</small>@endif
        @if($fieldError)<small class="ws-upload-error" role="alert">{{ $fieldError }}</small>@endif
    @else
        <input id="{{ $fieldId }}" type="text" name="storefront_theme[{{ $fieldKey }}]" value="{{ $fieldValue }}" maxlength="255" autocomplete="off" data-theme-input="{{ $fieldKey }}">
        @if($fieldHelp)<small class="ws-help">{{ $fieldHelp }}</small>@endif
        @if($fieldError)<small class="ws-upload-error" role="alert">{{ $fieldError }}</small>@endif
    @endif
</div>
