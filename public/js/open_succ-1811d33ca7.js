function ajax_wx_pay_status(e){1!=flag&&Ajax.custom({url:config.API_FD_BOX_STATUS,data:{device_id:deviceId},type:"GET",showLoading:!1},function(t){console.log(t),"stock"==t.status?$("#rby-loading1").show():"pay_succ"==t.status?(clearInterval(e),location.href="buy_succ.html?order_name="+t.order_name+"&deviceId="+deviceId):"free"==t.status&&(location.href="index.html"),flag=!1},function(e){flag=!1})}var deviceId=Cookie.get("deviceId"),timer,flag=!1;!function(){function e(){var e={title:"",url:"#"};window.history.pushState(e,"","#")}document.addEventListener("resume",function(e){AlipayJSBridge.call("closeWebview")}),document.addEventListener("pause",function(e){AlipayJSBridge.call("closeWebview")},!1),e(),window.addEventListener("popstate",function(t){e();var i=navigator.userAgent.toLowerCase();"micromessenger"==i.match(/MicroMessenger/i)?WeixinJSBridge.call("closeWindow"):i.indexOf("alipay")!=-1?AlipayJSBridge.call("closeWebview"):window.close()},!1),common.checkLoginStatus(function(){timer=setInterval(function(){ajax_wx_pay_status(timer)},3e3)})}();