<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditAdminActivity
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'admin_password', 'current_password',
        'secret', 'credential_value', 'api_key', 'access_token', 'refresh_token',
        'private_key', 'client_secret', 'authorization', 'card_number', 'cvv', 'cvc',
    ];

    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($request->session()->has('admin_id') && ! $request->isMethod('get') && Schema::hasTable('admin_activity_logs')) {
            try {
                $input = $this->redact($request->except(['_token']));
                DB::table('admin_activity_logs')->insert([
                    'admin_id' => $request->session()->get('admin_id'),
                    'admin_name' => $request->session()->get('admin_name'),
                    'action' => $this->action($request),
                    'method' => $request->method(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'ip_hash' => hash_hmac('sha256', (string) $request->ip(), config('app.key')),
                    'details' => json_encode($input),
                    'created_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Audit logging must never interrupt the administrator's request.
            }
        }

        return $response;
    }

    private function redact(array $input): array
    {
        foreach ($input as $key => $value) {
            $normalized = strtolower(str_replace(['-', '.'], '_', (string) $key));
            $isSensitive = in_array($normalized, self::SENSITIVE_KEYS, true)
                || preg_match('/(?:password|passwd|secret|token|api_?key|private_?key|credential|authorization|card_?number|cvv|cvc)/i', $normalized);

            $input[$key] = $isSensitive
                ? '[REDACTED]'
                : (is_array($value) ? $this->redact($value) : $value);
        }

        return $input;
    }

    private function action($request): string
    {
        return ucwords(str_replace(['-', '/'], ' ', trim($request->path(), '/')));
    }
}
