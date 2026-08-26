@extends('admin.admin-master')
@section('admin_main_content')
<style>
.au-page{max-width:1180px;padding-bottom:50px}.au-box{margin-bottom:18px;padding:20px;background:#fff;border:1px solid #dfe8ed;border-radius:10px}.au-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.au-field label{display:block;font-size:12px;font-weight:700;color:#3c515e}.au-field input,.au-field select{width:100%;box-sizing:border-box;margin-top:5px;padding:9px;border:1px solid #cbd8df;border-radius:6px}.au-check{display:flex;align-items:center;gap:8px;margin-top:24px;font-size:13px;font-weight:700}.au-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.au-btn{display:inline-block;padding:10px 15px;border:0;border-radius:6px;background:#1467a5;color:#fff;font-weight:700;cursor:pointer}.au-btn.warn{background:#c65312}.au-btn.gray{background:#667787}.au-alert{padding:11px 14px;margin-bottom:15px;border-radius:6px;background:#e9f8ee;color:#176b37}.au-error{background:#fff0f0;color:#9d2828}.au-status{padding:15px;border-left:4px solid #1467a5;background:#f4f8fb}.au-status h3{margin:0 0 6px}.au-workflow{margin-top:18px;border:1px solid #dfe8ed;border-radius:8px;overflow:hidden}.au-step{display:grid;grid-template-columns:36px 1fr;gap:12px;padding:16px;border-bottom:1px solid #e7eef2}.au-step:last-child{border-bottom:0}.au-step-number{width:28px;height:28px;line-height:28px;text-align:center;border-radius:50%;background:#e5f0f7;color:#1467a5;font-weight:700}.au-step.is-blocked .au-step-number{background:#fff0e5;color:#b44b0c}.au-step.is-done .au-step-number{background:#e9f8ee;color:#176b37}.au-step h3{margin:2px 0 5px;font-size:16px}.au-step p{margin:0}.au-commits{width:100%;border-collapse:collapse;margin-top:14px}.au-commits th,.au-commits td{padding:8px;text-align:left;border-bottom:1px solid #e7eef2;font-size:12px}.au-muted{color:#71828c;font-size:12px}.au-secret{font-weight:400;color:#71828c}.au-file-list{margin:10px 0 0;padding:10px 12px 10px 28px;max-height:170px;overflow:auto;background:#fafcfd;border:1px solid #e1e9ee;border-radius:6px;font:12px/1.55 monospace}.au-note{margin-top:10px;padding:10px 12px;border-radius:6px;background:#fff9ec;color:#76521a;font-size:12px;line-height:1.45}@media(max-width:800px){.au-grid{grid-template-columns:1fr 1fr}}@media(max-width:520px){.au-grid{grid-template-columns:1fr}.au-step{grid-template-columns:30px 1fr}}
</style>
<div id="content" class="span10 au-page">
    <ul class="breadcrumb"><li><i class="icon-home"></i> Admin <i class="icon-angle-right"></i></li><li>System <i class="icon-angle-right"></i></li><li>Application Update</li></ul>
    @if(session('message'))<div class="au-alert">{{ session('message') }}</div>@endif
    @if(session('exception'))<div class="au-alert au-error">{{ session('exception') }}</div>@endif

    <div class="au-box">
        <h2>GitHub Repository</h2>
        <p class="au-muted">Configure this once. Updates only read from GitHub; this application never pushes changes to the repository.</p>
        <form method="post" action="{{ route('admin.application-update.settings') }}">@csrf
            <div class="au-grid">
                <div class="au-field"><label>Repository integration<select name="enabled"><option value="0" {{ !$settings->enabled?'selected':'' }}>Off</option><option value="1" {{ $settings->enabled?'selected':'' }}>On</option></select></label></div>
                <div class="au-field"><label>Repository type<select name="repository_type"><option value="public" {{ $settings->repository_type==='public'?'selected':'' }}>Public</option><option value="private" {{ $settings->repository_type==='private'?'selected':'' }}>Private</option></select></label></div>
                <div class="au-field"><label>Branch<input name="branch" value="{{ $settings->branch }}" required></label></div>
                <div class="au-field" style="grid-column:1/-1"><label>GitHub repository URL<input name="repository_url" value="{{ $settings->repository_url }}" placeholder="https://github.com/company/project.git" required><span class="au-muted">The running checkout must already have this repository configured as the selected remote.</span></label></div>
                <div class="au-field"><label>Remote name<input name="remote_name" value="{{ $settings->remote_name }}" required></label></div>
                <div class="au-field"><label>Authentication<select name="authentication"><option value="none" {{ $settings->authentication==='none'?'selected':'' }}>None (public)</option><option value="ssh" {{ $settings->authentication==='ssh'?'selected':'' }}>SSH deploy key</option><option value="pat" {{ $settings->authentication==='pat'?'selected':'' }}>Personal access token</option></select></label></div>
                <div class="au-field"><label>Replace secret <span class="au-secret">(never displayed)</span><input type="password" name="secret" data-has-secret="{{ $settings->encrypted_secret ? '1' : '0' }}" autocomplete="new-password" placeholder="Leave blank to keep current secret"></label></div>
                <div class="au-field"><label>Dependency handling<select name="dependency_mode"><option value="changed" {{ $settings->dependency_mode==='changed'?'selected':'' }}>Automatically when changed</option><option value="always" {{ $settings->dependency_mode==='always'?'selected':'' }}>Always</option><option value="never" {{ $settings->dependency_mode==='never'?'selected':'' }}>Never</option></select></label></div>
                <div class="au-field"><label>Public asset deployment<select name="public_asset_mode"><option value="auto" {{ ($settings->public_asset_mode ?? 'auto')==='auto'?'selected':'' }}>Automatic (cPanel live / local)</option><option value="laravel_public" {{ ($settings->public_asset_mode ?? '')==='laravel_public'?'selected':'' }}>Keep inside Laravel public folder</option><option value="cpanel_root" {{ ($settings->public_asset_mode ?? '')==='cpanel_root'?'selected':'' }}>cPanel main document-root folder</option></select><span class="au-muted">Automatic publishes asset, css, js and svg to the main folder only in production. Local keeps them in public/.</span></label></div>
                <label class="au-check"><input type="hidden" name="run_migrations" value="0"><input type="checkbox" name="run_migrations" value="1" {{ $settings->run_migrations?'checked':'' }}> Run migrations</label>
                <label class="au-check"><input type="hidden" name="clear_cache" value="0"><input type="checkbox" name="clear_cache" value="1" {{ $settings->clear_cache?'checked':'' }}> Clear cache</label>
                <label class="au-check"><input type="hidden" name="health_check" value="0"><input type="checkbox" name="health_check" value="1" {{ $settings->health_check?'checked':'' }}> Health check</label>
            </div>
            <div class="au-actions"><button class="au-btn" type="submit">Save Repository Settings</button></div>
        </form>
    </div>

    <div class="au-box">
        <h2>Safe update workflow</h2>
        <p class="au-muted">Follow the numbered steps. Code is only changed at the final deployment step.</p>

        @if($error)
            <div class="au-alert au-error">{{ $error }}</div>
        @elseif(!$settings->enabled)
            <div class="au-status"><h3>Integration disabled</h3><p class="au-muted">Enable and save the repository integration before beginning an update.</p></div>
        @elseif($status)
            <div class="au-status"><h3>{{ $status['status'] }}</h3><p>{{ $status['message'] }}</p><p class="au-muted">Current: <code>{{ substr((string)$status['local'],0,12) }}</code> · Remote: <code>{{ substr((string)$status['remote'],0,12) }}</code> · Branch: {{ $settings->branch }}</p></div>
        @endif

        @if($settings->enabled)
            <div class="au-workflow">
                <section class="au-step">
                    <div class="au-step-number">1</div>
                    <div><h3>Verify repository connection</h3><p class="au-muted">Confirm that this server can access the configured GitHub repository and branch.</p><div class="au-actions"><form method="post" action="{{ route('admin.application-update.test') }}">@csrf<button class="au-btn gray">Test Connection</button></form></div></div>
                </section>
                <section class="au-step">
                    <div class="au-step-number">2</div>
                    <div><h3>Check GitHub for updates</h3><p class="au-muted">Fetch the latest branch state and review the commits before anything is deployed.</p><div class="au-actions"><form method="post" action="{{ route('admin.application-update.check') }}">@csrf<button class="au-btn">Check for Updates</button></form></div></div>
                </section>
                <section class="au-step {{ $status && $status['status']==='Local Changes Detected' ? 'is-blocked' : '' }}">
                    <div class="au-step-number">3</div>
                    <div>
                        <h3>Resolve server-side changes</h3>
                        @if($status && $status['status']==='Local Changes Detected')
                            <p>Tracked files were edited on this server. Review or preserve those edits outside this workflow, then check again. If they are no longer needed, discard only the tracked edits below.</p>
                            @if(!empty($status['local_changes']))<ul class="au-file-list">@foreach($status['local_changes'] as $change)<li>{{ $change }}</li>@endforeach</ul>@endif
                            <div class="au-note">Untracked files, including uploads and runtime files, are kept. This action resets only tracked Git files, then deploys the reviewed update.</div>
                            <div class="au-actions"><form method="post" action="{{ route('admin.application-update.discard-and-deploy') }}" onsubmit="var value=prompt('This discards the tracked server changes shown above. Type DISCARD LOCAL CHANGES to continue.');if(value!=='DISCARD LOCAL CHANGES'){alert('Deployment cancelled.');return false;}this.confirmation.value=value;return true;">@csrf<input type="hidden" name="confirmation" value=""><button class="au-btn warn">Discard Tracked Changes &amp; Deploy</button></form></div>
                        @elseif($status && $status['status']==='Diverged Branch')
                            <p class="au-muted">The server branch and GitHub have different history. Resolve this manually with a maintainer before deploying.</p>
                        @elseif($status && $status['status']==='Update Available')
                            <p class="au-muted">No blocking tracked server changes were found. Review the listed commits, then continue to deployment.</p>
                        @else
                            <p class="au-muted">No tracked server changes are blocking the update path.</p>
                        @endif
                    </div>
                </section>
                <section class="au-step {{ $status && $status['status']==='Up to date' ? 'is-done' : '' }}">
                    <div class="au-step-number">4</div>
                    <div>
                        <h3>Deploy reviewed update</h3>
                        @if($status && $status['status']==='Update Available')
                            <p>Fast-forward to the reviewed GitHub commit, then run the enabled deployment checks.</p>
                            <div class="au-actions"><form method="post" action="{{ route('admin.application-update.deploy') }}" onsubmit="return confirm('Deploy the reviewed fast-forward update now?')">@csrf<button class="au-btn warn">Deploy Reviewed Update</button></form></div>
                        @elseif($status && $status['status']==='Up to date')
                            <p class="au-muted">The server is already on the latest configured commit.</p>
                        @else
                            <p class="au-muted">Complete the earlier steps before deployment becomes available.</p>
                        @endif
                    </div>
                </section>
            </div>
            <div class="au-note"><strong>Public asset deployment:</strong> {{ ($settings->public_asset_mode ?? 'auto') === 'cpanel_root' ? 'cPanel main document-root folder' : ((($settings->public_asset_mode ?? 'auto') === 'laravel_public') ? 'Laravel public/ folder' : 'Automatic: cPanel main folder in production, Laravel public/ locally') }}. <form method="post" action="{{ route('admin.application-update.publish-public-assets') }}" style="display:inline" onsubmit="return confirm('Publish the tracked public asset directories using the selected deployment mode?')">@csrf<button class="au-btn gray" type="submit">Publish Public Assets Now</button></form></div>
        @endif

        @if($status && count($status['commits']))
            <h3>Updates to review ({{ count($status['commits']) }})</h3>
            <table class="au-commits"><thead><tr><th>Commit</th><th>Subject</th><th>Author</th><th>Date</th></tr></thead><tbody>@foreach($status['commits'] as $commit)<tr><td><code>{{ $commit['short'] }}</code></td><td>{{ $commit['subject'] }}</td><td>{{ $commit['author'] }}</td><td>{{ $commit['date'] }}</td></tr>@endforeach</tbody></table>
        @endif
    </div>

    <div class="au-box">
        <h2>Deployment History</h2>
        <table class="au-commits"><thead><tr><th>Date</th><th>Branch</th><th>From</th><th>To</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($history as $item)<tr><td>{{ optional($item->created_at)->format('Y-m-d H:i') }}</td><td>{{ $item->branch }}</td><td><code>{{ substr((string)$item->previous_commit,0,12) }}</code></td><td><code>{{ substr((string)($item->deployed_commit ?: $item->target_commit),0,12) }}</code></td><td>{{ strtoupper($item->status) }}</td><td>@if($item->status==='success' && !$item->rollback_of)<form method="post" action="{{ route('admin.application-update.rollback',$item->id) }}" onsubmit="return confirm('Rollback source to the recorded previous commit? Database migrations will not be reversed.')">@csrf<button class="au-btn gray">Rollback source</button></form>@endif</td></tr>@empty<tr><td colspan="6" class="au-muted">No deployments recorded.</td></tr>@endforelse</tbody></table>
        <p class="au-muted">Rollback restores source using the recorded deployment commit. It does not reverse database migrations.</p>
    </div>
</div>
<script>(function(){var form=document.querySelector('.au-box form[action="{{ route('admin.application-update.settings') }}"]');if(!form)return;var type=form.querySelector('[name="repository_type"]'),auth=form.querySelector('[name="authentication"]'),secret=form.querySelector('[name="secret"]');function sync(){var privateRepo=type.value==='private';auth.required=privateRepo;secret.required=privateRepo&&secret.getAttribute('data-has-secret')!=='1';}type.addEventListener('change',sync);sync();}());</script>
@endsection
