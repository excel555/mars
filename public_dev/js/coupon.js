(function () {
    var id = Tools._GET().id || 0;

    if(!id){
        Tools.showAlert('缺少参数，请重新扫码');
        $('#rby-detail').hide();
        return;
    }

    var title = '世界那么大,我们吃光它!';
    var desc = '物美价廉不排队,一起吃的更划算!';
    var pic = location.protocol + '//' + location.host +'/public/img/redpacket.png';
    var url = location.href;

    //获取优惠券信息列表
    function get_detail() {
        Ajax.custom({
            url: config.API_COUPON,
            data:{
                qrcard_id:id
            },
            showLoading: true
        }, function(response) {
            document.title = response.title;
            $('#money').html('<small>¥</small>'+Tools.rbyFormatCurrency(response.coupon_money));
            $('#coupon_date').text('有效期:'+response.coupon_begin_date+'-'+response.coupon_end_date);
            $('#coupon_remarks').text(response.coupon_remarks);
            $('#banner_img').attr('src',response.banner_img);

            var d2=new Date();//取今天的日期
            var d1 = new Date(Date.parse(response.coupon_end_date));
            if(response.status == 0){
                $('.coupon-con').html('<p style="width: 100%;text-align: center;line-height: 50px;">主人，来晚了一步 活动已经结束了...</p>');
            }
            pic = response.share_img;
            title = response.share_title;
            desc = response.share_content;

            if(response.get_type == 2){
                $("#share_btn").hide();
            }
            if(Tools.returnUserAgent() == "fruitday-app" || Tools.returnUserAgent() == "fruitday-web"){
                $("#used_btn").attr('href',"fruitday://CityboxScan");
            }

            Ajax.render("#user-content", "user-content-tmpl", response);
            Ajax.render("#user-rule", "user-rule-tmpl", response);
            if(response.error_tips){
                Tools.showToast(response.error_tips,3000);
            }else {
                Tools.showToast('领取成功',3000);
            }

            if(Tools.isWeChatBrowser()){
                var t = {
                    title: title,
                    desc: desc,
                    imgUrl: pic
                };
                var e = {
                    title: title,
                    imgUrl: pic
                };
                common.share.override(e, t, url);
            }
        },function (e) {
            Tools.showAlert(e.message);
        });
    }

    $('.share-btn').click(function () {
        if(Tools.isAlipayBrowser()){
            alipay_share();
        }else{
            $('.share-bg').show();
        }
    });
    $('.share-bg').click(function () {
        $('.share-bg').hide();
    });

    if(Tools.returnUserAgent() == "fruitday-app" || Tools.returnUserAgent() == "fruitday-web"){
        authFruitday(get_detail);
    }else{
        common.checkLoginStatus(function () {//入口
            if(Tools.isWeChatBrowser() || Tools.isAlipayBrowser()){
                get_detail();
            }
        });
    }



    function alipay_share() {
        if ((Ali.alipayVersion).slice(0, 3) >= 8.1) {
            Ali.share({
                //渠道名称。支持以下几种：Weibo/LaiwangContacts/LaiwangTimeline/Weixin/WeixinTimeLine/SMS/CopyLink
                'channels': [
                    {
                        name: 'ALPContact',   //支付宝联系人,9.0版本
                        param: {   //请注意，支付宝联系人仅支持一下参数
                            contentType: 'url',    //必选参数,目前支持支持"text","image","url"格式
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic,   //必选参数,缩略图url，发送前预览使用,
                            imageUrl: pic, //图片url
                            url: url,   //必选参数，卡片跳转连接
                            title: title,    //必选参数,分享标题
                            memo: ""   //透传参数,分享成功后，在联系人界面的通知提示。
                        }
                    }, {
                        name: 'ALPTimeLine', //支付宝生活圈
                        param: {
                            contentType: 'url',    //必选参数,目前只支持"url"格式
                            title: title,   //标题
                            url: url,  //url
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic //icon
                        }
                    }, {
                        name: 'Weixin', //微信
                        param: {
                            contentType: 'url',    //必选参数,目前只支持"url"格式
                            title: title,   //标题
                            url: url,  //url
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic //icon
                        }
                    }, {
                        name: 'WeixinTimeLine', //朋友圈
                        param: {
                            contentType: 'url',    //必选参数,目前只支持"url"格式
                            title: title,   //标题
                            url: url,  //url
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic //icon
                        }
                    }
                    ]
            }, function (result) {
                Tools.alert('分享成功');
                Tools.alert(result);
            });
        } else {
            alert('请在钱包8.1以上版本运行');
        }
    }

})()
