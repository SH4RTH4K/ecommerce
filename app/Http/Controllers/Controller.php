<?php

namespace App\Http\Controllers;

use App\Services\ProductCodeGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function adminPermissions(): array
    {
        return array_values(array_filter((array) session('admin_permissions', []), static function ($permission) {
            return is_string($permission) && trim($permission) !== '';
        }));
    }

    protected function adminHasPermission(string $permission): bool
    {
        return in_array($permission, $this->adminPermissions(), true);
    }

    protected function adminCanAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->adminHasPermission((string) $permission)) {
                return true;
            }
        }

        return false;
    }

    protected function requireAdminPermission(string $permission): void
    {
        abort_unless($this->adminHasPermission($permission), 403, 'You do not have permission to perform this action.');
    }

    protected function auditAdminAction(string $action, array $details = [], ?string $method = null, ?string $path = null): void
    {
        if (! Schema::hasTable('admin_activity_logs')) {
            return;
        }

        DB::table('admin_activity_logs')->insert([
            'admin_id' => session('admin_id'),
            'admin_name' => session('admin_name'),
            'action' => $action,
            'method' => $method ?: request()->method(),
            'path' => $path ?: request()->path(),
            'ip_hash' => hash_hmac('sha256', (string) request()->ip(), config('app.key')),
            'details' => json_encode($details),
            'created_at' => now(),
        ]);
    }

    protected function nextUniqueBusinessCode(
        string $table,
        string $column,
        string $seed,
        int $maxLength = 30,
        string $fallbackPrefix = 'X',
        ?int $ignoreId = null,
        string $keyColumn = 'id',
        array $scope = []
    ): string {
        $codeType = $this->businessCodeTypeForTable($table, $column);
        if ($codeType !== null) {
            try {
                $context = array_merge($scope, [
                    'code_type' => $codeType,
                    'name' => $seed,
                    'entity_name' => $seed,
                    'ignore_id' => $ignoreId,
                    'table' => $table,
                    'column' => $column,
                    'key_column' => $keyColumn,
                ]);
                $allocation = app(ProductCodeGenerator::class)->allocate($context);
                $generated = trim((string) ($allocation['code'] ?? $allocation['product_code'] ?? ''));
                if ($generated !== '') {
                    return $generated;
                }
            } catch (\Throwable $exception) {
                // Fall back to the legacy generator when the configured engine
                // cannot yet produce the requested code.
            }
        }

        $base = normalize_business_code($seed, $maxLength) ?: normalize_business_code($fallbackPrefix, $maxLength) ?: $fallbackPrefix;
        $candidate = $base;
        $counter = 2;

        while ($this->codeExists($table, $column, $candidate, $ignoreId, $keyColumn, $scope)) {
            $suffix = (string) $counter;
            $prefixLength = max(1, $maxLength - strlen($suffix));
            $prefix = substr($base, 0, $prefixLength);
            $candidate = normalize_business_code($prefix.$suffix, $maxLength) ?: $fallbackPrefix.$suffix;
            $counter++;
        }

        return $candidate;
    }

    protected function nextUniqueSlug(
        string $table,
        string $column,
        string $seed,
        ?int $ignoreId = null,
        string $keyColumn = 'id',
        array $scope = []
    ): string {
        $base = Str::slug(trim($seed));
        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $counter = 2;

        while ($this->codeExists($table, $column, $candidate, $ignoreId, $keyColumn, $scope)) {
            $candidate = Str::slug($base.'-'.$counter);
            if ($candidate === '') {
                $candidate = 'item-'.$counter;
            }
            $counter++;
        }

        return $candidate;
    }

    private function codeExists(
        string $table,
        string $column,
        string $value,
        ?int $ignoreId = null,
        string $keyColumn = 'id',
        array $scope = []
    ): bool {
        $query = DB::table($table)->where($column, $value);

        if ($ignoreId !== null) {
            $query->where($keyColumn, '<>', $ignoreId);
        }

        foreach ($scope as $scopeColumn => $scopeValue) {
            if ($scopeValue === null) {
                $query->whereNull($scopeColumn);
            } else {
                $query->where($scopeColumn, $scopeValue);
            }
        }

        return $query->exists();
    }

    private function businessCodeTypeForTable(string $table, string $column): ?string
    {
        $map = [
            'companies' => ['company_code' => 'company'],
            'category' => ['category_code' => 'category'],
            'sub_category' => ['subcategory_code' => 'subcategory'],
            'manufacturer' => ['brand_code' => 'brand'],
            'product_series' => ['series_code' => 'series'],
            'product' => ['product_code' => 'product'],
        ];

        return $map[$table][$column] ?? null;
    }
}
