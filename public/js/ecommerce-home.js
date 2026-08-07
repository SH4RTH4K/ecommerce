(function(){
  'use strict';
  var menuButton=document.querySelector('.lt-menu-toggle');
  var menu=document.getElementById('main-menu');

  function closeMainMenu(){
    if(!menuButton||!menu)return;
    menu.classList.remove('is-open');
    menuButton.setAttribute('aria-expanded','false');
  }

  if(menuButton&&menu){
    menuButton.addEventListener('click',function(){
      var open=menu.classList.toggle('is-open');
      menuButton.setAttribute('aria-expanded',String(open));
    });
  }

  document.addEventListener('click',function(event){
    if(menu&&menu.classList.contains('is-open')&&!menu.contains(event.target)&&!(menuButton&&menuButton.contains(event.target))){
      closeMainMenu();
    }
  });

  document.addEventListener('keydown',function(event){
    if(event.key!=='Escape')return;
    var menuWasOpen=menu&&menu.classList.contains('is-open');
    closeMainMenu();
    if(menuWasOpen&&menuButton)menuButton.focus();
  });

  document.querySelectorAll('[data-tab-button]').forEach(function(button){button.addEventListener('click',function(){var target=button.getAttribute('data-tab-button');document.querySelectorAll('[data-tab-button]').forEach(function(item){var active=item===button;item.classList.toggle('is-active',active);item.setAttribute('aria-selected',String(active));});document.querySelectorAll('[data-tab-panel]').forEach(function(panel){panel.hidden=panel.getAttribute('data-tab-panel')!==target;});});});

  var topbar=document.querySelector('[data-topbar]');
  if(topbar&&topbar.getAttribute('data-mode')==='rotating'){var notices=Array.prototype.slice.call(topbar.querySelectorAll('[data-topbar-announcement]')),noticeIndex=0,noticeTimer;function showNotice(next){if(!notices.length)return;noticeIndex=(next+notices.length)%notices.length;notices.forEach(function(item,i){var active=i===noticeIndex;item.classList.toggle('is-active',active);item.setAttribute('aria-hidden',String(!active));});}function startNotices(){clearInterval(noticeTimer);if(notices.length>1&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches)noticeTimer=setInterval(function(){showNotice(noticeIndex+1);},Math.max(2000,parseInt(topbar.getAttribute('data-interval'),10)||5000));}topbar.addEventListener('mouseenter',function(){clearInterval(noticeTimer)});topbar.addEventListener('mouseleave',startNotices);topbar.addEventListener('focusin',function(){clearInterval(noticeTimer)});topbar.addEventListener('focusout',startNotices);startNotices();}

  var featureArea=document.querySelector('[data-feature-cards]');
  if(featureArea){
    var featureCards=Array.prototype.slice.call(featureArea.querySelectorAll('[data-feature-card]')),featureIndex=0,featureTimer;
    function showFeature(next){if(!featureCards.length)return;featureIndex=(next+featureCards.length)%featureCards.length;featureCards.forEach(function(card,i){card.classList.toggle('is-current',i===featureIndex);});}
    if(featureArea.getAttribute('data-layout')==='SLIDER'&&featureCards.length){showFeature(0);if(featureArea.getAttribute('data-autoplay')==='1'&&!window.matchMedia('(prefers-reduced-motion: reduce)').matches){featureTimer=setInterval(function(){showFeature(featureIndex+1);},Math.max(3000,parseInt(featureArea.getAttribute('data-interval'),10)*1000||5000);}if(featureArea.getAttribute('data-pause')==='1'){featureArea.addEventListener('mouseenter',function(){clearInterval(featureTimer)});featureArea.addEventListener('mouseleave',function(){if(featureCards.length>1)featureTimer=setInterval(function(){showFeature(featureIndex+1);},Math.max(3000,parseInt(featureArea.getAttribute('data-interval'),10)*1000||5000);});}}
  }
  var carousel=document.querySelector('[data-carousel]');
  if(!carousel)return;
  var slides=Array.prototype.slice.call(carousel.querySelectorAll('[data-slide]'));var dots=Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-dot]'));var index=0;var paused=false;var timer;
  function show(next){if(!slides.length)return;index=(next+slides.length)%slides.length;slides.forEach(function(slide,i){var active=i===index;slide.classList.toggle('is-active',active);slide.setAttribute('aria-hidden',String(!active));});dots.forEach(function(dot,i){var active=i===index;dot.classList.toggle('is-active',active);dot.setAttribute('aria-selected',String(active));});}
  function start(){clearInterval(timer);if(slides.length<2)return;timer=setInterval(function(){if(!paused)show(index+1);},6000);}
  var previous=carousel.querySelector('[data-prev]'),next=carousel.querySelector('[data-next]'),pause=carousel.querySelector('[data-pause]');
  if(previous)previous.addEventListener('click',function(){show(index-1);start();});
  if(next)next.addEventListener('click',function(){show(index+1);start();});
  dots.forEach(function(dot){dot.addEventListener('click',function(){show(parseInt(dot.getAttribute('data-carousel-dot'),10));start();});});
  if(pause)pause.addEventListener('click',function(){paused=!paused;this.innerHTML=paused?'<i class="fa fa-play"></i>':'<i class="fa fa-pause"></i>';this.setAttribute('aria-label',paused?'Play carousel':'Pause carousel');});
  carousel.addEventListener('keydown',function(event){if(event.key==='ArrowLeft')show(index-1);if(event.key==='ArrowRight')show(index+1);if(event.key===' '&&pause){event.preventDefault();pause.click();}});
  var touchStart=null;
  carousel.addEventListener('touchstart',function(event){touchStart=event.changedTouches[0].clientX;},{passive:true});
  carousel.addEventListener('touchend',function(event){if(touchStart===null)return;var distance=event.changedTouches[0].clientX-touchStart;touchStart=null;if(Math.abs(distance)>45){show(index+(distance<0?1:-1));start();}},{passive:true});
  carousel.addEventListener('mouseenter',function(){paused=true;});
  carousel.addEventListener('mouseleave',function(){paused=false;});
  start();
}());
