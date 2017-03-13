if(!localStorage.getItem('token')){
	location.href='/module/system/user/login.html';
}

document.onreadystatechange=function(){};//主页不启动加载进度条
if(parent!=window){
	$(document.body).hide();
	parent.lib.popup.result({bool:false,text:"页面不存在"});
}
$('#page').on('_ready',function(){//#page _ready事件
	
	window.onresize=function(){//注册resize事件
		$('.frame-main').height($(window).height()-$('header').height());
	};
	window.onresize();//触发resize事件
	
	var loadbar=$('.loadbar');//加载进度条
	var iframe=$('iframe');
	var timeStamp=0;
	var time=500;
	$(document.body).on('loading',function(e){//注册loading事件
		var winWidth=$(window).width()
		timeStamp=e.timeStamp;
		loadbar.stop().css({display:"block",width:0,opacity:1}).animate({
			width:winWidth-250
		},time);
		iframe.css('opacity',0.35);
		$(this).one('loadingend',function(e){//注册loadingend事件，只执行一次
			if(e.timeStamp-timeStamp<time){
				setTimeout(function(){
					loadbar.stop().animate({
						width:winWidth
					},250,'swing',function(){
						setTimeout(function(){
							loadbar.fadeOut(120);
						},50);
					});
					iframe.css('opacity',1);
				},time-(e.timeStamp-timeStamp));
			}else{
				loadbar.stop().animate({
					width:winWidth
				},250,'swing',function(){
					setTimeout(function(){
						loadbar.fadeOut(120);
					},50);
				});
				iframe.css('opacity',1);
			}
		});
	});
	
	$('aside').on('click','li',function(){//三级菜单高亮
		$('aside li.active').removeClass('active');
		$(this).addClass('active');
	}).on('click','a',function(){
		if(iframe[0].contentWindow.location.href==this.href){//处理iframe的hash链接与当前href相同不重新加载问题
			iframe[0].contentWindow.location.reload();
		}
	}).on('click','.menu-category-title',function(e){//展示子菜单
		var $this=$(this);
		$this.parent().addClass('active').children('ul').slideToggle(200);
		$this.parent().siblings().removeClass('active').children('ul').slideUp(200);
	});
	
	$('#logout').on('click',function(){//退出事件
		lib.popup.result({
			bool: true,
			text:'退出成功',
			define:function(){
				localStorage.removeItem('token');
				location.href='/module/system/user/login.html';
			}
		});
	});
	//F5刷新事件
	$(window).on('keydown',function(e){
		if(e.keyCode==116){
			iframe[0].contentWindow.location.reload();
			e.preventDefault();
		}
	});
	//ie9修正
	if(window.ie9){
		$('.swiper-slide').hide();
		$('.nav-main li').on('click',function(){
			var $this=$(this)
			$this.addClass('active').siblings().removeClass('active');
			$('.swiper-slide').eq($this.index()).show().siblings().hide();
		}).first().trigger('click');
		return;
	}
	//二级菜单使用swiper切换
	var swiper = new Swiper($('aside .swiper-container')[0],{
		loop: true,
		initialSlide : 0,
		spaceBetween: 0,
		simulateTouch:false
	});
	$('.nav-main li').on('click',function(){//顶部菜单事件
		var $this=$(this)
		$this.addClass('active').siblings().removeClass('active');
		swiper.slideTo($this.index()+1);
	}).first().addClass('active');
	
	
});
	