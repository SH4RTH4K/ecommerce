(function(){
  'use strict';
  var menuButton=document.querySelector('.lt-menu-toggle');
  var menu=document.getElementById('main-menu');
  if(menuButton&&menu){menuButton.addEventListener('click',function(){var open=menu.classList.toggle('is-open');menuButton.setAttribute('aria-expanded',String(open));});}
  var allCategories=document.querySelector('.lt-all-categories');
  var allWrap=document.querySelector('.lt-all-wrap');
  if(allCategories&&allWrap){
    allCategories.addEventListener('click',function(event){event.stopPropagation();var open=allWrap.classList.toggle('is-open');allCategories.setAttribute('aria-expanded',String(open));});
    document.addEventListener('click',function(event){if(!allWrap.contains(event.target)){allWrap.classList.remove('is-open');allCategories.setAttribute('aria-expanded','false');}});
    document.addEventListener('keydown',function(event){if(event.key==='Escape'){allWrap.classList.remove('is-open');allCategories.setAttribute('aria-expanded','false');allCategories.focus();}});
  }

  document.querySelectorAll('[data-tab-button]').forEach(function(button){button.addEventListener('click',function(){var target=button.getAttribute('data-tab-button');document.querySelectorAll('[data-tab-button]').forEach(function(item){var active=item===button;item.classList.toggle('is-active',active);item.setAttribute('aria-selected',String(active));});document.querySelectorAll('[data-tab-panel]').forEach(function(panel){panel.hidden=panel.getAttribute('data-tab-panel')!==target;});});});

  var carousel=document.querySelector('[data-carousel]');
  if(!carousel)return;
  var slides=Array.prototype.slice.call(carousel.querySelectorAll('[data-slide]'));var index=0;var paused=false;var timer;
  function show(next){if(!slides.length)return;index=(next+slides.length)%slides.length;slides.forEach(function(slide,i){var active=i===index;slide.classList.toggle('is-active',active);slide.setAttribute('aria-hidden',String(!active));});}
  function start(){clearInterval(timer);timer=setInterval(function(){if(!paused)show(index+1);},6000);}
  carousel.querySelector('[data-prev]').addEventListener('click',function(){show(index-1);});
  carousel.querySelector('[data-next]').addEventListener('click',function(){show(index+1);});
  carousel.querySelector('[data-pause]').addEventListener('click',function(){paused=!paused;this.innerHTML=paused?'<i class="fa fa-play"></i>':'<i class="fa fa-pause"></i>';this.setAttribute('aria-label',paused?'Play carousel':'Pause carousel');});
  carousel.addEventListener('keydown',function(event){if(event.key==='ArrowLeft')show(index-1);if(event.key==='ArrowRight')show(index+1);if(event.key===' '){event.preventDefault();carousel.querySelector('[data-pause]').click();}});
  start();
}());
