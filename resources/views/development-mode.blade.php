<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $mode['title'] }} - {{ $mode['site_name'] }}</title>
    @if($mode['favicon'])<link rel="icon" href="{{ asset($mode['favicon']) }}">@endif
    <style>
        :root{color-scheme:light;--navy:#0b3d62;--orange:#f5821f;--ink:#163247;--muted:#637887}*{box-sizing:border-box}body{margin:0;min-height:100vh;font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;color:var(--ink);background:radial-gradient(circle at 10% 10%,rgba(245,130,31,.14),transparent 28%),radial-gradient(circle at 90% 85%,rgba(11,61,98,.16),transparent 32%),#f5f8fa;display:grid;place-items:center;padding:24px}.dm-shell{width:min(920px,100%);background:#fff;border:1px solid #dfe8ee;border-radius:24px;box-shadow:0 24px 70px rgba(17,53,75,.15);overflow:hidden}.dm-accent{height:7px;background:linear-gradient(90deg,var(--navy),#16789f,var(--orange))}.dm-content{padding:clamp(30px,7vw,72px);text-align:center}.dm-logo{display:block;max-width:220px;max-height:76px;object-fit:contain;margin:0 auto 28px}.dm-brand{font-weight:800;color:var(--navy);letter-spacing:.03em;margin-bottom:28px}.dm-icon{width:86px;height:86px;margin:0 auto 24px;border-radius:24px;background:#edf5f9;color:var(--navy);display:grid;place-items:center;font-size:38px;font-weight:800}.dm-badge{display:inline-flex;padding:7px 13px;border-radius:999px;background:#fff2e6;color:#b94f00;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.dm-title{font-size:clamp(30px,5vw,52px);line-height:1.08;color:var(--navy);margin:18px auto 16px;max-width:760px}.dm-message{font-size:clamp(17px,2.5vw,21px);line-height:1.65;max-width:700px;margin:0 auto;color:var(--muted);white-space:pre-line}.dm-additional{max-width:650px;margin:20px auto 0;line-height:1.65;white-space:pre-line}.dm-availability{display:inline-block;margin-top:25px;padding:11px 16px;background:#f2f7fa;border-radius:10px;color:#39566a;font-weight:700}.dm-actions{margin-top:30px}.dm-login{display:inline-block;padding:13px 23px;border-radius:10px;background:var(--navy);color:#fff;text-decoration:none;font-weight:800;box-shadow:0 8px 20px rgba(11,61,98,.2)}.dm-login:hover,.dm-login:focus{background:#062c48;outline:3px solid rgba(245,130,31,.35);outline-offset:3px}.dm-footer{margin-top:44px;color:#82929d;font-size:13px}@media(max-width:520px){body{padding:12px}.dm-shell{border-radius:16px}.dm-content{padding:32px 22px}.dm-icon{width:72px;height:72px;font-size:31px}.dm-footer{margin-top:34px}}
    </style>
</head>
<body>
    <main class="dm-shell">
        <div class="dm-accent"></div>
        <div class="dm-content">
            @if($mode['logo'])<img class="dm-logo" src="{{ asset($mode['logo']) }}" alt="{{ $mode['site_name'] }}">@else<div class="dm-brand">{{ $mode['site_name'] }}</div>@endif
            <div class="dm-icon" aria-hidden="true">@if($mode['icon']==='rocket')&#8593;@elseif($mode['icon']==='warning')!@elseif($mode['icon']==='message')&#8220;@elseif($mode['icon']==='code')&lt;/&gt;@elseif($mode['icon']==='upgrade')&#8635;@else&#9881;@endif</div>
            <span class="dm-badge">{{ $mode['badge'] }}</span>
            <h1 class="dm-title">{{ $mode['title'] }}</h1>
            <p class="dm-message">{{ $mode['message'] }}</p>
            @if($mode['additional_message'])<p class="dm-additional">{{ $mode['additional_message'] }}</p>@endif
            @if($mode['availability_text'])<div class="dm-availability">{{ $mode['availability_text'] }}</div>@endif
            @if($mode['show_admin_login'])<div class="dm-actions"><a class="dm-login" href="{{ route('admin.login') }}">{{ $mode['login_button_text'] }}</a></div>@endif
            <footer class="dm-footer">{{ $mode['copyright'] ? str_replace('{year}', date('Y'), $mode['copyright']) : '© '.date('Y').' '.$mode['site_name'].'. All rights reserved.' }}</footer>
        </div>
    </main>
</body>
</html>
