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

if (! function_exists('product_description_html')) {
    function product_description_html(?string $description): string
    {
        $description = trim((string) $description);
        if ($description === '') {
            return e('Contact us for full product information.');
        }

        $allowedTags = '<h1><h2><h3><h4><h5><h6><p><br><strong><b><em><i><u><ul><ol><li><a><span><div><blockquote>'; 
        $html = strip_tags($description, $allowedTags);
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?: '';
        $html = preg_replace('/\s+style\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?: '';
        $html = preg_replace_callback('/\s+(href|src)\s*=\s*(["\'])(.*?)\2/is', function (array $match): string {
            $url = trim(html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (preg_match('/^(?:javascript|data|vbscript):/i', $url)) {
                return '';
            }

            return ' '.$match[1].'='.$match[2].e($url).$match[2];
        }, $html) ?: '';

        return strip_tags($html) === $html ? nl2br(e($description)) : $html;
    }
}
