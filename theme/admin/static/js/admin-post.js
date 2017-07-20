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
    
});