<?php

namespace App\Services;

use App\Models\ApplicationDeployment;
use App\Models\ApplicationUpdateSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class ApplicationUpdateService
{
    private const LOCK_FILE = 'application-update.lock';

    public function settings(): ApplicationUpdateSetting
    {
        return ApplicationUpdateSetting::first() ?: ApplicationUpdateSetting::create([
            'enabled' => false, 'provider' => 'github', 'repository_type' => 'public',
            'branch' => 'main', 'remote_name' => 'origin', 'authentication' => 'none',
        ]);
    }

    public function saveSecret(ApplicationUpdateSetting $settings, ?string $secret): void
    {
        $secret = trim((string) $secret);
        if ($secret === '') return;
        $settings->encrypted_secret = Crypt::encryptString($secret);
        $settings->secret_fingerprint = hash('sha256', $secret);
    }

    public function status(?ApplicationUpdateSetting $settings = null): array
    {
        $settings ??= $this->settings();
        $base = ['configured' => false, 'status' => 'Disabled', 'message' => 'Git repository integration is disabled.', 'local' => null, 'remote' => null, 'commits' => [], 'changed_files' => []];
        if (!$settings->enabled) return $base;
        $this->validateConfiguration($settings);
        $this->assertRepository();
        $currentBranch = trim($this->git(['branch', '--show-current'], false)['output']);
        if ($currentBranch !== $settings->branch) return array_merge($base, ['configured' => true, 'status' => 'Branch Not Found', 'message' => 'The deployed checkout is not currently on the configured branch.', 'local' => null]);
        $local = $this->git(['rev-parse', 'HEAD']);
        $remote = $this->git(['remote', 'get-url', $settings->remote_name]);
        $this->assertRepositoryMatches($settings, trim($remote['output']));
        $branch = $this->git(['rev-parse', '--verify', $settings->remote_name.'/'.$settings->branch], false);
        if ($branch['code'] !== 0) return array_merge($base, ['configured' => true, 'status' => 'Branch Not Found', 'message' => 'The configured branch was not found on the configured remote.', 'local' => trim($local['output'])]);
        $target = trim($branch['output']);
        $count = $this->git(['rev-list', '--count', 'HEAD..'.$settings->remote_name.'/'.$settings->branch]);
        $commits = $this->git(['log', '--format=%H%x1f%h%x1f%an%x1f%aI%x1f%s', 'HEAD..'.$settings->remote_name.'/'.$settings->branch, '-n', '50']);
        $rows = [];
        foreach (array_filter(preg_split('/\R/', trim($commits['output']))) as $line) { $parts = explode("\x1f", $line, 5); if (count($parts) === 5) $rows[] = ['hash' => $parts[0], 'short' => $parts[1], 'author' => $parts[2], 'date' => $parts[3], 'subject' => $parts[4]]; }
        $changed = $this->git(['diff', '--name-status', 'HEAD', $settings->remote_name.'/'.$settings->branch])['output'];
        $worktreeLines = array_values(array_filter(preg_split('/\R/', trim($this->git(['status', '--porcelain'])['output']))));
        // Untracked files include uploads, caches, and deployment artifacts. They
        // do not prevent a fast-forward merge and must never make the discard
        // option look as if it will remove them.
        $trackedWorktreeLines = array_values(array_filter($worktreeLines, static fn ($line) => ! str_starts_with($line, '??') && ! str_starts_with($line, '!!')));
        $clean = $trackedWorktreeLines === [];
        // A manual copy may have installed the exact remote files without
        // moving HEAD. It is safe to normalize that state; other edits stay blocked.
        $localChangesMatchTarget = ! $clean
            && $this->git(['diff', '--quiet', $settings->remote_name.'/'.$settings->branch, '--'], false)['code'] === 0;
        $deployable = $clean || $localChangesMatchTarget;
        $ahead = trim($this->git(['rev-list', '--count', $settings->remote_name.'/'.$settings->branch.'..HEAD'])['output']) !== '0';
        return ['configured' => true, 'status' => !$deployable ? 'Local Changes Detected' : ($ahead ? 'Diverged Branch' : ((int) trim($count['output']) ? 'Update Available' : 'Up to date')), 'message' => !$deployable ? 'Deployment is blocked because tracked server changes exist.' : ($localChangesMatchTarget ? 'The checkout already contains the remote files. Deployment will synchronize its Git state.' : ($ahead ? 'Branch has diverged; manual Git intervention is required.' : ((int) trim($count['output']) ? trim($count['output']).' update(s) available.' : 'Application is up to date.'))), 'local' => trim($local['output']), 'remote' => $target, 'commits' => $rows, 'changed_files' => array_values(array_filter(preg_split('/\R/', trim($changed)))), 'local_changes' => $trackedWorktreeLines, 'untracked_files' => array_values(array_filter($worktreeLines, static fn ($line) => str_starts_with($line, '??'))), 'local_changes_match_target' => $localChangesMatchTarget];
    }

    public function fetch(ApplicationUpdateSetting $settings): array
    {
        $this->validateConfiguration($settings); $this->assertRepository();
        $remote = $this->git(['remote', 'get-url', $settings->remote_name]); $this->assertRepositoryMatches($settings, trim($remote['output']));
        $result = $this->git(['fetch', '--prune', $settings->remote_name, $settings->branch]);
        if ($result['code'] !== 0) throw new \RuntimeException('Unable to fetch the configured GitHub repository. Check repository access and branch settings.');
        return $this->status($settings);
    }

    public function pullLatest(ApplicationUpdateSetting $settings): array
    {
        $handle = $this->lock();
        try {
            $status = $this->fetch($settings);
            if ($status['status'] === 'Local Changes Detected') {
                throw new \RuntimeException('Pull blocked because local tracked changes exist. Review them or use Discard Local Changes & Deploy.');
            }
            if ($status['status'] === 'Diverged Branch') {
                throw new \RuntimeException('Pull blocked because the local branch has diverged from GitHub. Resolve the branch history first.');
            }
            if ($status['status'] === 'Update Available') {
                $this->gitOrFail(['merge', '--ff-only', $settings->remote_name.'/'.$settings->branch], 'Fast-forward pull was not possible.');
            }
            return $this->status($settings);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function deploy(ApplicationUpdateSetting $settings, int $adminId, bool $discardLocalChanges = false): ApplicationDeployment
    {
        $handle = $this->lock();
        try {
            $status = $this->fetch($settings);
            if ($discardLocalChanges && $status['status'] === 'Local Changes Detected') {
                // Only tracked source files are reset. Untracked files (uploads,
                // environment files, and runtime data) are deliberately kept.
                $this->gitOrFail(['reset', '--hard', 'HEAD'], 'The local tracked changes could not be discarded.');
                $status = $this->fetch($settings);
            }
            if ($status['status'] !== 'Update Available') throw new \RuntimeException($status['message']);
            if (! empty($status['local_changes_match_target'])) {
                $this->gitOrFail(['reset', '--hard', $status['remote']], 'The checkout could not be synchronized with the remote update.');
            }
            $deployment = ApplicationDeployment::create(['repository_url' => $settings->repository_url, 'branch' => $settings->branch, 'previous_commit' => $status['local'], 'target_commit' => $status['remote'], 'status' => 'running', 'commits_applied' => count($status['commits']), 'started_by' => $adminId, 'started_at' => now(), 'safe_log' => 'Validation passed. Fetch completed.']);
            try {
                $merge = $this->git(['merge', '--ff-only', $settings->remote_name.'/'.$settings->branch]);
                if ($merge['code'] !== 0) throw new \RuntimeException('Fast-forward update was not possible. The branch may have diverged.');
                $deployment->deployed_commit = trim($this->git(['rev-parse', 'HEAD'])['output']);
                $deployment->migration_status = 'not_run'; $deployment->dependency_status = 'not_run'; $deployment->static_status = 'not_applicable';
                if ($settings->run_migrations) { $this->artisan('migrate', '--force'); $deployment->migration_status = 'successful'; }
                if ($settings->clear_cache) { $this->artisan('optimize:clear'); $deployment->safe_log .= "\nCache cleared."; }
                if ($settings->health_check) { $this->healthCheck(); $deployment->health_status = 'passed'; }
                else $deployment->health_status = 'skipped';
                $deployment->status = 'success'; $deployment->completed_at = now(); $deployment->save();
                return $deployment;
            } catch (\Throwable $e) {
                $deployment->status = 'failed'; $deployment->failed_stage = $deployment->migration_status === 'successful' ? 'health_or_finish' : 'source_or_migration'; $deployment->error_summary = $this->safeError($e->getMessage()); $deployment->completed_at = now(); $deployment->save(); throw $e;
            }
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    public function rollback(ApplicationDeployment $deployment, int $adminId): ApplicationDeployment
    {
        if ($deployment->status !== 'success' || !$deployment->previous_commit) throw new \RuntimeException('Only a successful deployment with a recorded previous commit can be rolled back.');
        $settings = $this->settings(); $handle = $this->lock();
        try { $this->assertRepository(); $current = trim($this->git(['rev-parse', 'HEAD'])['output']); $this->gitOrFail(['revert', '--no-edit', '--no-commit', $deployment->previous_commit.'..'.$current], 'Source rollback failed; resolve the source history manually.'); $this->gitOrFail(['commit', '-m', 'Rollback application deployment #'.$deployment->id], 'Rollback commit failed.'); $rollback = ApplicationDeployment::create(['repository_url'=>$settings->repository_url,'branch'=>$settings->branch,'previous_commit'=>$current,'target_commit'=>$deployment->previous_commit,'deployed_commit'=>trim($this->git(['rev-parse','HEAD'])['output']),'status'=>'success','started_by'=>$adminId,'started_at'=>now(),'completed_at'=>now(),'rollback_of'=>$deployment->id,'safe_log'=>'Source rollback completed. Database migrations were not reversed.']); return $rollback; }
        finally { flock($handle, LOCK_UN); fclose($handle); }
    }

    private function validateConfiguration(ApplicationUpdateSetting $s): void { if ($s->provider !== 'github' || !in_array($s->repository_type, ['public','private'], true) || !preg_match('#^(https://github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+(?:\.git)?|git@github\.com:[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+\.git)$#', trim((string)$s->repository_url))) throw new \RuntimeException('Enter a valid GitHub repository URL.'); if (!preg_match('/^[A-Za-z0-9._-]{1,100}$/', $s->branch) || !preg_match('/^[A-Za-z0-9._-]{1,50}$/', $s->remote_name)) throw new \RuntimeException('Branch or remote name is invalid.'); if ($s->repository_type === 'private' && !in_array($s->authentication, ['ssh','pat'], true)) throw new \RuntimeException('Private repositories require SSH deploy-key or PAT authentication.'); }
    private function assertRepository(): void { if (!is_dir(base_path('.git'))) throw new \RuntimeException('This installation is not currently managed by Git.'); }
    private function assertRepositoryMatches($s, string $actual): void { $normalize = fn($v) => rtrim(strtolower(preg_replace('#^https://github\.com/#','github:',preg_replace('#^git@github\.com:#','github:',rtrim(trim($v), '/')))), '.git'); if ($normalize($actual) !== $normalize($s->repository_url)) throw new \RuntimeException('Configured repository does not match the repository currently deployed in this application directory.'); }
    private function git(array $args, bool $throw = true): array { $env = []; $secret = null; $s = $this->settings(); if ($s->enabled && $s->encrypted_secret) { try {$secret = Crypt::decryptString($s->encrypted_secret);} catch(\Throwable $e){} } $temp = null; if ($secret && $s->authentication === 'ssh') { $temp = storage_path('app/.git-deploy-key'); File::put($temp, $secret); @chmod($temp, 0600); $env['GIT_SSH_COMMAND'] = 'ssh -i "'.$temp.'" -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new'; } elseif ($secret && $s->authentication === 'pat') { $temp = storage_path('app/.git-askpass.bat'); File::put($temp, "@echo off\r\necho ".$secret."\r\n"); $env['GIT_ASKPASS'] = $temp; $env['GIT_TERMINAL_PROMPT'] = '0'; } try { $p = new Process(array_merge(['git'], $args), base_path(), $env, null, 60); $p->run(); $result = ['code'=>$p->getExitCode() ?? 1, 'output'=>trim($p->getOutput()), 'error'=>trim($p->getErrorOutput())]; if ($throw && $result['code'] !== 0) throw new \RuntimeException($this->safeError($result['error'] ?: 'Git operation failed.')); return $result; } finally { if ($temp && is_file($temp)) @unlink($temp); } }
    private function gitOrFail(array $args, string $message): void { $r=$this->git($args,false); if($r['code']!==0) throw new \RuntimeException($message); }
    private function artisan(string ...$args): void { $p=new Process(array_merge([PHP_BINARY, base_path('artisan')],$args),base_path(),null,null,300);$p->run();if($p->getExitCode()!==0)throw new \RuntimeException('Approved deployment task failed.'); }
    private function healthCheck(): void { DB::select('SELECT 1'); if(!app()->bound('router'))throw new \RuntimeException('Application health check failed.'); }
    private function safeError(string $error): string { return preg_replace('/(?:token|password|secret|private.?key|authorization)[^\s]*/i', '[REDACTED]', substr($error,0,2000)); }
    private function lock() { $path=storage_path('app/'.self::LOCK_FILE);$h=fopen($path,'c');if(!$h||!flock($h,LOCK_EX|LOCK_NB))throw new \RuntimeException('Another application deployment is already in progress.');return $h; }
}
