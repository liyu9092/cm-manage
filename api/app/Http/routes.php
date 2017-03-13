<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

//welcome view
Route::any('/', function () {
    return view('welcome');
});

//测试控制器
Route::any('test',array(
	'uses'=>'IndexController@test'
));
//测试控制器
Route::any('makeup/test',array(
	'uses'=>'Transaction\MakeupController@test'
));

Route::any('captcha', 'IndexController@captcha');
Route::any('login', 'IndexController@login');
Route::any('logout', 'IndexController@logout');

//self模块
Route::group(['middleware' => ['jwt.auth']], function(){
	Route::any('self/show',array(
		'as'=>'self.show',
		'uses'=>'SelfController@show'
	));
	Route::any('self/update',array(
		'as'=>'self.update',
		'uses'=>'SelfController@update'
	));

});


//用户等级列表
Route::any('level/index',array(
	'as'=>'level.index',
	'uses'=>'LevelController@index'
));		
		
//列表模块
Route::any('list/city',array(
	'as'=>'list.city',
	'uses'=>'ListController@city'
));
Route::any('list/department',array(
	'as'=>'list.department',
	'uses'=>'ListController@department'
));
Route::any('list/position',array(
	'as'=>'list.position',
	'uses'=>'ListController@position'
));
Route::any('list/permission',array(
	'as'=>'list.permission',
	'uses'=>'ListController@permission'
));
Route::any('list/menu',array(
	'as'=>'list.menu',
	'uses'=>'ListController@menu'
));
Route::any('salonList/getProvinces',array(  //获取省市区商圈
	'as'=>'salonList.getProvinces',
	'uses'=>'Merchant\ListController@getProvinces'
));	
Route::any('salonList/getBussesName',array(  //获取业务代表
	'as'=>'salonList.getBussesName',
	'uses'=>'Merchant\ListController@getBussesName'
));	
Route::any('merchant/checkMerchantSn',array( //检测商户编号
	'as'=>'merchant.checkMerchantSn',
	'uses'=>'Merchant\MerchantController@checkMerchantSn'
));
Route::any('merchant/getSn',array( //获取商户编号
				'as'=>'merchant.getSn',
				'uses'=>'Merchant\MerchantController@getSn'
));
Route::any('salon/checkSalonSn',array(  //检测店铺编号
	'as'=>'salon.checkSalonSn',
	'uses'=>'Merchant\SalonController@checkSalonSn'
));	
Route::any('salonAccount/getSalonName',array(  //模糊查找店铺
	'as'=>'salonAccount.getSalonName',
	'uses'=>'Merchant\SalonAccountController@getSalonName'
));
Route::any('salonList/getItemType',array(  //获取项目分类
		'as'=>'salonList.getItemType',
		'uses'=>'Merchant\ListController@getItemType'
));
Route::any('itemInfo/getItems',array(  //获取分类下项目名称
		'as'=>'itemInfo.getItemByTypeid',
		'uses'=>'Item\ItemInfoController@getItemByTypeid'
));
Route::any('itemInfo/getAddedService',array(  //获取增值服务
		'as'=>'itemInfo.getAddedService',
		'uses'=>'Item\ItemInfoController@getAddedService'
));

//项目分类列表
Route::any('item/type',array(
	'as'=>'item.type',
	'uses'=>'ItemController@type'
));

// 店铺消费验证  结算
Route::any('shop_count/count_order','ShopCount\ShopCountController@countOrder');

//退款回调 支付宝 普通单
Route::any('refund/call_back_of_alipay',array( 
	'as'=>'refund.call_back_of_alipay',
	'uses'=>'Transaction\OrderRefundController@call_back_of_alipay'
));

Route::any('AlipayRefundNotify/callback_alipay',array(  //赏金单支付包退款回调
	'as'=>'AlipayRefundNotify.callback_alipay',
	'uses'=>'Alipay\AlipayRefundNotifyController@callback_alipay'
));
//退款回调 支付宝 定妆单
Route::any('beautyrefund/beauty_call_back_of_alipay',array( 
	'as'=>'beautyrefund.beauty_call_back_of_alipay',
	'uses'=>'Transaction\BeautyRefundController@beauty_call_back_of_alipay'
));
// 营销平台活动用到的
Route::any('platform/getRequestDepartment',array(  
    'as'=>'platform.getRequestDepartment',
    'uses'=>'Promotion\PlatformController@getRequestDepartment'
));
Route::any('platform/getDepartmentManager/{id}',array(  
    'as'=>'platform.getDepartmentManager',
    'uses'=>'Promotion\PlatformController@getDepartmentManager'
));
Route::any('platform/getActNum',array(  
    'as'=>'platform.getActNum',
    'uses'=>'Promotion\PlatformController@getActNum'
));
Route::any('coupon/getActNum',array(  
    'as'=>'coupon.getActNum',
    'uses'=>'Coupon\CouponController@getActNum'
));
Route::any('platform/checkSerial',array(  
    'as'=>'platform.checkSerial',
    'uses'=>'Promotion\PlatformController@checkSerial'
));
Route::any('platform/getItemType',array(  
    'as'=>'platform.getItemType',
    'uses'=>'Promotion\PlatformController@getItemType'
));

Route::any('artificer/checkNumberExists/{id?}',array(  
    'as'=>'artificer.checkNumberExists',
    'uses'=>'Artificer\ArtificerController@checkNumberExists'
)); 
Route::any('artificer/checkNameExists/{id?}',array(  
    'as'=>'artificer.checkNameExists',
    'uses'=>'Artificer\ArtificerController@checkNameExists'
)); 
Route::any('beautyItem/itemList',array(  
	'as'=>'beautyItem.itemList',
	'uses'=>'Item\BeautyItemController@itemList'
));

Route::any('beautyItem/checkName',array(  
	'as'=>'beautyItem.checkName',
	'uses'=>'Item\BeautyItemController@checkName'
));
	
	

Route::any('assistant/checkNumberExists/{id?}',array(  
    'as'=>'assistant.checkNumberExists',
    'uses'=>'Artificer\ArtificerAssistantController@checkNumberExists'
));
Route::any('assistant/checkNameExists/{id?}',array(  
    'as'=>'assistant.checkNameExists',
    'uses'=>'Artificer\ArtificerAssistantController@checkNameExists'
));
Route::any('assistant/getArtificer',array(  
    'as'=>'assistant.getArtificer',
    'uses'=>'Artificer\ArtificerAssistantController@getArtificer'
));

Route::any('artificer/search/{name}',array(  
    'as'=>'assistant.search',
    'uses'=>'Artificer\ArtificerController@searchNameAndNumber'
));

Route::any('assistant/search/{name}',array(  
    'as'=>'assistant.search',
    'uses'=>'Artificer\ArtificerAssistantController@searchNameAndNumber'
));

Route::any('others/checkNumberExists/{number}',array(  
    'as'=>'others.checkNumberExists',
    'uses'=>'Beauty\OthersController@checkNumberExists'
    ));
Route::any('others/search/{type}/{name}',array(  
    'as'=>'others.search',
    'uses'=>'Beauty\OthersController@searchNameAndNumber'
));

//商家后台提现
Route::any('pay_manage/withdraw','Pay\PayController@withdraw');

//消息盒子内容跳转h5
 Route::any('messageBox/redirectUrl/{pushId}',array(  
        'as'=>'messageBox.redirectUrl',
        'uses'=>'MessageBox\MessageBoxController@redirectUrl'
    ))->where('pushId', '[0-9]+'); 
//权限管理后台接口
// Route::group(['middleware' => ['jwt.auth','acl.auth']], function(){
Route::group(['middleware' => ['before']], function(){


	//管理员模块
	Route::any('manager/index',array(
		'as'=>'manager.index',
		'uses'=>'ManagerController@index'
	));
	Route::any('manager/show/{id}',array(
		'as'=>'manager.show',
		'uses'=>'ManagerController@show'
	));
	Route::any('manager/update/{id}',array(
		'as'=>'manager.update',
		'uses'=>'ManagerController@update'
	));
	Route::any('manager/create',array(
		'as'=>'manager.create',
		'uses'=>'ManagerController@create'
	));	
	Route::any('manager/export',array(
		'as'=>'manager.export',
		'uses'=>'ManagerController@export'
	));

	//用户模块
	Route::any('user/survey',array(
		'as'=>'user.survey',
		'uses'=>'UserController@survey'
	));
	Route::any('user/index',array(
		'as'=>'user.index',
		'uses'=>'UserController@index'
	));
	Route::any('user/export',array(
		'as'=>'user.export',
		'uses'=>'UserController@export'
	));
	Route::any('user/show/{id}',array(
		'as'=>'user.show',
		'uses'=>'UserController@show'
	));
	Route::any('user/update/{id}',array(
		'as'=>'user.update',
		'uses'=>'UserController@update'
	));	
	Route::any('user/destroy/{id}',array(
		'as'=>'user.destroy',
		'uses'=>'UserController@destroy'
	));
	Route::any('user/enable/{id}',array(
		'as'=>'user.enable',
		'uses'=>'UserController@enable'
	));
	Route::any('user/disable/{id}',array(
		'as'=>'user.disable',
		'uses'=>'UserController@disable'
	));
	Route::any('user/resetCode/{id}',array(
		'as'=>'user.resetCode',
		'uses'=>'UserController@resetCode'
	));
	Route::any('user/setCode/{id}',array(
		'as'=>'user.setCode',
		'uses'=>'UserController@setCode'
	));
	Route::any('user/company',array(
		'as'=>'user.company',
		'uses'=>'UserController@company'
	));

	//用户等级模块
	Route::any('level/update',array(
		'as'=>'level.update',
		'uses'=>'LevelController@update'
	));

	//反馈模块
	Route::any('feed/index',array(
		'as'=>'feed.index',
		'uses'=>'FeedController@index'
	));
	Route::any('feed/destroy',array(
		'as'=>'feed.destroy',
		'uses'=>'FeedController@destroy'
	));


	//角色模块
	Route::any('role/index',array(
		'as'=>'role.index',
		'uses'=>'RoleController@index'
	));
	Route::any('role/show/{id}',array(
		'as'=>'role.show',
		'uses'=>'RoleController@show'
	));
	Route::any('role/update/{id}',array(
		'as'=>'role.update',
		'uses'=>'RoleController@update'
	));	
	Route::any('role/create',array(
		'as'=>'role.create',
		'uses'=>'RoleController@create'
	));	
	Route::any('role/export',array(
		'as'=>'role.export',
		'uses'=>'RoleController@export'
	));


	//权限模块
	Route::any('permission/index',array(
		'as'=>'permission.index',
		'uses'=>'PermissionController@index'
	));
	Route::any('permission/show/{id}',array(
		'as'=>'permission.show',
		'uses'=>'PermissionController@show'
	));
	Route::any('permission/update/{id}',array(
		'as'=>'permission.update',
		'uses'=>'PermissionController@update'
	));	
	Route::any('permission/create',array(
		'as'=>'permission.create',
		'uses'=>'PermissionController@create'
	));	
	Route::any('permission/export',array(
		'as'=>'permission.export',
		'uses'=>'PermissionController@export'
	));


	//日志模块
	Route::any('log/index',array(
		'as'=>'log.index',
		'uses'=>'LogController@index'
	));
	Route::any('log/export',array(
		'as'=>'log.export',
		'uses'=>'LogController@export'
	));

	//项目模块
	Route::any('item/index',array(
		'as'=>'item.index',
		'uses'=>'ItemController@index'
	));	
	Route::any('item/show/{id}',array(
		'as'=>'item.show',
		'uses'=>'ItemController@show'
	));	
	Route::any('item/sort',array(
		'as'=>'item.sort',
		'uses'=>'ItemController@sort'
	));
	Route::any('item/export',array(
		'as'=>'item.export',
		'uses'=>'ItemController@export'
	));
	Route::any('item/down/{id}',array(
		'as'=>'item.down',
		'uses'=>'ItemController@down'
	));

	//闲时特价
	Route::any('onsale/index',array(
		'as'=>'onsale.index',
		'uses'=>'OnSaleController@index'
	));	
	Route::any('onsale/show/{id}',array(
		'as'=>'onsale.show',
		'uses'=>'OnSaleController@show'
	));	
	Route::any('onsale/export',array(
		'as'=>'onsale.export',
		'uses'=>'OnSaleController@export'
	));
	
	//佣金单
	Route::any('commission/index',array(
		'as'=>'commission.index',
		'uses'=>'CommissionController@index'
	));
	Route::any('commission/export',array(
		'as'=>'commission.export',
		'uses'=>'CommissionController@export'
	));
	Route::any('commission/show/{id}',array(
		'as'=>'commission.show',
		'uses'=>'CommissionController@show'
	));

	//返佣单
	Route::any('rebate/index',array(
		'as'=>'rebate.index',
		'uses'=>'RebateController@index'
	));
	Route::any('rebate/create',array(
		'as'=>'rebate.create',
		'uses'=>'RebateController@create'
	));	

	Route::any('rebate/update/{id}',array(
		'as'=>'rebate.update',
		'uses'=>'RebateController@update'
	));

	Route::any('rebate/show/{id}',array(
		'as'=>'rebate.show',
		'uses'=>'RebateController@show'
	));	

	Route::any('rebate/export',array(
		'as'=>'rebate.export',
		'uses'=>'RebateController@export'
	));

	Route::any('rebate/confirm',array(
		'as'=>'rebate.confirm',
		'uses'=>'RebateController@confirm'
	));	

	Route::any('rebate/upload',array(
		'as'=>'rebate.upload',
		'uses'=>'RebateController@upload'
	));	

	Route::any('rebate/destroy/{id}',array(
		'as'=>'rebate.destroy',
		'uses'=>'RebateController@destroy'
	));



	 // 店铺结算模块
    
    // 列表 搜索
    Route::any('shop_count/index', array(
        'as' => 'shop_count.index',
        'uses' => 'ShopCount\ShopCountController@index'
    ));
    // 详情
    Route::any('shop_count/show/{id}', array(
        'as' => 'shop_count.show',
        'uses' => 'ShopCount\ShopCountController@show'
    ));
    // 修改
    Route::any('shop_count/update/{id}', array(
        'as' => 'shop_count.update',
        'uses' => 'ShopCount\ShopCountController@update'
    ));
    // 预览
    Route::any('shop_count/preview', array(
	    'as' => 'shop_count.preview',
	    'uses' => 'ShopCount\ShopCountController@create'
    ));

    // 新增
    Route::any('shop_count/create', array(
        'as' => 'shop_count.create',
        'uses' => 'ShopCount\ShopCountController@store'
    ));
    
    // 删除
    Route::any('shop_count/destroy/{id}', array(
        'as' => 'shop_count.destroy',
        'uses' => 'ShopCount\ShopCountController@destroy'
    ));
    
    // 代收单列表 搜索
    Route::any('shop_count/delegate_list', array(
        'as' => 'shop_count.delegate_list',
        'uses' => 'ShopCount\DelegateController@index'
    ));
    
    // 代收单详情
    Route::any('shop_count/delegate_detail/{id}', array(
        'as' => 'shop_count.delegate_detail',
        'uses' => 'ShopCount\DelegateController@show'
    ));
    
    // 往来余额 查询
    Route::any('shop_count/balance', array(
        'as' => 'shop_count.balance',
        'uses' => 'ShopCount\BalanceController@index'
    ));  
    
    //转付单导出
    Route::any('shop_count/export', array(
	    'as' => 'shop_count.export',
	    'uses' => 'ShopCount\ShopCountController@export'
    ));
    
    //代收单导出
    Route::any('shop_count/delegate_export', array(
	    'as' => 'shop_count.delegate_export',
	    'uses' => 'ShopCount\DelegateController@export'
    ));
    
    //店铺往来导出
    Route::any('shop_count/balance_export', array(
	    'as' => 'shop_count.balance_export',
	    'uses' => 'ShopCount\BalanceController@export'
    ));


	//商户模块
	Route::any('merchant/export',array(  //商户列表导出
		'as'=>'merchant.export',
		'uses'=>'Merchant\MerchantController@export'
	));


    Route::any('merchant/index',array(  //商户列表
		'as'=>'merchant.index',
		'uses'=>'Merchant\MerchantController@index'
	));

	Route::any('merchant/save',array(  //添加修改商户
		'as'=>'merchant.save',
		'uses'=>'Merchant\MerchantController@save'
	));
	Route::any('merchant/update',array(  //修改商户
		'as'=>'merchant.update',
		'uses'=>'Merchant\MerchantController@update'
	));
	Route::any('merchant/del',array(  //删除商户
		'as'=>'merchant.del',
		'uses'=>'Merchant\MerchantController@del'
	));
	Route::any('merchant/getMerchantList',array( //获取单个商户详情
		'as'=>'merchant.getMerchantList',
		'uses'=>'Merchant\MerchantController@getMerchantList'
	));


	Route::any('salon/index',array(  //店铺列表
		'as'=>'salon.index',
		'uses'=>'Merchant\SalonController@index'
	));
	
	//店铺模块
	Route::any('salon/export',array(  //店铺列表导出
		'as'=>'salon.export',
		'uses'=>'Merchant\SalonController@export'
	));	
	Route::any('salon/save',array( //店铺添加 接口
		'as'=>'salon.save',
		'uses'=>'Merchant\SalonController@save'
	));
	Route::any('salon/update',array( //店铺 更新接口
		'as'=>'salon.update',
		'uses'=>'Merchant\SalonController@update'
	));
	Route::any('salon/getSalon',array(  //获取店铺详情
		'as'=>'salon.getSalon',
		'uses'=>'Merchant\SalonController@getSalon'
	));	
	Route::any('salon/endCooperation',array( //终止合作
		'as'=>'salon.endCooperation',
		'uses'=>'Merchant\SalonController@endCooperation'
	));
	Route::any('salon/del',array(  //删除店铺
		'as'=>'salon.del',
		'uses'=>'Merchant\SalonController@del'
	));		
	
    //店铺星级配置管理
    Route::any('starconf/index', array(//星级积分列表
        'as' => 'starconf.index',
        'uses' => 'Merchant\StarconfController@index'
    ));
    Route::any('starconf/update', array(//星级积分更新
        'as' => 'starconf.update',
        'uses' => 'Merchant\StarconfController@update'
    ));
    Route::any('/starconf/online', array(// 开启/暂停  店铺星级积分
        'as' => 'starconf.online',
        'uses' => 'Merchant\StarconfController@online'
    ));

    //店铺星级积分管理
    Route::any('salonstar/index', array(//星级积分列表
        'as' => 'salonstar.index',
        'uses' => 'Merchant\SalonstarController@index'
    ));
    Route::any('salonstar/update', array(//增加/减少 积分
        'as' => 'salonstar.update',
        'uses' => 'Merchant\SalonstarController@update'
    ));
    Route::any('salonstar/show', array(//积分详情
        'as' => 'salonstar.show',
        'uses' => 'Merchant\SalonstarController@show'
    ));
    
    //评分对应分值配置
     Route::any('scoreconf/index', array(//评分配置列表
        'as' => 'scoreconf.index',
        'uses' => 'Merchant\ScoreconfController@index'
    ));
      Route::any('scoreconf/update', array(//评分配置跟新
        'as' => 'scoreconf.update',
        'uses' => 'Merchant\ScoreconfController@update'
    ));



    Route::any('salonAccount/index',array(  //店铺账号列表
		'as'=>'salonAccount.index',
		'uses'=>'Merchant\SalonAccountController@index'
	));	

	Route::any('salonAccount/save',array(  //添加账号
		'as'=>'salonAccount.save',
		'uses'=>'Merchant\SalonAccountController@save'
	));	
	Route::any('salonAccount/resetPwd',array(  //重置密码
		'as'=>'salonAccount.resetPwd',
		'uses'=>'Merchant\SalonAccountController@resetPwd'
	));	
	Route::any('salonAccount/delAct',array(  //删除
		'as'=>'salonAccount.del',
		'uses'=>'Merchant\SalonAccountController@delAct'
	));	
	
	//财务管理-收款管理
	Route::any('receivables/index',array(  //列表
		'as'=>'receivables.index',
		'uses'=>'Financial\ReceivablesController@index'
	));
	
	Route::any('receivables/save',array(  //添加
		'as'=>'receivables.save',
		'uses'=>'Financial\ReceivablesController@save'
	));
	Route::any('receivables/update',array(  //修改
		'as'=>'receivables.update',
		'uses'=>'Financial\ReceivablesController@update'
	));
	Route::any('receivables/confirmAct',array(  //确认收款
		'as'=>'receivables.confirmAct',
		'uses'=>'Financial\ReceivablesController@confirmAct'
	));
	Route::any('receivables/export',array(  //确认收款
		'as'=>'receivables.export',
		'uses'=>'Financial\ReceivablesController@export'
	));
	Route::any('receivables/getone',array(  //详细
		'as'=>'receivables.getone',
		'uses'=>'Financial\ReceivablesController@getReceivablesByid'
	));
	Route::any('receivables/del',array(  //删除
		'as'=>'receivables.del',
		'uses'=>'Financial\ReceivablesController@del'
	));
	
	
	
    
	//付款管理
	Route::any('pay_manage/index',array(  //列表
		'as'=>'pay_manage.index',
		'uses'=>'Pay\PayController@index'
    ));
	
	Route::any('pay_manage/check_list',array(  //审批列表
		'as'=>'pay_manage.check_list',
		'uses'=>'Pay\PayController@index'
    ));
	
	Route::any('pay_manage/confirm_list',array(  //确认列表
		'as'=>'pay_manage.confirm_list',
		'uses'=>'Pay\PayController@index'
    ));
	
	Route::any('pay_manage/show/{id}',array(  //查看
	'as'=>'pay_manage.show',
	'uses'=>'Pay\PayController@show'
	    ));
	
	Route::any('pay_manage/create',array(  //新增
		'as'=>'pay_manage.create',
		'uses'=>'Pay\PayController@store'
    ));
	
	Route::any('pay_manage/update/{id}',array(  //修改
		'as'=>'pay_manage.update',
		'uses'=>'Pay\PayController@update'
    ));
	
	Route::any('pay_manage/destroy/{id}',array(  //删除
		'as'=>'pay_manage.destroy',
		'uses'=>'Pay\PayController@destroy'
    ));
	
	Route::any('pay_manage/check',array(  //审核
		'as'=>'pay_manage.check',
		'uses'=>'Pay\PayController@check'
    ));
	
	Route::any('pay_manage/confirm',array(  //确认
		'as'=>'pay_manage.confirm',
		'uses'=>'Pay\PayController@confirm'
    ));
	
	Route::any('pay_manage/export',array(  //导出
		'as'=>'pay_manage.export',
		'uses'=>'Pay\PayController@export'
    ));

	
	//交易管理
	Route::any('order/index',array(  //订单列表
		'as'=>'order.index',
		'uses'=>'Transaction\OrderController@index'
    ));
	
	Route::any('order/show/{id}',array(  //订单详情
		'as'=>'order.show',
		'uses'=>'Transaction\OrderController@show'
    ));
	
	Route::any('order/export',array(  //订单导出
		'as'=>'order.export',
		'uses'=>'Transaction\OrderController@export'
    ));
	
	Route::any('ticket/index',array(  //臭美券列表
		'as'=>'ticket.index',
		'uses'=>'Transaction\TicketController@index'
    ));
	
	Route::any('ticket/show/{id}',array(  //臭美券详情
		'as'=>'ticket.show',
		'uses'=>'Transaction\TicketController@show'
    ));
	
	Route::any('ticket/export',array(  //臭美券导出
		'as'=>'ticket.export',
		'uses'=>'Transaction\TicketController@export'
    ));
	
	Route::any('refund/index',array(  //退款列表
		'as'=>'refund.index',
		'uses'=>'Transaction\OrderRefundController@index'
    ));
	
	Route::any('refund/show/{id}',array(  //退款详情
	'as'=>'refund.show',
	'uses'=>'Transaction\OrderRefundController@show'
    ));

	Route::any('refund/export',array(  //退款导出
		'as'=>'refund.export',
		'uses'=>'Transaction\OrderRefundController@export'
    ));
	
	
	Route::any('refund/accept',array(  //退款通过
		'as'=>'refund.accept',
		'uses'=>'Transaction\OrderRefundController@accept'
    ));
	
	Route::any('refund/reject',array(  //退款拒绝
		'as'=>'refund.reject',
		'uses'=>'Transaction\OrderRefundController@reject'
    ));
    
    Route::any('appointment/index',array(  //预约造型师列表
		'as'=>'appointment.index',
		'uses'=>'Transaction\AppointmentController@index'
    ));
    
    Route::any('appointment/show/{id}',array(  //查看预约造型师
		'as'=>'appointment.show',
		'uses'=>'Transaction\AppointmentController@show'
    ));
    
    Route::any('appointment/export',array(  //导出预约造型师列表
		'as'=>'appointment.export',
		'uses'=>'Transaction\AppointmentController@export'
    ));
    
    //预约单
    Route::any('book/index',array(  //预约单列表
    'as'=>'book.index',
    'uses'=>'Transaction\BookController@index'
        ));
    
    Route::any('book/create',array(  //新增代客预约单
    'as'=>'book.create',
    'uses'=>'Transaction\BookController@create'
        ));
    
    Route::any('book/show/{id}',array(  //查看预约单
    'as'=>'book.show',
    'uses'=>'Transaction\BookController@show'
        ));
    
    Route::any('book/receive/{id}',array(  //接待(美导信息)
    'as'=>'book.receive',
    'uses'=>'Transaction\BookController@receive'
        ));
    
    Route::any('book/change/{id}',array(  //接待(项目信息等)
    'as'=>'book.change',
    'uses'=>'Transaction\BookController@change'
        ));

    
    Route::any('book/cash/{id}',array(  //收银
    'as'=>'book.cash',
    'uses'=>'Transaction\BookController@cash'
        ));
    
    Route::any('book/bill/{id}',array(  //开发票
    'as'=>'book.bill',
    'uses'=>'Transaction\BookController@bill'
        ));
    
    Route::any('book/relatively/{id}',array(  //补色
    'as'=>'book.relatively',
    'uses'=>'Transaction\BookController@relatively'
        ));
    
    Route::any('book/relatively2/{id}',array(  //补色(二次补色)
    'as'=>'book.relatively2',
    'uses'=>'Transaction\BookController@relatively2'
        ));
    
    Route::any('book/refund/{id}',array(  //退款
    'as'=>'book.refund',
    'uses'=>'Transaction\BookController@refund'
        ));
    
    Route::any('book/refund_booking/{id}',array(  //退款
    'as'=>'book.refund',
    'uses'=>'Transaction\BookController@refund_booking'
        ));
    
    Route::any('book/update/{id}',array(  //修改项目详情
    'as'=>'book.update',
    'uses'=>'Transaction\BookController@update'
        ));
    

    Route::any('book/add_item/{id}',array(  //新增项目
    'as'=>'book.update',
    'uses'=>'Transaction\BookController@addItem'
        ));
    
    Route::any('book/remove_item/{id}',array(  //删除项目
    'as'=>'book.update',
    'uses'=>'Transaction\BookController@removeItem'
        ));
    
    Route::any('book/gift/{id}',array(  //赠送项目
    'as'=>'book.gift',
    'uses'=>'Transaction\BookController@gift'
        ));
    
    Route::any('book/destroy/{id}',array(  //删除
    'as'=>'book.destroy',
    'uses'=>'Transaction\BookController@destroy'
        ));
    
    Route::any('book/export',array(  //预约单列表
    'as'=>'book.export',
    'uses'=>'Transaction\BookController@export'
        ));

	//消息管理
	Route::any('message/create',array(  //添加消息
		'as'=>'message.create',
		'uses'=>'Message\MessageController@store'
	));
	Route::any('message/update',array(  //修改消息
		'as'=>'message.update',
		'uses'=>'Message\MessageController@update'
	));
	Route::any('message/checkPhone',array(  //修改消息
		'as'=>'message.checkPhone',
		'uses'=>'Message\MessageController@checkPhone'
	));
	Route::any('message/index',array(  //消息列表
		'as'=>'message.index',
		'uses'=>'Message\MessageController@index'
	));
	Route::any('message/destroy',array(  //删除消息
		'as'=>'message.destroy',
		'uses'=>'Message\MessageController@destroy'
	));
	Route::any('message/online',array(  //上线消息
		'as'=>'message.online',
		'uses'=>'Message\MessageController@online'
	));
	Route::any('message/getOne',array(  //查询单条信息
		'as'=>'message.getOne',
		'uses'=>'Message\MessageController@getOne'
	));
	Route::any('message/addingPreview',array(  //添加预览信息
		'as'=>'message.addingPreview',
		'uses'=>'Message\MessageController@addingPreview'
	));
	Route::any('message/getPreview',array(  //查询单条预览信息
		'as'=>'message.getPreview',
		'uses'=>'Message\MessageController@getPreview'
	));
	
	
        
    //图片风格管理
    Route::any('ImageStyle/index',array( 
        'as'=>'ImageStyle.index',
        'uses'=>'Image\ImageStyleController@index'
    ));
    
    Route::any('ImageStyle/create',array( 
        'as'=>'ImageStyle.create',
        'uses'=>'Image\ImageStyleController@create'
    ));
     
    Route::any('ImageStyle/destroy/{id}',array( 
        'as'=>'ImageStyle.destroy',
        'uses'=>'Image\ImageStyleController@destroy'
    ));
      
    Route::any('ImageStyle/update/{id}',array( 
        'as'=>'ImageStyle.update',
        'uses'=>'Image\ImageStyleController@update'
   ));
       
    Route::any('ImageStyle/show/{id}',array( 
        'as'=>'ImageStyle.show',
        'uses'=>'Image\ImageStyleController@show'
   ));
  
    //赏金单管理-交易管理
	Route::any('bounty/index',array(  //赏金单列表
		'as'=>'bounty.index',
		'uses'=>'Bounty\BountyController@index'
    ));
    
    Route::any('bounty/refundIndex',array(  //赏金单退款列表
		'as'=>'bounty.refundIndex',
		'uses'=>'Bounty\BountyController@index'
    ));
    
    Route::any('bounty/show',array(  //赏金单详情
		'as'=>'bounty.show',
		'uses'=>'Bounty\BountyController@show'
    ));
    
    Route::any('bounty/refundShow',array(  //赏金单退款详情
		'as'=>'bounty.refundShow',
		'uses'=>'Bounty\BountyController@refundShow'
    ));
    
    Route::any('bounty/accept',array(  //赏金单退款通过
		'as'=>'bounty.accept',
		'uses'=>'Bounty\BountyController@accept'
    ));
    
    Route::any('bounty/reaccept',array(  //赏金单重新退款通过
		'as'=>'bounty.reaccept',
		'uses'=>'Bounty\BountyController@accept'
    ));
    
    Route::any('bounty/reject',array(  //赏金单退款拒绝
		'as'=>'bounty.reject',
		'uses'=>'Bounty\BountyController@reject'
    ));
    
    Route::any('bounty/exportBounty',array(  //导出赏金单列表
		'as'=>'bounty.exportBounty',
		'uses'=>'Bounty\BountyController@exportBounty'
    ));
    
    Route::any('bounty/exportRefund',array(  //导出赏金单退款列表
		'as'=>'bounty.exportRefund',
		'uses'=>'Bounty\BountyController@exportRefund'
    ));   
  
    Route::any('requestLog/index',array(  //请求日志列表
		'as'=>'requestLog.index',
		'uses'=>'LoginQuery\LoginQueryController@index'
    ));

    
    Route::any('itemInfo/index',array(  //获取项目列表
    		'as'=>'info.index',
    		'uses'=>'Item\ItemInfoController@index'
    ));
	
	
    Route::any('itemInfo/create',array(  //创建项目
    		'as'=>'itemInfo.create',
    		'uses'=>'Item\ItemInfoController@store'
    ));
	
	Route::any('itemInfo/update',array(  //修改项目
    		'as'=>'itemInfo.update',
    		'uses'=>'Item\ItemInfoController@update'
    ));
	
	Route::any('itemInfo/createSpecialItem',array(  //创建特价项目
    		'as'=>'itemInfo.createSpecialItem',
    		'uses'=>'Item\ItemInfoController@createSpecialItem'
    ));
	
	Route::any('itemInfo/updateSpecialItem',array(  //修改特价项目
    		'as'=>'itemInfo.updateSpecialItem',
    		'uses'=>'Item\ItemInfoController@updateSpecialItem'
    ));
    
    Route::any('stylist/index',array(  //请求造型师列表
        'as'=>'stylist.index',
        'uses'=>'Stylist\StylistController@index'
    ));
     
    Route::any('stylist/show/{id}',array(  //查看造型师
        'as'=>'stylist.show',
        'uses'=>'Stylist\StylistController@show'
    ));
         
    Route::any('stylist/enable/{id}',array(  //启用造型师
        'as'=>'stylist.enable',
        'uses'=>'Stylist\StylistController@enable'
    ));
         
    Route::any('stylist/disabled/{id}',array(  //禁用造型师
         'as'=>'stylist.disabled',
         'uses'=>'Stylist\StylistController@disabled'
    ));
           
           
    Route::any('stylist/destroy/{id}',array(  //删除造型师
        'as'=>'stylist.destroy',
        'uses'=>'Stylist\StylistController@destroy'
    ));
    
    Route::any('stylist/update/{id}',array(  //修改造型师
        'as'=>'stylist.update',
        'uses'=>'Stylist\StylistController@update'

    ));
    Route::any('stylist/create/{id}',array(  //创建造型师
        'as'=>'stylist.create',
        'uses'=>'Stylist\StylistController@create'

    ));

	    
    Route::any('warehouse/index',array(  //项目仓库列表
    'as'=>'warehouse.index',
    'uses'=>'Item\WarehouseController@index'
        ));
    
    Route::any('warehouse/show/{id}',array(  //项目仓库详情
    'as'=>'warehouse.show',
    'uses'=>'ItemController@show'
        ));
    
    Route::any('warehouse/detail/{id}',array(  //项目仓库详情(闲时特价)
        'as'=>'warehouse.detail',
        'uses'=>'OnSaleController@show'
     ));
    
    Route::any('warehouse/destroy',array(  //删除项目
    'as'=>'warehouse.destroy',
    'uses'=>'Item\WarehouseController@destroy'
        ));
    
    Route::any('warehouse/puton',array(  //项目仓库上架
    'as'=>'warehouse.puton',
    'uses'=>'Item\WarehouseController@puton'
        ));
    
    Route::any('warehouse/import',array(  //项目仓库导入
    'as'=>'warehouse.import',
    'uses'=>'Item\WarehouseController@import'
        ));
    Route::any('works/index/{id}',array(  //造型师的作品列表和所在店的其他
    'as'=>'works.index',
    'uses'=>'Stylist\WorksController@index'
        ));
    Route::any('works/del_list/{id}',array(  //删除作品集合
    'as'=>'works.del_list',
    'uses'=>'Stylist\WorksController@del_list'
        ));
    Route::any('works/del/{id}',array(  //删除单个作品
    'as'=>'works.del',
    'uses'=>'Stylist\WorksController@del'
        ));
    Route::any('works/update/{id}',array(  //修改作品集合
    'as'=>'works.update',
    'uses'=>'Stylist\WorksController@update'
        ));
    Route::any('works/create',array(  //新增作品集合
    'as'=>'works.create',
    'uses'=>'Stylist\WorksController@create'
        ));

    Route::any('requestLog/export',array(  //导出日志列表
		'as'=>'requestLog.export',
		'uses'=>'LoginQuery\LoginQueryController@export'
    ));

    
    // 劵
    Route::any('voucher/list',array(  
		'as'=>'voucher.list',
		'uses'=>'VoucherTicket\TicketController@ticketList'
    ));
    Route::any('voucher/invalidStatus/{id}',array( 
		'as'=>'voucher.invalidStatus',
		'uses'=>'VoucherTicket\TicketController@invalidStatus'
    ));
    Route::any('voucher/info/{id}',array( 
		'as'=>'voucher.info',
		'uses'=>'VoucherTicket\TicketController@info'
    ));
    Route::any('voucher/exportTicketList',array( 
		'as'=>'voucher.exportTicketList',
		'uses'=>'VoucherTicket\TicketController@exportTicketList'
    ));
    // 平台活动配置
    Route::any('platform/add',array(  
		'as'=>'platform.add',
		'uses'=>'Promotion\PlatformController@addVoucherConf'
    ));
    
    Route::any('platform/list',array(  
		'as'=>'platform.list',
		'uses'=>'Promotion\PlatformController@confList'
    ));
    Route::any('platform/actView/{id}',array(  
		'as'=>'platform.actView',
		'uses'=>'Promotion\PlatformController@actView'
    ));
    Route::any('platform/getInfo/{id}',array(  
		'as'=>'platform.getInfo',
		'uses'=>'Promotion\PlatformController@getInfo'
    ));
    Route::any('platform/editConf',array(  
		'as'=>'platform.editConf',
		'uses'=>'Promotion\PlatformController@editConf'
    ));
    Route::any('platform/offlineConf/{id}',array(  
		'as'=>'platform.offlineConf',
		'uses'=>'Promotion\PlatformController@offlineConf'
    ));
    Route::any('platform/closeConf/{id}',array(  
		'as'=>'platform.closeConf',
		'uses'=>'Promotion\PlatformController@closeConf'
    ));
    Route::any('platform/upConf/{id}',array(  
		'as'=>'platform.upConf',
		'uses'=>'Promotion\PlatformController@upConf'
    ));
    Route::any('platform/exportList',array(  
		'as'=>'platform.exportList',
		'uses'=>'Promotion\PlatformController@exportList'
    ));
    // 代金劵配置
    Route::any('coupon/add',array(  
		'as'=>'coupon.add',
		'uses'=>'Coupon\CouponController@addConf'
    ));
    Route::any('coupon/list',array(  
		'as'=>'coupon.list',
		'uses'=>'Coupon\CouponController@confList'
    ));
    Route::any('coupon/actView/{id}',array(  
		'as'=>'coupon.actView',
		'uses'=>'Coupon\CouponController@actView'
    ));
    Route::any('coupon/getInfo/{id}',array(  
		'as'=>'coupon.getInfo',
		'uses'=>'Coupon\CouponController@getInfo'
    ));
    Route::any('coupon/editConf',array(  
		'as'=>'coupon.editConf',
		'uses'=>'Coupon\CouponController@editConf'
    ));
    Route::any('coupon/offlineConf/{id}',array(  
		'as'=>'coupon.offlineConf',
		'uses'=>'Coupon\CouponController@offlineConf'
    ));
    Route::any('coupon/closeConf/{id}',array(  
		'as'=>'coupon.closeConf',
		'uses'=>'Coupon\CouponController@closeConf'
    ));
    Route::any('coupon/upConf/{id}',array(  
		'as'=>'coupon.upConf',
		'uses'=>'Coupon\CouponController@upConf'
    ));
    Route::any('coupon/getCoupon/{id}',array(  
		'as'=>'coupon.getCoupon',
		'uses'=>'Coupon\CouponController@getCoupon'
    ));
    Route::any('coupon/exportCoupon/{id}',array(  
		'as'=>'coupon.exportCoupon',
		'uses'=>'Coupon\CouponController@exportCoupon'
    ));
    Route::any('coupon/exportList',array(  
		'as'=>'coupon.exportList',
		'uses'=>'Coupon\CouponController@exportList'
    ));


    //红包活动管理
    Route::any('laisee/create', array(//新增红包活动
        'as' => 'laisee.create',
        'uses' => 'Laisee\LaiseeController@create'
    ));

    Route::any('laisee/update', array(//修改红包活动
        'as' => 'laisee.update',
        'uses' => 'Laisee\LaiseeController@update'
    ));
    Route::any('laisee/index', array(// 红包活动列表
        'as' => 'laisee.index',
        'uses' => 'Laisee\LaiseeController@index'
    ));
    Route::any('laisee/show/{id}', array(// 活动概况
        'as' => 'laisee.show',
        'uses' => 'Laisee\LaiseeController@show'
    ));
     Route::any('laisee/export', array(// 活动列表导出
        'as' => 'laisee.export',
        'uses' => 'Laisee\LaiseeController@export'
    ));
     
    Route::any('laisee/online/{id}', array(// 活动上线
        'as' => 'laisee.online',
        'uses' => 'Laisee\LaiseeController@online'
    ));
    Route::any('laisee/offline/{id}', array(// 活动下线
        'as' => 'laisee.offline',
        'uses' => 'Laisee\LaiseeController@offline'
    ));
    Route::any('laisee/close/{id}', array(// 活动关闭
        'as' => 'laisee.close',
        'uses' => 'Laisee\LaiseeController@close'
    ));
    Route::any('laisee/itemTypes', array(// 现金券可使用项目类型列表
        'as' => 'laisee.itemTypes',
        'uses' => 'Laisee\LaiseeController@itemTypes'
    ));
    
    Route::any('bonus/index', array(// 红包列表
        'as' => 'bonus.index',
        'uses' => 'Laisee\BonusController@index'
    ));
    Route::any('bonus/export', array(// 红包列表导出
        'as' => 'bonus.export',
        'uses' => 'Laisee\BonusController@export'
    ));
    Route::any('bonus/show/{id}', array(// 红包详情
        'as' => 'bonus.show',
        'uses' => 'Laisee\BonusController@show'
    ));
    Route::any('/bonus/close/{id}', array(// 红包失效 
        'as' => 'bonus.close',
        'uses' => 'Laisee\BonusController@close'
    ));
    
    //消息盒子
    Route::any('messageBox/getCompanyCode',array(  
        'as'=>'messageBox.getCompanyCode',
        'uses'=>'MessageBox\MessageBoxController@getCompanyCode'
    ));
    Route::any('messageBox/getActivityCode',array(  
        'as'=>'messageBox.getActivityCode',
        'uses'=>'MessageBox\MessageBoxController@getActivityCode'
    ));
    Route::any('messageBox/getAllTown',array(  
        'as'=>'messageBox.getAllTown',
        'uses'=>'MessageBox\MessageBoxController@getAllTown'
    ));
    Route::any('messageBox/addSalon',array(  
        'as'=>'messageBox.addSalon',
        'uses'=>'MessageBox\MessageBoxController@addSalon'
    ));
    Route::any('messageBox/addPushConf',array(  
        'as'=>'messageBox.addPushConf',
        'uses'=>'MessageBox\MessageBoxController@addPushConf'
    ));
    Route::any('messageBox/messageList',array(  
        'as'=>'messageBox.messageList',
        'uses'=>'MessageBox\MessageBoxController@messageList'
    ));
    Route::any('messageBox/delMessage',array(  
        'as'=>'messageBox.delMessage',
        'uses'=>'MessageBox\MessageBoxController@delMessage'
    ));
    Route::any('messageBox/showMessage',array(  
        'as'=>'messageBox.showMessage',
        'uses'=>'MessageBox\MessageBoxController@showMessage'
    ));
    Route::any('messageBox/editMessage',array(  
        'as'=>'messageBox.editMessage',
        'uses'=>'MessageBox\MessageBoxController@editMessage'
    ));
    Route::any('messageBox/dailyMessagePush',array(  
        'as'=>'messageBox.dailyMessagePush',
        'uses'=>'MessageBox\MessageBoxController@dailyMessagePush'
    ));  
    Route::any('messageBox/showDailyMessage',array(  
        'as'=>'messageBox.showDailyMessage',
        'uses'=>'MessageBox\MessageBoxController@showDailyMessage'
    )); 
	
	//韩式定妆项目
	Route::any('beautyItem/index',array(  
        'as'=>'beautyItem.index',
        'uses'=>'Item\BeautyItemController@index'
    )); 
	
	Route::any('beautyItem/update',array(  
        'as'=>'beautyItem.update',
        'uses'=>'Item\BeautyItemController@update'
    )); 
	
	Route::any('beautyItem/show',array(  
        'as'=>'beautyItem.show',
        'uses'=>'Item\BeautyItemController@show'
    ));
    

	Route::any('beautyItem/updateFashion',array(  
        'as'=>'beautyItem.updateFashion',
        'uses'=>'Item\BeautyItemController@updateFashion'
    ));
	
	Route::any('beautyItem/indexFashion',array(  
        'as'=>'beautyItem.indexFashion',
        'uses'=>'Item\BeautyItemController@indexFashion'
    ));
	
	Route::any('beautyItem/showFashion',array(  
        'as'=>'beautyItem.showFashion',
        'uses'=>'Item\BeautyItemController@showFashion'
    ));

    //系统配置
    
    // 用户中心 用户评论
    Route::any('comment/index',array(  
        'as'=>'comment.index',
        'uses'=>'Item\CommentController@index'
    ));
    Route::any('comment/show/{id}',array(  
        'as'=>'comment.show',
        'uses'=>'Item\CommentController@show'
    ));
    Route::any('comment/hidden/{id}',array(  
        'as'=>'comment.hidden',
        'uses'=>'Item\CommentController@hidden'
    ));
    Route::any('comment/delete/{id}',array(  
        'as'=>'comment.delete',
        'uses'=>'Item\CommentController@delete'
    ));

    
    
    //黑名单
    Route::any('blacklist/phoneIndex',array(  
        'as'=>'blacklist.phoneIndex',
        'uses'=>'SystemConfig\BlacklistController@phoneIndex'
    )); 
    Route::any('blacklist/deviceIndex',array(  
        'as'=>'blacklist.deviceIndex',
        'uses'=>'SystemConfig\BlacklistController@deviceIndex'
    )); 
    Route::any('blacklist/openidIndex',array(  
        'as'=>'blacklist.openidIndex',
        'uses'=>'SystemConfig\BlacklistController@openidIndex'
    )); 
    
    
    Route::any('blacklist/phoneExport',array(  
        'as'=>'blacklist.phoneExport',
        'uses'=>'SystemConfig\BlacklistController@phoneExport'
    )); 
    Route::any('blacklist/deviceExport',array(  
        'as'=>'blacklist.deviceExport',
        'uses'=>'SystemConfig\BlacklistController@deviceExport'
    )); 
    Route::any('blacklist/openidExport',array(  
        'as'=>'blacklist.openidExport',
        'uses'=>'SystemConfig\BlacklistController@openidExport'
    )); 
    
    
    Route::any('blacklist/phoneUpload',array(
		'as'=>'blacklist.phoneUpload',
		'uses'=>'SystemConfig\BlacklistController@phoneUpload'
	));	
    Route::any('blacklist/deviceUpload',array(
		'as'=>'blacklist.deviceUpload',
		'uses'=>'SystemConfig\BlacklistController@deviceUpload'
	));
    Route::any('blacklist/openidUpload',array(
		'as'=>'blacklist.openidUpload',
		'uses'=>'SystemConfig\BlacklistController@openidUpload'
	));
    
    
    Route::any('blacklist/phoneSubmit',array(
		'as'=>'blacklist.phoneSubmit',
		'uses'=>'SystemConfig\BlacklistController@phoneSubmit'
	));	
    Route::any('blacklist/deviceSubmit',array(
		'as'=>'blacklist.deviceSubmit',
		'uses'=>'SystemConfig\BlacklistController@deviceSubmit'
	));	
    Route::any('blacklist/openidSubmit',array(
		'as'=>'blacklist.openidSubmit',
		'uses'=>'SystemConfig\BlacklistController@openidSubmit'
	));	
    
    
    Route::any('blacklist/phoneRemove/{id}',array(
		'as'=>'blacklist.phoneRemove',
		'uses'=>'SystemConfig\BlacklistController@phoneRemove'
	));	
    Route::any('blacklist/deviceRemove/{id}',array(
		'as'=>'blacklist.deviceRemove',
		'uses'=>'SystemConfig\BlacklistController@deviceRemove'
	));	
    Route::any('blacklist/openidRemove/{id}',array(
		'as'=>'blacklist.openidRemove',
		'uses'=>'SystemConfig\BlacklistController@openidRemove'
	));	
    
    //预警查询
    
    Route::any('warning/phoneIndex',array(  
        'as'=>'warning.phoneIndex',
        'uses'=>'SystemConfig\WarningController@phoneIndex'
    )); 
    Route::any('warning/deviceIndex',array(  
        'as'=>'warning.deviceIndex',
        'uses'=>'SystemConfig\WarningController@deviceIndex'
    )); 
    Route::any('warning/openidIndex',array(  
        'as'=>'warning.openidIndex',
        'uses'=>'SystemConfig\WarningController@openidIndex'
    )); 
    
    
    Route::any('warning/phoneExport',array(  
        'as'=>'warning.phoneExport',
        'uses'=>'SystemConfig\WarningController@phoneExport'
    )); 
    Route::any('warning/deviceExport',array(  
        'as'=>'warning.deviceExport',
        'uses'=>'SystemConfig\WarningController@deviceExport'
    )); 
    Route::any('warning/openidExport',array(  
        'as'=>'warning.openidExport',
        'uses'=>'SystemConfig\WarningController@openidExport'
    )); 
    
    Route::any('warning/deviceBlock',array(  
        'as'=>'warning.deviceBlock',
        'uses'=>'SystemConfig\WarningController@deviceBlock'
    )); 
    Route::any('warning/phoneBlock',array(  
        'as'=>'warning.phoneBlock',
        'uses'=>'SystemConfig\WarningController@phoneBlock'
    )); 
    Route::any('warning/openidBlock',array(  
        'as'=>'warning.openidBlock',
        'uses'=>'SystemConfig\WarningController@openidBlock'
    )); 

	
	// 专家
    Route::any('artificer/index',array(  
        'as'=>'artificer.index',
        'uses'=>'Artificer\ArtificerController@index'
    )); 
    Route::any('artificer/add',array(  
        'as'=>'artificer.add',
        'uses'=>'Artificer\ArtificerController@add'
    )); 
    Route::any('artificer/update',array(  
        'as'=>'artificer.update',
        'uses'=>'Artificer\ArtificerController@save'
    )); 
    Route::any('artificer/up/{id}',array(  
        'as'=>'artificer.up',
        'uses'=>'Artificer\ArtificerController@start'
    )); 
    Route::any('artificer/down/{id}',array(  
        'as'=>'artificer.down',
        'uses'=>'Artificer\ArtificerController@close'
    )); 
    Route::any('artificer/export',array(  
        'as'=>'artificer.export',
        'uses'=>'Artificer\ArtificerController@export'
    )); 
    Route::any('artificer/show/{id}',array(  
        'as'=>'artificer.show',
        'uses'=>'Artificer\ArtificerController@show'
    )); 
    Route::any('artificer/worksCreate',array(  
        'as'=>'artificer.worksCreate',
        'uses'=>'Artificer\ArtificerController@uploadWorks'
    ));  
    Route::any('artificer/updateIds',array(  
        'as'=>'artificer.updateIds',
        'uses'=>'Artificer\ArtificerController@updateWorks'
    ));  
    Route::any('artificer/updateDesc',array(  
        'as'=>'artificer.updateDesc',
        'uses'=>'Artificer\ArtificerController@updateWorksDesc'
    ));
    Route::any('artificer/workIndex/{id}',array(  
        'as'=>'artificer.workIndex',
        'uses'=>'Artificer\ArtificerController@showWorks'
    ));
	
    // 专家助理
    Route::any('assistant/index',array(  
        'as'=>'assistant.index',
        'uses'=>'Artificer\ArtificerAssistantController@index'
    )); 
    Route::any('assistant/add',array(  
        'as'=>'assistant.add',
        'uses'=>'Artificer\ArtificerAssistantController@add'
    )); 
    Route::any('assistant/update',array(  
        'as'=>'assistant.update',
        'uses'=>'Artificer\ArtificerAssistantController@save'
    )); 
    Route::any('assistant/up/{id}',array(  
        'as'=>'assistant.up',
        'uses'=>'Artificer\ArtificerAssistantController@start'
    ));
    Route::any('assistant/down/{id}',array(  
        'as'=>'assistant.down',
        'uses'=>'Artificer\ArtificerAssistantController@close'
    ));
    Route::any('assistant/export',array(  
        'as'=>'assistant.export',
        'uses'=>'Artificer\ArtificerAssistantController@export'
    )); 
    Route::any('assistant/show/{id}',array(  
        'as'=>'assistant.show',
        'uses'=>'Artificer\ArtificerAssistantController@show'
    ));
    
    // 其他人员
    Route::any('others/index',array(  
        'as'=>'others.index',
        'uses'=>'Beauty\OthersController@index'
    )); 
    Route::any('others/add',array(  
        'as'=>'others.add',
        'uses'=>'Beauty\OthersController@add'
    )); 
    Route::any('others/update',array(  
        'as'=>'others.update',
        'uses'=>'Beauty\OthersController@save'
    )); 
    Route::any('others/up/{id}',array(  
        'as'=>'others.up',
        'uses'=>'Beauty\OthersController@start' 
    ));
    Route::any('others/down/{id}',array(  
        'as'=>'others.down',
        'uses'=>'Beauty\OthersController@close'
    ));
    Route::any('others/export',array(  
        'as'=>'others.export',
        'uses'=>'Beauty\OthersController@export'
    )); 
    Route::any('others/show/{id}',array(  
        'as'=>'others.show',
        'uses'=>'Beauty\OthersController@show'
    ));
        
    
    //韩式定妆
    Route::any('banner/index',array(  //
        'as'=>'banner.index',
        'uses'=>'Banner\BannerController@index'
    )); 
    Route::any('banner/createOrSave',array(  //
        'as'=>'banner.createOrSave',
        'uses'=>'Banner\BannerController@createOrSave'
    )); 
    Route::any('banner/create',array(  //
        'as'=>'banner.create',
        'uses'=>'Banner\BannerController@create'
    )); 
    Route::any('banner/edit/{id}',array(  //
        'as'=>'banner.edit',
        'uses'=>'Banner\BannerController@edit'
    )); 
    Route::any('banner/destroy/{id}',array(  //
        'as'=>'banner.destroy',
        'uses'=>'Banner\BannerController@destroy'
    )); 
    Route::any('banner/sort',array(  //
        'as'=>'banner.sort',
        'uses'=>'Banner\BannerController@sort'
    )); 
    
    //定妆中心
    Route::any('beauty/index',array(  
        'as'=>'beauty.index',
        'uses'=>'Beauty\BeautyController@index'
    )); 
    Route::any('beauty/edit',array(  
        'as'=>'beauty.edit',
        'uses'=>'Beauty\BeautyController@edit'
    )); 
    Route::any('beauty/delete',array(  
        'as'=>'beauty.delete',
        'uses'=>'Beauty\BeautyController@delete'
    )); 
    
    //定妆活动
    Route::any('powderArticles/addArticles',array(  
        'as'=>'powderArticles.addArticles',
        'uses'=>'Powder\PowderArticlesController@addArticles'
    )); 
    Route::any('powderArticles/articlesList',array(  
        'as'=>'powderArticles.articlesList',
        'uses'=>'Powder\PowderArticlesController@articlesList'
    ));
    Route::any('powderArticles/showArticlesInfo',array(  
        'as'=>'powderArticles.showArticlesInfo',
        'uses'=>'Powder\PowderArticlesController@showArticlesInfo'
    )); 
    Route::any('powderArticles/switchArticles',array(  
        'as'=>'powderArticles.switchArticles',
        'uses'=>'Powder\PowderArticlesController@switchArticles'
    ));
    Route::any('powderArticles/switchVerifyArticles',array(  
        'as'=>'powderArticles.switchVerifyArticles',
        'uses'=>'Powder\PowderArticlesController@switchVerifyArticles'
    ));
    Route::any('powderArticles/presentList',array(  
        'as'=>'powderArticles.presentList',
        'uses'=>'Powder\PowderArticlesController@presentList'
    ));
    Route::any('powderArticles/presentListInfo',array(  
        'as'=>'powderArticles.presentListInfo',
        'uses'=>'Powder\PowderArticlesController@presentListInfo'
    ));
    Route::any('powderArticles/usePresentTicket',array(  
        'as'=>'powderArticles.usePresentTicket',
        'uses'=>'Powder\PowderArticlesController@usePresentTicket'
    ));
    Route::any('powderArticles/editPresentUseInfo',array(  
        'as'=>'powderArticles.editPresentUseInfo',
        'uses'=>'Powder\PowderArticlesController@editPresentUseInfo'
    ));
    Route::any('powderArticles/articlesTicketList',array(  
        'as'=>'powderArticles.articlesTicketList',
        'uses'=>'Powder\PowderArticlesController@articlesTicketList'
    ));
    Route::any('powderArticles/exportArticlesTicketList',array(  
        'as'=>'powderArticles.exportArticlesTicketList',
        'uses'=>'Powder\PowderArticlesController@exportArticlesTicketList'
    ));
    
        //定妆单退款
    Route::any('beautyrefund/index',array(  //定妆单退款列表
        'as'=>'beautyrefund.index',
        'uses'=>'Transaction\BeautyRefundController@index'
    )); 
    Route::any('beautyrefund/show/{id}',array(  //定妆单退款详情
        'as'=>'beautyrefund.show',
        'uses'=>'Transaction\BeautyRefundController@show'
    )); 
    
    Route::any('beautyrefund/reject',array(  //定妆单 拒绝退款
        'as'=>'beautyrefund.reject',
        'uses'=>'Transaction\BeautyRefundController@reject'
    )); 
    
    Route::any('beautyrefund/accept',array(  //定妆单 确认退款
        'as'=>'beautyrefund.accept',
        'uses'=>'Transaction\BeautyRefundController@accept'
    )); 
    
    // 预约日历
    Route::any('calendar/index',array(  
	    'as'=>'calendar.index',
	    'uses'=>'BookCalendar\CalendarController@index'
    ));
    
    Route::any('calendar/getDay',array( 
	    'as'=>'calendar.getDay',
	    'uses'=>'BookCalendar\CalendarController@getDayIndex'
    ));
    
    Route::any('calendar/status/{orderSn}',array( 
	    'as'=>'calendar.status',
	    'uses'=>'BookCalendar\CalendarController@modifyDayStatus'
    ));
    
    Route::any('calendar/modifyDay',array(  
	    'as'=>'calendar.modifyDay',
	    'uses'=>'BookCalendar\CalendarController@modifyDayIndex'
    ));
    Route::any('calendar/modifyLimit',array(  
	    'as'=>'calendar.modifyLimit',
	    'uses'=>'BookCalendar\CalendarController@setCalendar'
    ));
    Route::any('calendar/limit',array(  
	    'as'=>'calendar.limit',
	    'uses'=>'BookCalendar\CalendarController@indexLimit'
    ));
    Route::any('calendar/export',array(  
	    'as'=>'calendar.export',
	    'uses'=>'BookCalendar\CalendarController@exportDayIndex'
    ));
});
