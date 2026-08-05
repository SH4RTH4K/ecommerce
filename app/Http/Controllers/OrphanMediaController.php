<?php

namespace App\Http\Controllers;

use App\Services\MediaLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class OrphanMediaController extends Controller
{
    public function index(Request $request, MediaLifecycleService $mediaLifecycle)
    {
        $this->requireAdminPermission('view_orphan_media');

        $entity = strtolower(trim((string) $request->input('entity', 'all')));
        if (! in_array($entity, ['all', 'products', 'banners', 'branding', 'payments'], true)) {
            $entity = 'all';
        }

        $olderThanHours = max(0, (int) $request->input('older_than_hours', 24));
        $report = $mediaLifecycle->scanManagedMedia([
            'entity' => $entity,
            'older_than_days' => max(1, (int) ceil($olderThanHours / 24)),
            'delete' => false,
        ]);

        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.orphan-media', [
            'selectedEntity' => $entity,
            'olderThanHours' => $olderThanHours,
            'report' => $report,
            'entityOptions' => [
                'all' => 'All media',
                'products' => 'Products',
                'banners' => 'Banners',
                'branding' => 'Branding',
                'payments' => 'Payments',
            ],
        ]));
    }

    public function cleanup(Request $request, MediaLifecycleService $mediaLifecycle)
    {
        $this->requireAdminPermission('cleanup_orphan_media');

        $validated = $request->validate([
            'confirm_text' => 'required|string|in:DELETE',
            'entity' => 'nullable|string',
            'older_than_hours' => 'nullable|integer|min:0|max:8760',
        ]);

        $entity = strtolower(trim((string) ($validated['entity'] ?? 'all')));
        if (! in_array($entity, ['all', 'products', 'banners', 'branding', 'payments'], true)) {
            $entity = 'all';
        }

        $olderThanHours = max(0, (int) ($validated['older_than_hours'] ?? 24));
        $report = $mediaLifecycle->scanManagedMedia([
            'entity' => $entity,
            'older_than_days' => max(1, (int) ceil($olderThanHours / 24)),
            'delete' => true,
        ]);

        $this->auditAdminAction('ORPHAN_MEDIA_DELETE', [
            'entity' => $entity,
            'older_than_hours' => $olderThanHours,
            'deleted' => $report['summary']['deleted'],
        ]);

        return Redirect::route('orphan-media.index', [
            'entity' => $entity,
            'older_than_hours' => $olderThanHours,
        ])->with('message', $report['summary']['deleted'].' orphan file(s) deleted.');
    }
}
