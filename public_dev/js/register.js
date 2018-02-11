(function() {
    var from = Tools._GET().from,
        btnSend = $('.btn-send'),
        phone = $('#phone'),
        code = $('#code'),
        oriCode, //记录验证码
        phoneNum; //记录手机号码
    var redirect_url = Tools._GET().redirect_url || '';

    var mobile = Cookie.get("mobile");
    if(mobile != null && mobile.length == 11){
        $("#bind-form").hide();
        $(".container").html('<div class="">您已绑定手机号：'+mobile+'，自动跳转到个人中心</div>');
        location.href='person.html'
    }
    //点击发送验证码
    btnSend.on('click', function(e) {
        e.preventDefault();
        if (phone.val().isEmpty()) {
            Tools.showToast('手机号不能为空');
            return;
        }
        if (!phone.val().isPhone()) {
            Tools.showToast('手机号格式不正确！');
            return;
        }
        if (!btnSend.hasClass('disabled')) {
            sendSms();
        }
    });
    //提交绑定
    $('#bind-form').submit(function(e) {
        e.preventDefault();

        if (phone.val().isEmpty()) {
            Tools.showToast('手机号不能为空');
            return;
        }
        if (!phone.val().isPhone()) {
            Tools.showToast('手机号格式不正确');
            return;
        }
        if (code.val().isEmpty()) {
            Tools.showToast('验证码不能为空');
            return;
        }
        Ajax.custom({
            url: config.API_BIND,
            type:'POST',
            data: {
                mobile: phone.val(),
                vcode:code.val()
            },
        }, function(data) {
            history.replaceState(null,null,'person.html');//修改history.back
            if(redirect_url != ''){

                location.href= decodeURIComponent(redirect_url);
            }else{
                location.href="../public/person.html"
            }
            Cookie.set("mobile", phone.val(), null);
        }, function(data) {
            Tools.showAlert(data.message || '服务器异常');
        });

    });

    //发送短信验证码
    function sendSms() {
        var duration = config.smsDuration, //重发计时
            inte; //计时器
        btnSend.addClass('disabled').html('<span>发送中···</span>');
        Ajax.custom({
            url: config.API_SEND_VECODE,
            data: {
                mobile: phone.val(),
                type:'register'
            },
            showLoading: true
        }, function(data) {
            //计时重发
            inte = setInterval(function() {
                duration--;
                if (duration == 0) {
                    clearInterval(inte);
                    btnSend.removeClass('disabled').html('获取验证码');
                    return;
                }
                btnSend.html('<span>重发(' + duration + ')</span>');
            }, 1000);
        }, function(data) {
            Tools.showAlert(data.message || '服务器异常');
            btnSend.removeClass('disabled').html('获取验证码');
        });
    }
})()
