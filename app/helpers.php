<?php

use Illuminate\Support\Str;

if (! function_exists('str_random')) {
    function str_random(int $length = 16): string
    {
        return Str::random($length);
    }
}

if (! function_exists('str_slug')) {
    function str_slug(string $title, string $separator = '-'): string
    {
        return Str::slug($title, $separator);
    }
}

if (! function_exists('catalog_import_source_address')) {
    function catalog_import_source_address(?string $sourceAddress = null): string
    {
        $sourceAddress = trim((string) $sourceAddress);
        if ($sourceAddress === '') {
            $sourceAddress = 'https://www.startech.com.bd/';
        }

        if (! preg_match('#^https?://#i', $sourceAddress)) {
            $sourceAddress = 'https://'.$sourceAddress;
        }

        $sourceAddress = preg_replace('~[?#].*$~', '', $sourceAddress) ?: $sourceAddress;
        $sourceAddress = rtrim($sourceAddress, '/').'/';
        $parts = parse_url($sourceAddress);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return 'https://www.startech.com.bd/';
        }

        $normalized = $parts['scheme'].'://'.$parts['host'];
        if (isset($parts['port'])) {
            $normalized .= ':'.$parts['port'];
        }

        $path = trim((string) ($parts['path'] ?? ''), '/');

        return $normalized.'/'.($path !== '' ? $path.'/' : '');
    }
}

if (! function_exists('catalog_import_source_label')) {
    function catalog_import_source_label(?string $sourceAddress = null): string
    {
        $normalizedAddress = catalog_import_source_address($sourceAddress);
        $parts = parse_url($normalizedAddress);
        if (! is_array($parts) || empty($parts['host'])) {
            return 'Catalog';
        }

        $host = strtolower((string) $parts['host']);
        if (str_contains($host, 'startech')) {
            return 'Star Tech';
        }

        $host = preg_replace('/^(?:www\\d*|m)\\./', '', $host) ?: $host;

        $matchedSuffix = false;

        foreach (['com.bd', 'net.bd', 'org.bd', 'edu.bd', 'gov.bd', 'co.bd', 'info.bd', 'com.au', 'net.au', 'org.au', 'co.uk', 'org.uk', 'ac.uk', 'gov.uk', 'co.nz', 'com.sg', 'com.my', 'com.pk', 'com.ph', 'co.in', 'com.in'] as $suffix) {
            if (Str::endsWith($host, '.'.$suffix)) {
                $host = Str::beforeLast($host, '.'.$suffix);
                $matchedSuffix = true;
                break;
            }
        }

        $segments = array_values(array_filter(explode('.', $host), static function ($segment) {
            return $segment !== '';
        }));

        if ($matchedSuffix) {
            $labelSource = $segments !== [] ? (string) end($segments) : 'Catalog';
        } elseif (count($segments) > 1) {
            $labelSource = $segments[count($segments) - 2];
        } else {
            $labelSource = $segments[0] ?? 'Catalog';
        }

        $labelSource = preg_replace('/[-_]+/', ' ', $labelSource) ?: $labelSource;
        $labelSource = preg_replace('/\\s+/', ' ', trim($labelSource)) ?: 'Catalog';

        $label = Str::headline($labelSource);
        $label = preg_replace('/\\bStartech\\b/i', 'Star Tech', $label) ?: $label;

        return trim($label) !== '' ? trim($label) : 'Catalog';
    }
}

if (! function_exists('normalize_business_code')) {
    function normalize_business_code(?string $value, int $maxLength = 30): ?string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^A-Z0-9]+/', '', $value) ?: '';
        if ($value === '') {
            return null;
        }

        return substr($value, 0, max(1, $maxLength));
    }
}

if (! function_exists('normalize_product_code')) {
    function normalize_product_code(?string $value, int $maxLength = 100): ?string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', '', $value) ?: '';
        $value = preg_replace('/[^A-Z0-9\-._\/]+/', '', $value) ?: '';
        if ($value === '') {
            return null;
        }

        return substr($value, 0, max(1, $maxLength));
    }
}

if (! function_exists('suggest_business_code')) {
    function suggest_business_code(string $name, int $maxLength = 8): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $slug = Str::slug($name, '');
        if ($slug === '') {
            $slug = preg_replace('/[^A-Za-z0-9]/', '', $name) ?: '';
        }

        $slug = strtoupper($slug);
        if ($slug === '') {
            return '';
        }

        return substr($slug, 0, max(1, $maxLength));
    }
}

if (! function_exists('sanitize_product_description_html')) {
    /**
     * Keep the useful formatting produced by Word, Google Docs, and product
     * pages while removing executable markup and unsafe attributes.
     */
    function sanitize_product_description_html(?string $description): string
    {
        $description = trim((string) $description);
        if ($description === '') {
            return '';
        }

        if (! class_exists('DOMDocument')) {
            return trim(strip_tags($description, '<p><br><strong><b><em><i><u><ul><ol><li>'));
        }

        $allowedTags = [
            'a' => ['href', 'target', 'rel', 'title', 'class', 'style'],
            'b' => ['class', 'style'],
            'blockquote' => ['class', 'style'],
            'br' => [],
            'caption' => ['class', 'style'],
            'col' => ['class', 'style', 'span', 'width'],
            'colgroup' => ['class', 'style', 'span', 'width'],
            'div' => ['class', 'style', 'align', 'dir', 'lang'],
            'em' => ['class', 'style'],
            'font' => ['class', 'style', 'color', 'face', 'size'],
            'h1' => ['class', 'style', 'align', 'dir', 'lang'],
            'h2' => ['class', 'style', 'align', 'dir', 'lang'],
            'h3' => ['class', 'style', 'align', 'dir', 'lang'],
            'h4' => ['class', 'style', 'align', 'dir', 'lang'],
            'h5' => ['class', 'style', 'align', 'dir', 'lang'],
            'h6' => ['class', 'style', 'align', 'dir', 'lang'],
            'hr' => ['class', 'style'],
            'i' => ['class', 'style'],
            'img' => ['src', 'alt', 'title', 'width', 'height', 'class', 'style'],
            'li' => ['class', 'style', 'value'],
            'ol' => ['class', 'style', 'start', 'type'],
            'p' => ['class', 'style', 'align', 'dir', 'lang'],
            'span' => ['class', 'style', 'dir', 'lang'],
            'strong' => ['class', 'style'],
            'table' => ['class', 'style', 'border', 'cellpadding', 'cellspacing', 'width', 'align', 'bgcolor', 'dir'],
            'tbody' => ['class', 'style'],
            'td' => ['class', 'style', 'colspan', 'rowspan', 'width', 'height', 'align', 'valign', 'bgcolor'],
            'tfoot' => ['class', 'style'],
            'th' => ['class', 'style', 'colspan', 'rowspan', 'width', 'height', 'align', 'valign', 'bgcolor'],
            'thead' => ['class', 'style'],
            'tr' => ['class', 'style', 'height', 'align', 'valign', 'bgcolor'],
            'u' => ['class', 'style'],
            'ul' => ['class', 'style', 'type'],
        ];
        $removeEntirely = ['base', 'button', 'canvas', 'embed', 'form', 'iframe', 'input', 'link', 'meta', 'object', 'script', 'select', 'style', 'svg', 'textarea', 'video', 'audio'];
        $globalAttributes = ['class', 'dir', 'lang', 'title'];
        $styleProperties = [
            'background', 'background-color', 'border', 'border-bottom', 'border-bottom-color',
            'border-bottom-style', 'border-bottom-width', 'border-collapse', 'border-color',
            'border-left', 'border-left-color', 'border-left-style', 'border-left-width',
            'border-right', 'border-right-color', 'border-right-style', 'border-right-width',
            'border-spacing', 'border-style', 'border-top', 'border-top-color', 'border-top-style',
            'border-top-width', 'border-width', 'color', 'font', 'font-family', 'font-size',
            'font-style', 'font-weight', 'height', 'letter-spacing', 'line-height', 'list-style',
            'margin', 'margin-bottom', 'margin-left', 'margin-right', 'margin-top', 'max-height',
            'max-width', 'min-height', 'min-width', 'padding', 'padding-bottom', 'padding-left',
            'padding-right', 'padding-top', 'table-layout', 'text-align', 'text-decoration',
            'text-indent', 'text-transform', 'vertical-align', 'white-space', 'width', 'word-break',
            'word-wrap', 'overflow-wrap',
        ];

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="product-description-root">'.$description.'</div></body></html>';
        $loaded = $dom->loadHTML($wrapped, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        if (! $loaded) {
            return trim(strip_tags($description, '<p><br><strong><b><em><i><u><ul><ol><li>'));
        }

        $root = null;
        foreach ($dom->getElementsByTagName('div') as $candidate) {
            if ($candidate->getAttribute('id') === 'product-description-root') {
                $root = $candidate;
                break;
            }
        }
        if (! $root) {
            return trim(strip_tags($description, '<p><br><strong><b><em><i><u><ul><ol><li>'));
        }

        $elements = [];
        foreach ($root->getElementsByTagName('*') as $element) {
            $elements[] = $element;
        }

        for ($index = count($elements) - 1; $index >= 0; $index--) {
            $element = $elements[$index];
            $tag = strtolower($element->tagName);
            $parent = $element->parentNode;
            if (! $parent) {
                continue;
            }

            if (in_array($tag, $removeEntirely, true)) {
                $parent->removeChild($element);
                continue;
            }

            if (! array_key_exists($tag, $allowedTags)) {
                while ($element->firstChild) {
                    $parent->insertBefore($element->firstChild, $element);
                }
                $parent->removeChild($element);
                continue;
            }

            $allowedAttributes = array_unique(array_merge($globalAttributes, $allowedTags[$tag]));
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (str_starts_with($name, 'on') || ! in_array($name, $allowedAttributes, true)) {
                    $element->removeAttributeNode($attribute);
                    continue;
                }

                if ($name === 'class') {
                    $safeClasses = preg_replace('/[^a-zA-Z0-9_ -]/', '', $value) ?: '';
                    $safeClasses = trim(preg_replace('/\s+/', ' ', $safeClasses) ?: '');
                    if ($safeClasses === '') {
                        $element->removeAttribute('class');
                    } else {
                        $element->setAttribute('class', $safeClasses);
                    }
                    continue;
                }

                if ($name === 'style') {
                    $safeStyle = product_description_safe_style($value, $styleProperties);
                    if ($safeStyle === '') {
                        $element->removeAttribute('style');
                    } else {
                        $element->setAttribute('style', $safeStyle);
                    }
                    continue;
                }

                if (in_array($name, ['href', 'src'], true)) {
                    $url = trim(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if (! preg_match('~^(?:https?://|mailto:|tel:|/|\./|\.\./|#)~i', $url)) {
                        $element->removeAttribute($name);
                    } else {
                        $element->setAttribute($name, $url);
                    }
                    continue;
                }

                if (in_array($name, ['width', 'height'], true) && ! preg_match('/^\d+(?:\.\d+)?(?:px|pt|pc|cm|mm|in|em|rem|%)?$/i', $value)) {
                    $element->removeAttribute($name);
                    continue;
                }

                if (in_array($name, ['colspan', 'rowspan', 'span', 'start', 'value'], true) && ! preg_match('/^\d+$/', $value)) {
                    $element->removeAttribute($name);
                    continue;
                }

                if (in_array($name, ['bgcolor', 'color'], true) && ! preg_match('/^(?:#[0-9a-f]{3,8}|[a-z]+)$/i', $value)) {
                    $element->removeAttribute($name);
                }
            }

            if ($tag === 'a' && $element->hasAttribute('target') && $element->getAttribute('target') !== '_blank') {
                $element->removeAttribute('target');
            }
            if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }

        $html = '';
        foreach ($root->childNodes as $child) {
            $html .= $dom->saveHTML($child);
        }

        return trim($html);
    }
}

if (! function_exists('product_description_safe_style')) {
    function product_description_safe_style(string $style, array $allowedProperties): string
    {
        $clean = [];
        $style = preg_replace('/\/\*.*?\*\//s', '', $style) ?: '';
        foreach (preg_split('/\s*;\s*/', $style) ?: [] as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }
            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim(preg_replace('/\s*!important\b/i', '', $value) ?: '');
            if (! in_array($property, $allowedProperties, true) || $value === '' || strlen($value) > 300) {
                continue;
            }
            if (preg_match('/(?:url\s*\(|expression\s*\(|javascript\s*:|vbscript\s*:|data\s*:|behavior\s*:|@import|[<>])/i', $value)) {
                continue;
            }
            if (! preg_match('/^[#%(),.\/0-9a-zA-Z\s\-_*\'"+]+$/', $value)) {
                continue;
            }
            $clean[] = $property.':'.$value;
        }

        return implode(';', $clean);
    }
}

if (! function_exists('product_description_html')) {
    function product_description_html(?string $description): string
    {
        $description = trim((string) $description);
        if ($description === '') {
            return e('Contact us for full product information.');
        }

        $html = sanitize_product_description_html($description);
        if ($html === '') {
            return nl2br(e($description));
        }

        return $html;
    }
}
