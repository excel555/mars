/**
 * Created by RoseTong on 17/5/17.
 */

(function(){

    var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height()),
        index = 0;

    body.find('.layout').height(layout_Height);


    body.on('click','.recharge-item>li>span>input',function(){
        alert();
        $(this).addClass('cur').siblings().removeClass('cur');
        $('.amount').val('')
    })
    body.on('click','.recharge-item>li',function(){
        $(this).addClass('cur').siblings().removeClass('cur');
        $('.amount').val('')
    })

    body.on('click','.pay-way>li',function(){
        $(this).addClass('cur').siblings().removeClass('cur');
    })

    body.on('click','.CallCashierDesk',function(){
        location.href = 'fruitday://CallCashierDesk?orderName=P170523585731';
    })

    body.on('click', '.pull>img', function(){
        if($(this).hasClass('down')){
            $(this).removeClass().addClass('up');
            $('#order-list>li').slideDown(200)
        }else if($(this).hasClass('up')){
            $(this).removeClass().addClass('down');
            $('#order-list>li').each(function(i){
                if(i>2){
                    $(this).slideUp(200);
                }
            })
        }
    });  

    /*if( $('.swiper-banner').length ){
        $('.swiper-banner').height($(window).width()* 150/355);
        var swiper = new Swiper('.swiper-banner', {
            pagination: '.swiper-pagination',
            paginationClickable: true,
            loop: true,
            autoplay: 5000,
            autoplayDisableOnInteraction: false
        });
    };*/



    /*if( $('.menu-swiper').length ){
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
    };*/

    /*$('#toTop').on('click', function(){
        $(window).scrollTop(0);
        menuSwiper.slideTo(0);
        $('.menu-swiper .active').removeClass('active');
        $('.menu-swiper .swiper-slide').eq(0).addClass('active');
    })*/


    //切换魔盒的动画效果
    body.on('click', '.gps-con .change', function(){
        $('.cover-bg').fadeIn(100);
        $('.address-prop').slideDown(500);
    }).on('click', '.address-prop .cancel, .cover-bg', function(){
        $('.cover-bg').fadeOut(600);
        $('.address-prop').slideUp(500);
    }).on('click', '.click_div',function(){
    	if ($(this).attr('box_id')){
    		Tools.showLoading();
    		getDetail($(this).attr('box_id'));
    		$('.cover-bg').fadeOut(600);
    		$('.address-prop').slideUp(500);
    	}
    });

    

})();

function checkoutCallback(orderName) {
    location.href = 'fruitday://CloseCashierDesk';
}

function scrollEffect(swiper){
    var menuSwiperTop = $('.menu-swiper').offset().top;

    $('.home-swiper .swiper-wrap').each(function(){ 
        var t = $(this).offset().top - 40; 
        $(this).data('top',t); 
    });

    $(window).on('scroll', function(){
        var top=$(this).scrollTop();

        //fix导航
        if(top >= menuSwiperTop){
            $('.menu-swiper').addClass('fix');
            $('body').css({'paddingTop' : '.4rem'});
        }else{
            $('.menu-swiper').removeClass('fix');
            $('body').css({'paddingTop' : '0'});
        }

        //显示至顶
        if(top >= 500){
            $('#toTop').removeClass('hide');
        }else{
            $('#toTop').addClass('hide');
        }

        $('.home-swiper .swiper-wrap').each(function(index){  
			
            if(!$(this).next('.swiper-wrap').offset()){ console.log('isLast')
                if(top >= $(this).offset().top-40  ){
                    $('.menu-swiper .active').removeClass('active');
                    $('.menu-swiper .swiper-slide').eq(index).addClass('active')
                    swiper.slideTo(index);
                }
            }else{
                if( top >= $(this).offset().top-40 && top < $(this).next('.swiper-wrap').offset().top-40){   
                  $('.menu-swiper .active').removeClass('active');
                    $('.menu-swiper .swiper-slide').eq(index).addClass('active')
                    swiper.slideTo(index);   
                }else if( !$(this).prev('.swiper-wrap').offset() ){
                    //menuSwiper.slideTo(0);
                    $('.menu-swiper .active').removeClass('active');
                    $('.menu-swiper .swiper-slide').eq(0).addClass('active');
                }
            }
        })

    })
}



// 扩展里一个fadein, fadeout 效果，edit by rose 2017-09-14
(function ($) {
    $.extend($.fn, {
        fadeIn: function (speed, easing, complete) {
            if (typeof(speed) === 'undefined') speed = 400;
            if (typeof(easing) === 'undefined') {
                easing = 'swing';
            } else if (typeof(easing) === 'function') {
                if (typeof(complete) === 'undefined') complete = easing;
                easing = 'swing';
            }
 
            $(this).css({
                display: 'block',
                opacity: 0
            }).animate({
                opacity: 1
            }, speed, easing, function () {
                // complete callback
                complete && typeof(complete) === 'function' && complete();
            });
 
            return this;
        },
        fadeOut: function (speed, easing, complete) {
            if (typeof(speed) === 'undefined') speed = 400;
            if (typeof(easing) === 'undefined') {
                easing = 'swing';
            } else if (typeof(easing) === 'function') {
                if (typeof(complete) === 'undefined') complete = easing;
                easing = 'swing';
            }
 
            $(this).css({
                opacity: 1
            }).animate({
                opacity: 0
            }, speed, easing, function () {
                $(this).css('display', 'none');
                // complete callback
                complete && typeof(complete) === 'function' && complete();
            });
 
            return this;
        },
        fadeToggle: function (speed, easing, complete) {
            return this.each(function () {
                var el = $(this);
                el[(el.css('opacity') === 0 || el.css('display') === 'none') ? 'fadeIn' : 'fadeOut'](speed, easing, complete)
            })
        }
    })
    
})(Zepto);