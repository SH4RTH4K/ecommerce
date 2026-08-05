<?php

namespace App\Console\Commands;

use App\Services\MediaLifecycleService;
use Illuminate\Console\Command;

class ScanOrphanMedia extends Command
{
    protected $signature = 'scan_orphan_media {--dry-run : Only report potential orphans} {--delete : Delete confirmed orphan files} {--entity=all : products, banners, branding, payments, or all} {--older-than=24 : Minimum age in hours before a file can be considered orphaned}';

    protected $description = 'Scan managed uploads for referenced, orphaned, protected, and unknown files.';

    public function handle(MediaLifecycleService $mediaLifecycle): int
    {
        $dryRun = (bool) $this->option('dry-run') || ! (bool) $this->option('delete');
        $entity = strtolower(trim((string) $this->option('entity')));
        $olderThanHours = max(0, (int) $this->option('older-than'));
        $olderThanDays = max(0, (int) ceil($olderThanHours / 24));

        $report = $mediaLifecycle->scanManagedMedia([
            'entity' => $entity ?: 'all',
            'older_than_days' => $olderThanDays,
            'delete' => ! $dryRun,
        ]);

        $summary = $report['summary'];
        $this->info('Scanned Files: '.$summary['scanned']);
        $this->line('Referenced: '.$summary['referenced']);
        $this->line('Potential Orphans: '.$summary['orphan']);
        $this->line('Protected: '.$summary['protected']);
        $this->line('Unknown: '.$summary['unknown']);
        $this->line('Deleted: '.$summary['deleted']);

        if ($report['rows']) {
            $this->table(
                ['Status', 'Entity', 'Path', 'Last Modified', 'Size', 'Reason'],
                array_map(static function (array $row) {
                    return [
                        $row['status'],
                        $row['entity_type'],
                        $row['path'],
                        $row['last_modified'],
                        $row['size_human'],
                        $row['reason'],
                    ];
                }, array_slice($report['rows'], 0, 100))
            );
        }

        return self::SUCCESS;
    }
}
