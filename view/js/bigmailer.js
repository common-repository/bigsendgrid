

(function($){
    $('input[name=sg_api]').on('change', function(){
        if($('input[name=sg_api]:checked').val() == "SMTP"){
            $('#bigmailer_port').show();
        } else {
            $('#bigmailer_port').hide();
        }
    });

})(jQuery);
