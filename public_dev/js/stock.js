(function () {
    var deviceId = Tools._GET().d || '',
        container = $('#rby-list'), //
        cartPage = new SecondPage({ //迷你购物车
            targetPage: $('#mini-cart-page'),
            beforeOpen: function () {
                $('.btn-mini-cart').css('visibility', 'hidden');
            },
            afterClose: function () {
                $('.btn-mini-cart').css('visibility', 'visible');
            }
        }),
        detailPage = new SecondPage('#detail-page'); //商品详情

    var deviceId = Tools._GET().deviceId || '';

    if(!deviceId)
    {
        alert('缺少参数，请返回刷新重试');
        return;
    }




    function getList() {
        Ajax.custom({
            url: config.API_STOCK_EQ,
            data: {
                device_id: deviceId
            },
            showLoading: true
        }, function (response) {
            after_cart_init(response);
        }, function (data) {
            container.html('<div class="nodata">' + ((data && data.message) || '服务器异常。') + '</div>');
        })
    }

    function after_cart_init(response) {
        var data = response;
        if (data.length == 0) {
            container.html('<div class="nodata">没有数据，扫码添加商品。</div>');
            return;
        }

        Ajax.render('#rby-list', 'rby-list-tmpl', data);

        //2改变商品总数，3改变按钮上的总价文字，4显示底部按钮
        var stat = calc(response);
        afterChange(stat);
        $('.footer-goods').show();

        //计算详情弹窗的高度
        $('#detail-page').css('height', Tools.getDocument().height);
        //计算商品列表的最小高度
        $('.goods-list').css('min-height', Tools.getDocument().height - $('.footer-goods').height());
        if ($('.goods-group').height() >= Tools.getDocument().height / 1.8) {
            $('.logo').css('position', 'fixed');
            $('.logo').css('bottom', '40px');
            $('.logo').css('left', '0');
            $('.logo').css('right', '0');
            $('.logo').css('margin', 'auto');
        }
    }

    common.checkLoginStatus(function () { //入口
        getList();

    });


    //计算商品总价和总数
    function calc(data) {
        var total = 0,
            count = 0;

        for (var j in data.goods) {
            var d = data.goods[j];
            total += data.goods[j].price * data.goods[j].qty;
            count += parseInt(d.qty);
        }

        return {
            total: Tools.rbyFormatCurrency(total),
            quantity: count
        };
    }

    //列表中的更改数量按钮
    container.on('click', '.btn', function (e) {
        e.preventDefault();

        // if ($(this).hasClass('disabled')) {
        //     return;
        // }

        onBtnClick($(this), function (par, num) {
            changeQty(par.attr('key'), num);
        })
    });

    //扫码
    container.on('click', '.logo', function (e) {
        scan(e);
    });

    $('.btn-mini-cart1').click(function (e) {
        scan(e)
    });

    function scan(e) {
        e.preventDefault();
        if (Tools.isWeChatBrowser()) {
            common.wx_scanQRCode(scan_succ);
        }
        else if (Tools.isAlipayBrowser()) {
            common.aliScan(scan_succ);
        }
    }
    function scan_succ(code) {
        Tools.alert(code);
       add_deliver_cart(0, 1, 1,code);
    }


    // 更改数量成功后需要，1改变数量、2根据数量是否显示减按钮
    function changeQty(key, num) {
        $('.goods-list .goods-qty').each(function () {
            if ($(this).attr('key') == key) {
                $(this).find('.num-ipt').text(num);
            }
        })
    }


    //提交确认库存
    $('.btn-add-cart').click(function (e) {
        if ($(this).hasClass('disabled')) {
            return;
        }
        Ajax.custom({
            url: config.API_CONFIRE_STOCK,
            data: {
                device_id: deviceId
            },
            type: 'POST'
        }, function (response) {
            alert('提交成功，共'+response+"件商品");
            if(Tools.isAlipayBrowser()){
                AlipayJSBridge.call('closeWebview');
            }else{
                location.href='person.html'
            }
        }, function (data) {
            Tools.showToast((data && data.message) || '服务器异常');
        })


    });


    //更改数量成功后需要，2改变商品总数、3改变按钮上的总价文字
    //更改数量成功后需要，2改变商品总数、3改变按钮上的总价文字
    function afterChange(data) {
        if (parseFloat(data.total) == 0) {
            $('.btn-add-cart').addClass('disabled');
            $('.btn-add-cart').text('0件商品');
            $('.btn-mini-cart').addClass('disabled');
            $('.btn-mini-cart i').text(data.quantity);
            $('.btn-cart').addClass('disabled');
            $('.btn-cart i').text(data.quantity);
        } else {
            $('.btn-add-cart').removeClass('disabled');
            $('.btn-add-cart').text('商品数量正确，提交（共' + Tools.rbyFormatCurrency(data.quantity) + '件）');
            $('.btn-mini-cart').removeClass('disabled');
            $('.btn-mini-cart i').text(data.quantity);
            $('.btn-cart').removeClass('disabled');
            $('.btn-cart i').text(data.quantity);
        }
        $('.mini-cart-count .value').text('￥' + Tools.rbyFormatCurrency(data.total));
    }

    //计算迷你购物车的总价，在打开时显示
    function calcMiniCart() {
        var total = 0;
        for (var i in tempMiniCartData) {
            var d = tempMiniCartData[i];
            total += parseFloat(d.price) * d.qty;
        }

        return $('.mini-cart-count .value').text('￥' + Tools.rbyFormatCurrency(total));
    }

    //点击加减数量按钮
    function onBtnClick(that, fn) {
        var par = that.parent(),
            numDom = par.find('.num-ipt'),
            num = parseInt(numDom.text()),
            store = parseInt(numDom.attr('data-store')),
            limitNum = parseInt(numDom.attr('data-limit')),
            objIdent = numDom.attr('data-objident');

        if (that.hasClass('minus')) {
            num--;
        } else {
            num++;
        }

        if (num <= 0) {
            num = 0;
        }
        if (num > 99) {
            //默认预定购买最大数量99
            num = 99;
        }

       add_deliver_cart(objIdent, num, 0,0);
        if (that.hasClass('disabled')) return;
    }

    function add_deliver_cart(objIdent, num, add,code) {
        Ajax.custom({
            url: config.API_CHANGE_DELIVER_PRO,
            data: {
                serial_code: code,
                product_id:objIdent,
                device_id: deviceId,
                num: num,
                scan: add,
                k:'stock'
            },
            // type: 'POST',
            showLoading: true
        }, function (response) {
            after_cart_init(response);
        }, function (data) {
            Tools.showToast(data.message || '服务器异常');
        })
    }
})()