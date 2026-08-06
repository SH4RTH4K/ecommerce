<?php

namespace App\Http\Controllers;

use App\Services\MediaLifecycleService;
use App\Services\RecycleBinService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RecycleBinController extends Controller
{
    public function index(Request $request, RecycleBinService $recycleBin)
    {
        $this->requireAdminPermission('view_recycle_bin');

        $type = $request->input('type', 'all');
        if (! array_key_exists($type, $recycleBin->types())) {
            $type = 'all';
        }

        return view('admin.admin-master')->with('admin_main_content', view('admin.admin-pages.recycle-bin', [
            'types' => $recycleBin->types(),
            'typeCounts' => $recycleBin->counts(),
            'selectedType' => $type,
            'items' => $recycleBin->items($type),
        ]));
    }

    public function restore(Request $request, string $type, int $id, RecycleBinService $recycleBin)
    {
        $this->requireAdminPermission('restore_deleted_items');
        $recycleBin->restore($type, $id);
        $this->auditAdminAction('RESTORE', [
            'entity_type' => $type,
            'entity_id' => $id,
        ]);

        return Redirect::back()->with('message', 'Item restored from Recycle Bin.');
    }

    public function destroy(Request $request, string $type, int $id, RecycleBinService $recycleBin, MediaLifecycleService $mediaLifecycle)
    {
        $this->requireAdminPermission('permanently_delete_items');

        $validated = $request->validate([
            'confirm_text' => 'required|string|in:DELETE',
        ]);

        $item = $recycleBin->getItem($type, $id);
        abort_unless($item, 404);

        $paths = $recycleBin->purge($type, $id);
        foreach ($paths as $path) {
            $mediaLifecycle->deleteIfUnreferenced($path, [], 'Permanent delete: '.$type.' #'.$id);
        }

        $this->auditAdminAction('PERMANENT_DELETE', [
            'entity_type' => $type,
            'entity_id' => $id,
            'removed_files' => array_map('basename', $paths),
        ]);

        return Redirect::route('recycle-bin.index')->with('message', 'Item permanently deleted.');
    }

    public function bulkRestore(Request $request, string $type, RecycleBinService $recycleBin)
    {
        $this->requireAdminPermission('restore_deleted_items');

        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'required|integer|distinct',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string',
            'items.*.id' => 'required_with:items|integer',
        ]);

        $items = $validated['items'] ?? [];
        $ids = $validated['ids'] ?? [];
        if ($items === [] && $ids === []) {
            return Redirect::back()->withErrors(['ids' => 'Select at least one item first.']);
        }

        $count = $items !== []
            ? $recycleBin->bulkRestoreItems($items)
            : $recycleBin->bulkRestore($type, $ids);
        $this->auditAdminAction('RESTORE', [
            'entity_type' => $type,
            'count' => $count,
            'bulk' => true,
        ]);

        return Redirect::back()->with('message', $count.' item(s) restored.');
    }

    public function bulkDestroy(Request $request, string $type, RecycleBinService $recycleBin, MediaLifecycleService $mediaLifecycle)
    {
        $this->requireAdminPermission('permanently_delete_items');

        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'required|integer|distinct',
            'items' => 'nullable|array',
            'items.*.type' => 'required_with:items|string',
            'items.*.id' => 'required_with:items|integer',
            'confirm_text' => 'required|string|in:DELETE',
        ]);

        $items = $validated['items'] ?? [];
        $ids = $validated['ids'] ?? [];
        if ($items === [] && $ids === []) {
            return Redirect::back()->withErrors(['ids' => 'Select at least one item first.']);
        }

        $paths = $items !== []
            ? $recycleBin->bulkPurgeItems($items)
            : $recycleBin->bulkPurge($type, $ids);
        $count = $items !== []
            ? count($items)
            : count($ids);
        foreach ($paths as $path) {
            $mediaLifecycle->deleteIfUnreferenced($path, [], 'Bulk permanent delete: '.$type);
        }

        $this->auditAdminAction('PERMANENT_DELETE', [
            'entity_type' => $type,
            'count' => $count,
            'bulk' => true,
            'removed_files' => array_map('basename', $paths),
        ]);

        return Redirect::back()->with('message', 'Selected items permanently deleted.');
    }

    public function empty(Request $request, RecycleBinService $recycleBin, MediaLifecycleService $mediaLifecycle)
    {
        $this->requireAdminPermission('empty_recycle_bin');

        $validated = $request->validate([
            'confirm_text' => 'required|string|in:DELETE',
        ]);

        $paths = $recycleBin->empty();
        foreach ($paths as $path) {
            $mediaLifecycle->deleteIfUnreferenced($path, [], 'Empty recycle bin');
        }

        $this->auditAdminAction('EMPTY_RECYCLE_BIN', [
            'removed_files' => array_map('basename', $paths),
        ]);

        return Redirect::route('recycle-bin.index')->with('message', 'Recycle Bin emptied.');
    }
}
