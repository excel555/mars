var deviceId = Tools._GET().deviceId || '';
var d = '';
var  t = 'pc';
(function() {
    var container = $('#car-page');





    var scan_succ = Tools._GET().scan_succ || '';

    if(scan_succ == 'succ'){
        $(".login_div").show();
        t = 'wap';
        Ajax.custom({
            url: config.API_SCAN_STATUS,
            data: {
                device_id:deviceId,
                type:"scan"
            },
            type:'POST',
            showLoading: true
        }, function(response) {
            if(response.status =="scan_succ"){
                $(".scan_succ").show();
            }
        }, function(data) {
            // window.location.href='order.html?orderId='+data.order_name;
        });

    }else{
        $(".scan_div").show();
        Ajax.custom({
            url: config.API_SCAN_STATUS,
            data: {
                device_id:deviceId,
                type:"init"
            },
            type:'POST',
            showLoading: true
        }, function(response) {
            d = response.device.name+"["+response.device.equipment_id+"]"
            $("#device_view").text(d);
            $("#qr").attr('src',response.device.qr);
        }, function(data) {
            // window.location.href='order.html?orderId='+data.order_name;
        });

    }


    $("#c_login").click(function () {
        Ajax.custom({
            url: config.API_SCAN_STATUS,
            data: {
                device_id:deviceId,
                type:'login_c'
            },
            type:'POST',
            showLoading: true
        }, function(response) {
            alert('登录成功');
            if(response.status =="login_succ"){
                location.href='orders.html?status=-1';
            }
        }, function(data) {
        });
    });
})();
setInterval("fouse_input()",2000);

function fouse_input() {
    if(t== "pc"){
        Ajax.custom({
            url: config.API_SCAN_STATUS,
            data: {
                device_id:deviceId
            },
            type:'POST',
            showLoading: true
        }, function(response) {
            if(response.status =="login_succ" ){

                Cookie.set("UserSN", response.id, null);
                location.href='cart.html?deviceId='+deviceId+'&name='+response.name+"&device="+d;
            }else if(response.status =="scan_succ"){
                $(".scan_succ").show();
            }
        }, function(data) {
            // window.location.href='order.html?orderId='+data.order_name;
        });
    }
}
