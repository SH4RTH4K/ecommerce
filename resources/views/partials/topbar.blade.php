@php
$s=$topBar['settings'];$on=function($key,$default='0')use($s){return in_array($s->get($key,$default),[true,1,'1','true','on'],true);};
$announcements=$on('top_bar_show_announcement','1')?$topBar['announcements']:collect();$contacts=$on('top_bar_show_contacts','1')?$topBar['contacts']:collect();
$hasDesktop=$announcements->contains('show_on_desktop',true)||$contacts->contains('show_on_desktop',true);$hasMobile=$on('top_bar_mobile_enabled','1')&&($announcements->contains('show_on_mobile',true)||$contacts->contains('show_on_mobile',true));
@endphp
@if($on('top_bar_enabled','1')&&($hasDesktop||$hasMobile))
<div class="lt-topbar {{ $on('top_bar_sticky')?'is-sticky':'' }} {{ !$hasMobile?'is-mobile-hidden':'' }}" style="--tb-bg:{{ $s->get('top_bar_background_color','#073451') }};--tb-text:{{ $s->get('top_bar_text_color','#ffffff') }};--tb-link:{{ $s->get('top_bar_link_color','#ffffff') }};--tb-height:{{ (int)$s->get('top_bar_height',36) }}px" data-topbar data-mode="{{ $topBar['mode'] }}" data-interval="{{ (int)$s->get('announcement_rotation_interval',5000) }}">
 <div class="lt-container lt-topbar-inner">
 @if($announcements->isNotEmpty())<div class="lt-topbar-announcements mode-{{ $topBar['mode'] }}" aria-live="polite"><div class="lt-topbar-announcement-track">@foreach($announcements as $index=>$a)<div class="lt-topbar-announcement {{ $index===0?'is-active':'' }} {{ $a->show_on_desktop?'show-desktop':'hide-desktop' }} {{ $a->show_on_mobile?'show-mobile':'hide-mobile' }}" data-topbar-announcement aria-hidden="{{ $index===0?'false':'true' }}">@if($a->show_type_badge)<span class="lt-topbar-type">{{ ucfirst($a->announcement_type) }}</span>@endif<span>{{ $a->message }}</span>@if($a->link_url)<a href="{{ $a->link_url }}" @if($a->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif>{{ $a->link_text?:'Learn more' }}</a>@endif</div>@endforeach</div></div>@endif
 @if($contacts->isNotEmpty())<nav class="lt-topbar-contacts" aria-label="Contact information">@foreach($contacts as $c)@if($c->resolved_url)<a class="{{ $c->show_on_desktop?'show-desktop':'hide-desktop' }} {{ $c->show_on_mobile?'show-mobile':'hide-mobile' }}" href="{{ $c->resolved_url }}" aria-label="{{ $c->label }}: {{ $c->value }}" @if($c->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif><i class="fa {{ $c->icon?:'fa-link' }}" aria-hidden="true"></i><span>{{ $c->label }}@if($c->value!==$c->label)<b>{{ $c->value }}</b>@endif</span></a>@endif @endforeach</nav>@endif
 </div>
</div>
@endif
