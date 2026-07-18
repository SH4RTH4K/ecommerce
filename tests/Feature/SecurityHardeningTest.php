<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditAdminActivity;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Services\SafeExternalUrl;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    public function testBrowserSecurityHeadersAreApplied(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy');
    }

    public function testCatalogMutationEndpointsDoNotAcceptGetRequests(): void
    {
        $targets = [
            'delete-category/{id}',
            'delete-subCategory/{id}',
            'delete-manufacturer/{id}',
            'delete-product/{id}',
            'unpublished-category/{id}',
            'published-category/{id}',
            'unpublished-subCategory/{id}',
            'published-subCategory/{id}',
            'unpublished-manufacturer/{id}',
            'published-manufacturer/{id}',
            'unpublished-product/{id}',
            'published-product/{id}',
        ];

        foreach ($targets as $target) {
            $route = collect(Route::getRoutes())->first(fn ($route) => $route->uri() === $target);
            $this->assertNotNull($route);
            $this->assertSame(['POST'], $route->methods());
        }
    }

    public function testPaymentApprovalRequiresSettingsPermission(): void
    {
        $reflection = new ReflectionClass(EnsureAdminAuthenticated::class);
        $method = $reflection->getMethod('permissionFor');
        $method->setAccessible(true);

        $this->assertSame('settings', $method->invoke(new EnsureAdminAuthenticated(), 'payment-transactions/42/verify'));
    }

    public function testAdminAuditRecursivelyRedactsCredentials(): void
    {
        $reflection = new ReflectionClass(AuditAdminActivity::class);
        $method = $reflection->getMethod('redact');
        $method->setAccessible(true);
        $result = $method->invoke(new AuditAdminActivity(), [
            'name' => 'Gateway',
            'credential_value' => 'live-secret',
            'nested' => ['api-key' => 'another-secret', 'safe' => 'visible'],
        ]);

        $this->assertSame('Gateway', $result['name']);
        $this->assertSame('[REDACTED]', $result['credential_value']);
        $this->assertSame('[REDACTED]', $result['nested']['api-key']);
        $this->assertSame('visible', $result['nested']['safe']);
        $this->assertStringNotContainsString('live-secret', json_encode($result));
    }

    public function testWebhookDestinationsRejectInternalAndInsecureAddresses(): void
    {
        $validator = new SafeExternalUrl();

        $this->assertFalse($validator->isAllowed('http://example.com/hook'));
        $this->assertFalse($validator->isAllowed('https://localhost/hook'));
        $this->assertFalse($validator->isAllowed('https://127.0.0.1/hook'));
        $this->assertFalse($validator->isAllowed('https://10.1.2.3/hook'));
        $this->assertFalse($validator->isAllowed('https://[::1]/hook'));
    }

    public function testProductDescriptionUsesEscapedOutput(): void
    {
        $view = file_get_contents(resource_path('views/front-end/pages/product-details.blade.php'));

        $this->assertStringContainsString('nl2br(e($product_details->product_description', $view);
        $this->assertStringNotContainsString('{!! $product_details->product_description', $view);
    }
}
