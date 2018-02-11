(function() {
	AlipayJSBridge.call('setTitle', {
	  title: '发现魔盒',
	});
	
    Tools.showLoading();
    var admin = Tools._GET().admin || -1;
    var is_new = Tools._GET().is_new || -1;
    var ref = Tools._GET().ref || 'deliver';
    var deviceId = Tools._GET().deviceId || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
    }
    
    function rec_scan_succ(qrCode){
        Ajax.custom({
            url: config.API_REC_ADMIN,
            data:{
                admin: admin,
                qrcode:qrCode,
                is_new:is_new
            },
            type: 'POST',
            showLoading: true
        }, function(response) {
            deviceId =  response.device_id;
            Cookie.set("deviceId", deviceId, null);
            if(admin != -1){
                if(response.status == 'redirect') {
                    if(ref == 'deliver'){
                        location.href = 'deliver.html?admin=1&deviceId=' + deviceId;
                    }else {
                        location.href = 'stock.html?deviceId=' + deviceId;
                    }
                }else{
                    AlipayJSBridge.call('closeWebview');
                }
            }else{
                if(response['status'] == 'error' && response['url']){
                    location.href = response['url'];
                    return false;
                }
                Tools.showToast("开门中....");
                // AlipayJSBridge.call('closeWebview');
                history.replaceState(null,null,'index.html?deviceId='+deviceId);//修改history.back
                if(response.status == 'redirect_p') {
                    location.href=response.redirect_url;
                }else{
                    location.href='index.html?from=open&deviceId='+deviceId;    
                }
            }
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        });

    }
    function qr(){
        common.aliScan(rec_scan_succ);
        Tools.hideLoading();
    }
    
	//获取当前盒子以及其他盒子地址
    function get_box_address(){
        Ajax.custom({
            url: config.API_BUY_ADDRESS,
            data:{
                type:Tools.returnUserAgent()
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
        	if (response.is_open == 1){
        		$('#first_name').html(response.first['name']);
        		if (response.first['address'].length > 20){
        			$('#first_address').html(response.first['address'].substring(0,18) + '...');
        		} else {
        			$('#first_address').html(response.first['address']);
        		}
        		
        		$(response.box).each(function(i,val){
        			var append_result = '<li class="item" id = "'+val.equipment_id+'">';
        			if (i == 0){
        				append_result += '<span class="active sel"><img src="img/icon5.png" alt="" /></span>';
        			} else {
        				append_result += '<span class="sel"><img src="img/icon5.png" alt="" /></span>';
        			}
        			
        			append_result += '<div box_id="'+val['equipment_id']+'" class="infor click_div">';
        			append_result += '<span class="name">'+val['name']+'</span>';
        			append_result += '<p>'+val['address']+'</p>';
        			append_result += '</div>';
        			append_result += '</li>';
        			$('.scroll ul').append(append_result);
        		});
        		//获取第一个设备的相关信息
        		var first_id = response.first['equipment_id'];
        		getDetail(first_id);
        	} else {//没有开过门
        		$('.container').addClass('hide');
        		$('.container-empty').removeClass('hide');
        	}
        	Tools.hideLoading();
            console.log(response);
            
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }
    common.checkLoginStatus(function() {
        get_box_address();
    })
    //get_box_address();
    
    $('body').on('click', '.scan-con', function() {
        qr();//扫码
    });
    
    
})();

function getDetail(id){ 
	Ajax.custom({
        url: config.API_DEVICE_INFO,
        data:{
            type:Tools.returnUserAgent(),
            device_id:id
        },
        type: 'GET',
        showLoading: true
    }, function(response) {
    	
    	//清空之前的内容
    	$('#active_banner').html('');
    	$('#active_icon').html('');
    	$('#swiper-wrapper').html('<div class="swiper-slide"><span>优惠活动</span></div>');
    	$('.home-swiper').html('<div class="swiper-wrap" id="swiper0"><div class="title">优惠活动</div><div class="item-wrap activity" id="active_icon"></div></div>');
    	if (response.equipment){
    		$('#first_name').html(response.equipment.name);
    		if (response.equipment.address.length > 20){
    			$('#first_address').html(response.equipment.address.substring(0,18) + '...');
    		} else {
    			$('#first_address').html(response.equipment.address);
    		}
    		$('.scroll ul').children('li').each(function(i,val){
    			if (response.equipment.equipment_id == $(this).attr('id')){
    				$(this).find('.sel').addClass('active');
    			} else {
    				$(this).find('.sel').removeClass('active');
    			}
    		})
    	}
    	if (response.banner){
    		$(response.banner).each(function(i,val){
    			var banner = '<div class="swiper-slide"><img src="'+val.img_url+'" /></div>';
    			$('#active_banner').append(banner);
    		});
    	}
    	if (response.active_icon){
    		$(response.active_icon).each(function(i,val){
    			var icon = '<div class="item">';
    			icon += '<img class="active-img" src="'+val.img_url+'" alt="">';
                icon += '<dl class="">';
                icon += '<dt>'+val.remarks+'</dt>';
                icon += '<dd>'+val.start_time+'-'+val.end_time+'</dd>';
                icon += '</dl>';
                icon += '</div>';
                $('#active_icon').append(icon);
    		});
    		if ($.isEmptyObject(response.active_icon)){
    			$('#swiper0').remove();
    			$('#swiper-wrapper').html('');
    		}
    	} 
    	
    	if (response.classes){
    		$(response.classes).each(function(i,val){
    			$('#swiper-wrapper').append('<div class="swiper-slide"><span>'+val.name+'</span></div>');
    			var num = i+1;
    			var result = '<div class="swiper-wrap" id="swiper'+num+'">';
                result += '<div class="title">'+val.name+'</div>';
                result += '<div class="item-wrap goods" id = "goods_'+val.id+'">';
                result += '</div>';
                result += '</div>';
                if ($('.swiper-wrap').length > 0){
                	$(result).insertAfter($('#swiper'+i));
                } else {
                	$('.home-swiper').append(result);
                }
    			
    		});
    		//alert($('#swiper-wrapper').find('.swiper-slide').last().html());
    		$('#swiper-wrapper').find('.swiper-slide').last().addClass('isLast');
    	}
    	
    	if (response.products){
    		$(response.products).each(function(i,val){
    			var result = '<div class="item">';
                result += '<img class="good-img" src="'+val.img_url+'" alt="">';  
                result += '<dl>'; 
                result += '<dt>'+val.product_name+'</dt>';  
                result += '<dd>'+val.volume+'</dd>'; 
                result += '<div>';
                result += '<span class="num pull-right">可售 '+val.stock+'</span>';
                result += '<small>￥</small><big>'+val.price+'</big>';
                result += '</div>'; 
                result += '</dl>';  
                result += '</div>';
                $('#goods_'+val.parent_class_id).append(result);
    			//alert(i);
    		});
    	}
    	
    	
    	var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height()),
        index = 0;

	    body.find('.layout').height(layout_Height);
	
	
	    if( $('.swiper-banner').length ){
	        $('.swiper-banner').height($(window).width()* 150/355);
	        /*if (is_swiper){ 
	        	swiper.destroy();
	        }*/
	        var swiper = new Swiper('.swiper-banner', {
	            pagination: '.swiper-pagination',
	            paginationClickable: true,
	            loop: true,
	            autoplay: 5000,
	            autoplayDisableOnInteraction: false,
	            observer:true,//修改swiper自己或子元素时，自动初始化swiper 
				observeParents:false,//修改swiper的父元素时，自动初始化swiper 
				onSlideChangeEnd: function(swiper){ 
				　　　swiper.update();
				}

	        });
	    };
	
	
	
	    if( $('.menu-swiper').length ){
	        var menuSwiper = new Swiper('.menu-swiper', {
	            slidesPerView: 3.8,
	            watchSlidesProgress : true, 
	            onInit: function(swiper){
	                $('.menu-swiper .swiper-slide').eq(index).addClass('active');
	            },
	            onTap: function(swiper){
	                index = swiper.clickedIndex;
	                $('.menu-swiper .active').removeClass('active');
	                $('.menu-swiper .swiper-slide').eq(index).addClass('active');
	                swiper.slideTo(index);
	
	                var t = $('.home-swiper .swiper-wrap').eq(index).data('top'); 
	                    $(window).scrollTop(t);
	                
	            }
	        }); 
	
	        scrollEffect(menuSwiper);
	    };
	
	    $('#toTop').on('click', function(){
	        $(window).scrollTop(0);
	        menuSwiper.slideTo(0);
	        $('.menu-swiper .active').removeClass('active');
	        $('.menu-swiper .swiper-slide').eq(0).addClass('active');
	    })
        console.log(response);
        
    }, function(e) {
        Tools.showAlert(e.message || '服务器异常');
    })
}






