;
(function($) {
    var type = 'qrcode';
    var doing = false;
    
    
    $("body").on('click', '.switchBtn', function(event) {
        event.preventDefault();
        if(type == 'qrcode'){
            type = 'pwd';
            $("#loginform p").show()
            $("#loginform .qrcode-item").hide()
            $(this).show()

        }else{
            $("#loginform p").hide()
            $("#loginform .qrcode-item").show()
            type = 'qrcode';
        }

        $(".forgetmenot").show()
        $(".submit").show()
        
    });

    
    $("body").on('click', '#wp-submit', function(event) {
        if (type == 'qrcode') {
            event.preventDefault();
            let vcode = $("#vcode").val()
            let ticket = $("#qrcode-key").val()
            
            let redirect_to = $("input[name='redirect_to']").val()
            let data = { vcode: vcode, ticket: ticket, security: jsobj.nonce }
            if( doing ) return false; 
			doing = true
            let req = wp.ajax.post('qrcode_login_action', data)
            req.done(function(res) {
                console.log('logined', res);
                doing = false;
                window.location.href = redirect_to
            })
            req.fail(function(err){
                console.log('err', err );
                if(window.confirm( '登陆失败，请刷新重新登陆' )){
                    window.location.reload()
                }else{
                    doing = false;
                }
            })
            return false;
        }
    });


})(jQuery);