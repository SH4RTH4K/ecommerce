<script>
document.addEventListener('DOMContentLoaded',function(){
    var form=document.getElementById(@json($formId)),selectAll=document.getElementById(@json($selectAllId)),button=document.getElementById(@json($buttonId)),counter=document.getElementById(@json($counterId));
    if(!form||!selectAll||!button||!counter)return;
    function boxes(){return Array.prototype.slice.call(document.querySelectorAll('#'+form.id+' .bulk-row-checkbox, .bulk-row-checkbox[form="'+form.id+'"]'));}
    function syncUniform(targets){if(window.jQuery&&jQuery.uniform&&jQuery.uniform.update)jQuery.uniform.update(targets);}
    function update(){var all=boxes(),checked=all.filter(function(box){return box.checked;});counter.textContent=checked.length+' selected';button.disabled=checked.length===0;selectAll.checked=all.length>0&&checked.length===all.length;selectAll.indeterminate=checked.length>0&&checked.length<all.length;syncUniform(all.concat(selectAll));}
    selectAll.addEventListener('change',function(){boxes().forEach(function(box){box.checked=selectAll.checked;});update();});
    document.addEventListener('change',function(event){if(event.target.classList.contains('bulk-row-checkbox')&&boxes().indexOf(event.target)!==-1)update();});
    form.addEventListener('submit',function(event){var count=boxes().filter(function(box){return box.checked;}).length;if(!count||!confirm('Delete '+count+' selected '+@json($itemLabel)+'? Referenced records will be skipped.'))event.preventDefault();});
    update();
});
</script>
