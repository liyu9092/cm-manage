var slug={
	'manager.index':'/module/system/user/index.html',
	'role.index':'/module/system/role/index.html',
	'permission.index':'/module/system/power/index.html',
	'log.index':'/module/system/log/index.html',
	'salon.index':'/module/store/shop/index.html',
	'salonAccount.index':'/module/store/account/index.html',
	'merchant.index':'/module/store/merchant/index.html',
	'shop_count.index':'/module/store/single/index.html',
	'shop_count.delegate_list':'/module/store/collection/index.html',
	'shop_count.balance':'/module/store/balance/index.html',
	'pay_manage.index':'/module/finance/payment/index.html',
	'pay_manage.check_list':'/module/finance/approval/index.html#state=2',
	'pay_manage.confirm_list':'/module/finance/define/index.html#state=3',
	'receivables.index':'/module/finance/receivables/index.html',
	'commission.index':'/module/finance/income/index.html',
	'rebate.index':'/module/finance/income/list.html',
	'message.index':'/module/hairstyle/message/index.html',
	'ImageStyle.index':'/module/hairstyle/photo/index.html',
	'user.survey':'/module/user/center/overview.html',
	'user.index':'/module/user/center/index.html',
	'user.company':'/module/user/center/group.html',
	'level.update':'/module/user/center/settings-rating.html',
	'feed.index':'/module/user/operation/index.html',
	'order.index':'/module/transaction/order/index.html',
	'refund.index':'/module/transaction/order/refundment.html',
	'bounty.index':'/module/transaction/reward/index.html',
	'bounty.refundIndex':'/module/transaction/reward/refundment.html',
	'ticket.index':'/module/transaction/other/ticket.html',
	'appointment.index':'/module/transaction/hairstyle/index.html',
	'voucher.list':'/module/marketing/ticket/cashCouponList/index.html',
	'platform.list':'/module/marketing/ticket/platformAct/index.html',
	'coupon.list':'/module/marketing/ticket/ticketAct/index.html',
	'laisee.index':'/module/marketing/ticket/packetAct/index.html',
	'bonus.index':'/module/marketing/ticket/packetList/index.html',
	'starconf.index':'/module/config/app/index.html',
	'salonstar.index':'/module/config/app/vantages.html',
	'scoreconf.index':'/module/config/app/score.html',
	'info.index':'/module/store/info/index.html',
	'messageBox.messageList':'/module/config/message/index.html',
	'messageBox.showDailyMessage':'/module/config/message/day.html',
	'warning.phoneIndex' : '/module/config/anti-fraud/warning_phone.html',
	'warning.openidIndex': '/module/config/anti-fraud/warning_openId.html',
	'warning.deviceIndex': '/module/config/anti-fraud/warning_device.html',
	'blacklist.phoneIndex': '/module/config/anti-fraud/black_list_phone.html',
	'blacklist.deviceIndex': '/module/config/anti-fraud/black_list_deviceId.html',
	'blacklist.openidIndex': '/module/config/anti-fraud/black_list_wechatOpenId.html',
	'comment.index': '/module/user/operation/customer_review.html',
	'requestLog.index': '/module/config/anti-fraud/login_log.html',
	'beautyItem.index':'/module/makeup/item/index.html',
	'beautyItem.indexFashion':'/module/makeup/item/fashion.html',
	'banner.index':'/module/makeup/banner/homeBanner.html',
	'banner.index2':'/module/makeup/banner/itemBanner.html',
	'assistant.index':'/module/makeup/assistant/index.html',
	'artificer.index':'/module/makeup/professional/index.html',
    'book.index':'/module/makeup/order/index.html',
    'powderArticles.articlesList':'/module/makeup/coupon/index.html',
    'powderArticles.presentList':'/module/makeup/order/present_list.html',
    'beautyrefund.index':'/module/makeup/order/refund_list.html',
	'beauty.index':'/module/makeup/introduction.html',
	'calendar.index':'/module/makeup/reservation/index.html',
	'calendar.limit':'/module/makeup/reservation/settings.html',
	'others.index':'/module/makeup/member/index.html'
}
var access={
	data:[],
	map:{},
	init:function(){
		this.data=JSON.parse(localStorage.getItem('access.data'));
		this.map=JSON.parse(localStorage.getItem('access.map'));
	},
	get:function(slug){
		return this.map[slug];
	},
	getData:function(){
		return JSON.parse(localStorage.getItem('access.data'))
	},
	foreach:function(data){
		for(var i=0;i<data.length;i++){
			if(data[i].slug){
				this.map[data[i].slug]=true;
			}
			if(data[i].child instanceof Array){
				access.foreach(data[i].child);
			}
		}
	},
	control:function(dom){
		var self=this;
		$(dom).find('[data-slug]').each(function(){
			var $this=$(this);
			var arr=$this.data('slug').split(',');
			if(self.map[arr[0]]||(arr[1]&&self.map[arr[1]])){
				$this.removeAttr('data-slug');
			}else{
				var form=$this.closest('form[data-role="form"]');
				if($this.is('button')&&form.length==1){
					form.attr('disabled','disabled');
				}
			}
		});
		$(document.body).trigger('access');
	}
}
$(function(){
	if($('.loadbar').length==1){
		if(!localStorage.getItem('access.data')){
			var ajat=lib.ajat('list/menu#domid=page&tempid=page-t');
			ajat.setExternal({slug:slug});
			ajat.render().done(function(data){
				access.foreach(data.data);
				localStorage.setItem('access.data',JSON.stringify(data.data));
				localStorage.setItem('access.map',JSON.stringify(access.map));
				access.init();
			});
		}else{
			var ajat=lib.ajat('#domid=page&tempid=page-t');
			ajat.template(JSON.parse(localStorage.getItem('access.data')));
		}
	}else{
		access.init();
	}
})
