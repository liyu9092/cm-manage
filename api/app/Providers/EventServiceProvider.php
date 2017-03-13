<?php

namespace App\Providers;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        //用户模块
        'user.update' => [
            'App\Listeners\UserEventListener@onUpdate',
        ],
        'user.create' => [
            'App\Listeners\UserEventListener@onCreate',
        ],   
        'user.delete' => [
            'App\Listeners\UserEventListener@onDelete',
        ],     
        'user.export' => [
            'App\Listeners\UserEventListener@onExport',
        ],        
        'user.disable' => [
            'App\Listeners\UserEventListener@onDisable',
        ],        
        'user.enable' => [
            'App\Listeners\UserEventListener@onEnable',
        ],        
        'user.resetCode' => [
            'App\Listeners\UserEventListener@onResetCode',
        ],
        'user.setCode' => [
            'App\Listeners\UserEventListener@onSetCode',
        ],
        'login' => [
            'App\Listeners\UserEventListener@onLogin',
        ],
        'logout' => [
            'App\Listeners\UserEventListener@onLogout',
        ],   

        //管理员模块
        'manager.update' => [
            'App\Listeners\ManagerEventListener@onUpdate',
        ],
        'manager.create' => [
            'App\Listeners\ManagerEventListener@onCreate',
        ],      
        'manager.export' => [
            'App\Listeners\ManagerEventListener@onExport',
        ],
        'login' => [
            'App\Listeners\ManagerEventListener@onLogin',
        ],
        'logout' => [
            'App\Listeners\ManagerEventListener@onLogout',
        ],   


        //角色模块
        'role.update' => [
            'App\Listeners\RoleEventListener@onUpdate',
        ],
        'role.create' => [
            'App\Listeners\RoleEventListener@onCreate',
        ],              
        'role.export' => [
            'App\Listeners\RoleEventListener@onExport',
        ],      

        //佣金单模块
        'commission.export' => [
            'App\Listeners\CommissionEventListener@onExport',
        ], 

        //返佣单模块
        'rebate.update' => [
            'App\Listeners\RebateEventListener@onUpdate',
        ],
        'rebate.create' => [
            'App\Listeners\RebateEventListener@onCreate',
        ],              
        'rebate.export' => [
            'App\Listeners\RebateEventListener@onExport',
        ],      
        'rebate.confirm' => [
            'App\Listeners\RebateEventListener@onConfirm',
        ],      


        //权限模块
        'permission.update' => [
            'App\Listeners\PermissionEventListener@onUpdate',
        ],
        'permission.create' => [
            'App\Listeners\PermissionEventListener@onCreate',
        ],          
        'permission.export' => [
            'App\Listeners\PermissionEventListener@onExport',
        ],  

        //日志模块          
        'role.export' => [
            'App\Listeners\LogEventListener@onExport',
        ],  
    		
    	//店铺模块
    	'salon.export' => [
    		'App\Listeners\SalonEventListener@onExport',
    	],
    	'salon.save' => [
    		'App\Listeners\SalonEventListener@onSave',
    	],
    	'salon.del' => [
    		'App\Listeners\SalonEventListener@onDel',
    	],
		'salon.endCooperation' => [
    		'App\Listeners\SalonEventListener@onEndCooperation',
    	],
		'salon.update' => [
    		'App\Listeners\SalonEventListener@onUpdate',
    	],
		//商户模块
    	'merchant.export' => [
    		'App\Listeners\MerchantEventListener@onExport',
    	],
    	'merchant.save' => [
    		'App\Listeners\MerchantEventListener@onSave',
    	],
    	'merchant.del' => [
    		'App\Listeners\MerchantEventListener@onDel',
    	],
		'merchant.update' => [
    		'App\Listeners\MerchantEventListener@onUpdate',
    	],
		//店铺账号模块
    	'salonAccount.save' => [
    		'App\Listeners\SalonAccountEventListener@onSave',
    	],
    	'salonAccount.delAct' => [
    		'App\Listeners\SalonAccountEventListener@onDelAct',
    	],
		'salonAccount.resetPwd' => [
    		'App\Listeners\SalonAccountEventListener@onResetPwd',
    	],

        //店铺结算相关操作
        'shopcount.export' => [
            'App\Listeners\ShopcountEventListener@onExport',
        ],
        'shopcount.create' => [
            'App\Listeners\ShopcountEventListener@onCreate',
        ],
        'shopcount.store' => [
            'App\Listeners\ShopcountEventListener@onStore',
        ],
        'shopcount.update' => [
            'App\Listeners\ShopcountEventListener@onUpdate',
        ],
        'shopcount.destroy' => [
            'App\Listeners\ShopcountEventListener@onDestroy',
        ],
        'shopcount.delegateExport' => [
            'App\Listeners\ShopcountEventListener@onDelegateExport',
        ],
        'shopcount.balanceExport' => [
            'App\Listeners\ShopcountEventListener@onBalanceExport',
        ],
        'shopcount.countOrder' => [
            'App\Listeners\ShopcountEventListener@onCountOrder',
        ],
        
        //付款单相关操作
        'pay.export' => [
            'App\Listeners\PayManageEventListener@onExport',
        ],
        'pay.store' => [
            'App\Listeners\PayManageEventListener@onStore',
        ],
        'pay.update' => [
            'App\Listeners\PayManageEventListener@onUpdate',
        ],
        'pay.destroy' => [
            'App\Listeners\PayManageEventListener@onDestroy',
        ],
        'pay.check' => [
            'App\Listeners\PayManageEventListener@onCheck',
        ],
        'pay.confirm' => [
            'App\Listeners\PayManageEventListener@onConfirm',
        ],
        

        //项目仓库
        'warehouse.puton' => [
            'App\Listeners\WarehouseEventListener@onPuton',
        ],
        'warehouse.import' => [
            'App\Listeners\WarehouseEventListener@onImport',
        ],
        'warehouse.destroy' => [
            'App\Listeners\WarehouseEventListener@onDestroy',
        ],
    		
    	//添加修改项目
		'ItemInfo.save' => [
    		'App\Listeners\ItemInfoEventListener@onSave',
    	],
		'ItemInfo.update' => [
    		'App\Listeners\ItemInfoEventListener@onUpdate',
    	],

        //交易管理
        'order.export' => [
            'App\Listeners\TransactionEventListener@onOrderExport',
        ],
        'ticket.export' => [
             'App\Listeners\TransactionEventListener@onTicketExport',
        ],
        'refund.export' => [
             'App\Listeners\TransactionEventListener@onRefundExport',
        ],
        'refund.accept' => [
            'App\Listeners\TransactionEventListener@onAccept',
        ],
        'refund.reject' => [
            'App\Listeners\TransactionEventListener@onReject',
        ],

    	//收款单
    	'Receivables.save' => [
    			'App\Listeners\ReceivablesEventListener@onSave',
    	],
    	'Receivables.update' => [
    			'App\Listeners\ReceivablesEventListener@onUpdate',
    	],
    	'Receivables.delete' => [
    			'App\Listeners\ReceivablesEventListener@onDel',
    	],
    	'Receivables.confirmReceivables' => [
    			'App\Listeners\ReceivablesEventListener@onConfirmReceivables',
    	],
    	'Receivables.export' => [
    			'App\Listeners\ReceivablesEventListener@onExport',
    	],

    	//消息
    	'StylistMsgConf.online' => [
    			'App\Listeners\StylistMsgConfEventListener@online',
    	],
    	'StylistMsgConf.update' => [
    			'App\Listeners\StylistMsgConfEventListener@onUpdate',
    	],
    	'StylistMsgConf.delete' => [
    			'App\Listeners\StylistMsgConfEventListener@onDel',
    	],
    	'StylistMsgConf.save' => [
    			'App\Listeners\StylistMsgConfEventListener@onSave',
    	], 
        
        // 平台活动
    	'platform.add' => [
            'App\Listeners\PlatformEventListener@onCreate',
    	],
        'platform.editConf' => [
            'App\Listeners\PlatformEventListener@onUpdate',
    	],
        'platform.exportList' => [
            'App\Listeners\PlatformEventListener@onExport',
    	],
        'platform.offlineConf' => [
            'App\Listeners\PlatformEventListener@onOffline',
    	],
        'platform.closeConf' => [
            'App\Listeners\PlatformEventListener@onClose',
    	],
        'platform.upConf' => [
            'App\Listeners\PlatformEventListener@onUp',
    	],
        
        // 兑换活动
    	'coupon.add' => [
            'App\Listeners\CouponEventListener@onCreate',
    	],
        'coupon.editConf' => [
            'App\Listeners\CouponEventListener@onUpdate',
    	],
        'coupon.exportList' => [
            'App\Listeners\CouponEventListener@onExport',
    	],
        'coupon.exportCoupon' => [
            'App\Listeners\CouponEventListener@onExportCoupon',
    	],
        'coupon.offlineConf' => [
            'App\Listeners\CouponEventListener@onOffline',
    	],
        'coupon.closeConf' => [
            'App\Listeners\CouponEventListener@onClose',
    	],
        'coupon.upConf' => [
            'App\Listeners\CouponEventListener@onUp',
    	],
        
        // 劵操作
        'voucher.invalidStatus' => [
            'App\Listeners\TicketEventListener@onOffline',
    	],
        'voucher.exportTicketList' => [
            'App\Listeners\TicketEventListener@onExport',
    	],

        
        // 用户评价
        'comment.hidden' => [
            'App\Listeners\CommentEventListener@onHidden',
    	],
        'comment.delete' => [
            'App\Listeners\CommentEventListener@onDelete',
		],
		// 韩式定妆项目操作
        'beautyItem.updateFashion' => [
            'App\Listeners\BeautyItemEventListener@onUpdateFashion',
    	],
        'beautyItem.update' => [
            'App\Listeners\BeautyItemEventListener@onUpdate',
		],
        // 专家列表
        'artificer.add' => [
            'App\Listeners\ArtificerEventListener@onCreate',
    	],
        'artificer.update' => [
            'App\Listeners\ArtificerEventListener@onUpdate',
    	],
        'artificer.up' => [
            'App\Listeners\ArtificerEventListener@onUp',
    	],
        'artificer.down' => [
            'App\Listeners\ArtificerEventListener@onDown',
    	],
        'artificer.export' => [
            'App\Listeners\ArtificerEventListener@onExport',
    	],
        'artificer.worksCreate' => [
            'App\Listeners\ArtificerEventListener@onWorkCreate',
    	],
        'artificer.updateIds' => [
            'App\Listeners\ArtificerEventListener@onUpdateIds',
    	],
        'artificer.updateDesc' => [
            'App\Listeners\ArtificerEventListener@onUpdateDesc',
    	],
        // 专家助理列表
        'assistant.add' => [
            'App\Listeners\AssistantEventListener@onCreate',
    	],
        'assistant.update' => [
            'App\Listeners\AssistantEventListener@onUpdate',
    	],
        'assistant.up' => [
            'App\Listeners\AssistantEventListener@onUp',
    	],
        'assistant.down' => [
            'App\Listeners\AssistantEventListener@onDown',
    	],
        'assistant.export' => [
            'App\Listeners\AssistantEventListener@onExport',
    	],
        
        // 其他人员列表
        'others.add' => [
            'App\Listeners\OthersEventListener@onCreate',
    	],
        'others.update' => [
            'App\Listeners\OthersEventListener@onUpdate',
    	],
        'others.up' => [
            'App\Listeners\OthersEventListener@onUp',
    	],
        'others.down' => [
            'App\Listeners\OthersEventListener@onDown',
    	],
        'others.export' => [
            'App\Listeners\OthersEventListener@onExport',
    	],
        
        //韩式定妆模块
        'banner.create' => [
            'App\Listeners\BannerEventListener@onCreate',
    	],
        'banner.edit' => [
            'App\Listeners\BannerEventListener@onEdit',
    	],
        'banner.destroy' => [
            'App\Listeners\BannerEventListener@onDestroy',
        ],
        
        // 定妆中心介绍
        'beauty.edit' => [
            'App\Listeners\BeautyEventListener@onEdit',
    	],
        'beauty.delete' => [
            'App\Listeners\BeautyEventListener@onDelete',
    	],
        
        //定妆赠送活动
        'powder.create' => [
            'App\Listeners\PowderArticlesEventListener@onCreate',
    	],
        'powder.showArticleDetail' => [
            'App\Listeners\PowderArticlesEventListener@onShowArticleDetail',
    	],
        'powder.closeArticleVerify' => [
            'App\Listeners\PowderArticlesEventListener@onCloseArticleVerify',
    	],
        'powder.closeArticle' => [
            'App\Listeners\PowderArticlesEventListener@onCloseArticle',
    	],
        'powder.showArticleTicketInfo' => [
            'App\Listeners\PowderArticlesEventListener@onShowArticleTicketInfo',
    	],
        'powder.exportArticleTicket' => [
            'App\Listeners\PowderArticlesEventListener@onExportArticleTicket',
    	],
        'powder.showTicketInfo' => [
            'App\Listeners\PowderArticlesEventListener@onShowTicketInfo',
    	],
        'powder.useArticleTicket' => [
            'App\Listeners\PowderArticlesEventListener@onUseArticleTicket',
    	],
        'powder.editPresentUseInfo' => [
            'App\Listeners\PowderArticlesEventListener@onEditPresentUseInfo',
    	],

        //定妆单退款
        'BeautyRefund.show'=>[
          'App\Listeners\BeautyRefundEventListener@onShow',  
        ],
        'BeautyRefund.reject'=>[
          'App\Listeners\BeautyRefundEventListener@onReject',  
        ],
        'BeautyRefund.accept'=>[
          'App\Listeners\BeautyRefundEventListener@onAccept', 
	],

		//定妆单操作
        'booking.create' => [
            'App\Listeners\BookingOrderEventListener@onCreate',
        ],
        'booking.receive' => [
            'App\Listeners\BookingOrderEventListener@onReceive',
        ],
        'booking.cash' => [
            'App\Listeners\BookingOrderEventListener@onCash',
        ],
        'booking.bill' => [
            'App\Listeners\BookingOrderEventListener@onBill',
        ],
        'booking.relatively' => [
            'App\Listeners\BookingOrderEventListener@onRelatively',
        ],
        'booking.relatively2' => [
            'App\Listeners\BookingOrderEventListener@onRelatively2',
        ],
        'booking.refund' => [
            'App\Listeners\BookingOrderEventListener@onRefund',
        ],
        'booking.change' => [
            'App\Listeners\BookingOrderEventListener@onChange',
        ],
        'booking.update' => [
            'App\Listeners\BookingOrderEventListener@onUpdate',
        ],
        'booking.gift' => [
            'App\Listeners\BookingOrderEventListener@onGift',
        ],
        'booking.destroy' => [
            'App\Listeners\BookingOrderEventListener@onDestroy',
        ],
// 		预约设置
        'calendar.status' => [
        	'App\Listeners\BookingCalendarEventListener@onStatus',
        ],
        'calendar.modifyDay' => [
            'App\Listeners\BookingCalendarEventListener@onModifyDay',
        ],
        'calendar.export' => [
            'App\Listeners\BookingCalendarEventListener@onExport',
        ],
        'calendar.modifyLimit' => [
            'App\Listeners\BookingCalendarEventListener@onModifyLimit',
        ],
        
    ];

    /**
     * Register any other events for your application.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @return void
     */
    public function boot(DispatcherContract $events)
    {
        parent::boot($events);

        //
    }
}
