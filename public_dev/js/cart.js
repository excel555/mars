(function() {
    var container = $('#car-page');
    var deviceId = Tools._GET().deviceId || '';



    //enter事件
    (function () {
        var instance = {};
        bindEvents();
        function bindEvents() {
            $("#input_scan").keydown(function (events) {
                if (events.which == 13) {
                    console.log($(this).val());
                    add2cart($(this).val());
                }
            });
        };
        // return instance;
    })();
    
    $(".btn-submit").click(function (e) {
        add2cart($('#input_scan').val());
    });

    container.on('click', '.deleteCartpro', function(e) {
        var id = $(this).attr('product_id');
        if(parseInt(id) >0){
            Ajax.custom({
                url: config.API_DEL_CART,
                data: {
                    product_id: id,
                    device_id:deviceId
                },
                showLoading: true
            }, function(response) {
                render_cart(response);
            }, function(data) {
                // alert(data);
            });
        }
    });
    function add2cart(code) {
        if(code == ""){
            alert("条形码不能为空");
            return;
        }
        Ajax.custom({
            url: config.API_ADD_CART,
            data: {
                serial_code: code,
                device_id:deviceId
            },
            showLoading: true
        }, function(response) {
            render_cart(response);
        }, function(data) {
            // alert(data);
        });
    }

    function init_cart() {
        Ajax.custom({
            url: config.API_INIT_CART,
            data: {
                device_id:deviceId
            },
            showLoading: true
        }, function(response) {
            render_cart(response);
        }, function(data) {
            alert(data);
        });
    }

    
    function render_cart(data) {
        console.log(data);
        Ajax.render("#goods-container", "goods-container-tmpl", data);
        $("#input_scan").val('');
        $("#input_scan").focus();
        $("#qty_goods").text(data.pay.qty+"件");
        $("#all-order").text(data.pay.total);
    }

    
    $(".go-pay").click(function () {
        $(this).text("正在结算...");
        Tools.showLoading();
        Ajax.custom({
            url: config.API_PAY_CART,
            data: {
                device_id:deviceId
            },
            type:'POST',
            showLoading: true
        }, function(response) {
            Tools.hideLoading();
            alert('结算成功');
           location.href='scan_login.html?deviceId=68805328909';
        }, function(data) {
            alert(data.message);
            // window.location.href='order.html?orderId='+data.order_name;
        });
    });

    common.checkLoginStatus(function() {
        $("#name_view").text(decodeURI(Tools._GET().name || ''));
        $("#device_view").text(decodeURI(Tools._GET().device || ''));
        init_cart();
    })

})();
setInterval("fouse_input()",500);

function fouse_input() {
    $("#input_scan").focus();
}
