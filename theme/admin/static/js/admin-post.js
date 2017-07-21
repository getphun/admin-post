$(function(){
    
    $('#field-status').change(function(){
        var ePub = $('#field-published');
        if($(this).val() == 3){
            ePub.parent().parent().show();
        }else{
            ePub.parent().parent().hide();
        }
        ePub.focus();
    });
    $('#field-status').change();
    
    $('#field-canal').change(function(){
        var $this = $(this);
        var val = parseInt($this.val());
        
        if(!val)
            return $('.field-category').prop('disabled', false);
        $('.field-category:not([data-canal='+val+'])').prop('disabled', true);
        $('.field-category[data-canal='+val+']').prop('disabled', false);
    });
    $('#field-canal').change();
});