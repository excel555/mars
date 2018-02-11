(function() {

    var phone = $('#phone'),
        name = $('#name'),
        open_id = $('#open_id'),
        platform_id = $('#platform_id'),
        email = $('#email');

    if (!Tools.isWeChatBrowser()) {
        // Tools.showAlert('请在微信里面打开');
        // return;
    }
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
        if (email.val().isEmpty()) {
            Tools.showToast('邮箱不能为空');
            return;
        }
        if (name.val().isEmpty()) {
            Tools.showToast('姓名-公司不能为空');
            return;
        }
        if (open_id.val().isEmpty()) {
            Tools.showToast('微信id不能为空');
            return;
        }
        if (platform_id.val() == '0') {
            Tools.showToast('请选择商户');
            return;
        }

        Ajax.custom({
            url: config.API_WARN_MSG,
            type:'POST',
            data: {
                mobile: phone.val(),
                email:email.val(),
                wechat_id:open_id.val(),
                name:name.val(),
                platform_id:platform_id.val()
            },
        }, function(data) {
            Tools.showAlert('提交成功，1.请告知对应的商户管理员;2.长按图片识别二维码并关注公众号');
        }, function(data) {
            Tools.showAlert(data.message || '服务器异常');
        });

    });
    function info() {
        Ajax.custom({
            url: config.API_MEMBER_INFO,
            showLoading: true
        }, function(response) {
            $('#rby-detail').show();
            $('.empty-page-tip').hide();
            $('#open_id').val(response.open_id);
            var str = ' <option value="0">--请选择商户--</option>';
            for(i=0;i< response.platforms.length;i++){
                str +='<option value="'+response.platforms[i]['id']+'">'+response.platforms[i]['name']+'</option>';
            }
            $("#platform_id").html(str);
            console.log(str);
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    $('body').on('click', '.empty-page-tip', function() {
        location.reload();
    });
    common.checkLoginStatus(function() {
        info();
    })
})()


