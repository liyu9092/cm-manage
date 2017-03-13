<?php
namespace App\Exceptions;

class ERROR
{
    ////////////系统模块//////////////
    /**
     *@param token无效
     */
     CONST TOKEN_INVILD =  -40000;
     
     /**
     *@param token过期
     */
     CONST TOKEN_EXPIRED =  -40001;

     /**
     *@param 未授权访问
     */
     CONST UNAUTHORIZED =  -40002;
     
     /**
      *@param 参数丢失
      */
     CONST PARAMS_LOST =  -40003;
     
     /**
      *@param 功能关闭
      */
     CONST SERVER_STOPED =  -40004;
     
     /**
      *@param 未知错误
      */
     CONST UNKNOWN_ERROR =  -40005;
     
     /**
      * @param 上传的文件丢失
      */
     CONST UPLOAD_FILE_LOST = -40006;
     
     /**
      * @param 上传的文件后缀名不正确
      */
     CONST UPLOAD_FILE_ERR_EXTENSION = -40007;

     /*
      *@param 获取不到配置信息
      */
     CONST CONFIG_LOST =  -40009;



     /**
      * @param 上传的文件格式不正确
      */
     CONST UPLOAD_FILE_ERR_FORMAT = -40008;
     
    ////////////用户模块//////////////
     /**
     *@param 用户名已存在
     */
     CONST USER_EXIST =  -50001;     

     /**
     *@param 用户创建失败
     */
     CONST USER_CREATE_FAILED =  -50002;     

     /**
     *@param 用户更新失败
     */
     CONST USER_UPDATE_FAILED =  -50003;     

     /**
     *@param 用户密码错误
     */
     CONST USER_PASSWORD_ERROR =  -50004;

     /**
     *@param 用户不存在
     */
     CONST USER_NOT_FOUND =  -50005;

    /**
     *@param 邀请码不存在
     */
     CONST CODE_NOT_FOUND =  -50006;

     /**
     *@param 存在未完成订单
     */
     CONST UNFINISHED_ORDER_EXIST =  -50007;



    ////////////返佣单模块//////////////
     /**
     *@param 返佣单创建失败
     */
     CONST REBATE_CREATE_FAILED =  -50100;
     

     /**
     *@param 返佣单更新失败
     */
     CONST REBATE_UPDATE_FAILED =  -50101;     

     /**
     *@param 返佣单确认失败
     */
     CONST REBATE_CONFIRM_FAILED =  -50102;     

     /**
     *@param 返佣单导入失败
     */
     CONST REBATE_UPLOAD_FAILED =  -50103;

     /**
     *@param 返佣单删除失败
     */
     CONST REBATE_DELETE_FAILED =  -50104; 

     /**
     *@param 未知返佣单
     */
     CONST REBATE_NOT_FOUND =  -50105;     

     /**
     *@param 未指定返佣单
     */
     CONST REBATE_NOT_DEFINED =  -50106;






     ////////////权限模块//////////////
     /**
     *@param 权限创建失败
     */
     CONST PERMISSION_CREATE_FAILED =  -50200;

     /**
     *@param 权限更新失败
     */
     CONST PERMISSION_UPDATE_FAILED =  -50201;


     ////////////角色模块//////////////
     /**
     *@param 角色名已存在
     */
     CONST ROLE_EXIST =  -50300;

     /**
     *@param 角色更新失败
     */
     CONST ROLE_CREATE_FAILED =  -50301;     

     /**
     *@param 角色更新失败
     */
     CONST ROLE_UPDATE_FAILED =  -50302;



     ////////////INDEX模块//////////////
     /**
     *@param 验证码错误
     */
     CONST CAPTCHA_ERROR =  -50400;

     /**
     *@param 当前帐户已停用或注销
     */
     CONST ACCOUNT_INVALID =  -50401;     

     /**
     *@param 用户名或密码错误
     */
     CONST LOGIN_FAILED =  -50402;     

     /**
     *@param 用户名或密码错误
     */
     CONST LOGOUT_FAILED =  -50403;


     ////////////佣金单模块//////////////
     /**
     *@param 未知佣金单
     */
     CONST COMMISSION_NOT_FOUND =  -50500;



     ////////////上传模块//////////////
     /**
     *@param 文件为空
     */
     CONST FILE_EMPTY =  -50507;

     /**
     *@param 文件格式错误
     */
     CONST FILE_FORMAT_ERROR =  -50508;
     
     ////////////交易管理相关///////////////
     
     /**
      * 订单不存在
      * @var unknown
      */
     CONST ORDER_NOT_EXIST = -50601;
     
     /**
      * 臭美券不存在
      * @var unknown
      */
     CONST TICKET_NOT_EXIST = -50602;
     
     /**
      * 退款单不存在
      * @var unknown
      */
     CONST REFUND_NOT_EXIST = -50603;
     
     /**
      * 订单项目不存在
      * @var unknown
      */
     CONST ORDER_ITEM_NOT_EXIST = -50604;
     
     /**
      * 退款单状态不正常
      * @var unknown
      */
     CONST REFUND_STATE_WRONG = -50605;
     
     /**
      * 退款单找不到支付流水信息
      * @var unknown
      */
     CONST REFUND_FLOW_LOST= -50606;
     
     /**
      * 退款单找不到臭美券信息
      * @var unknown
      */
     CONST REFUND_TICKET_LOST= -50607;
     
     /**
      * 退款单找不到臭美券信息
      * @var unknown
      */
     CONST REFUND_TICKET_STATE_WRONG= -50608;
     
     /**
      * 退款单找不到paymentlog信息
      * @var unknown
      */
     CONST REFUND_PAYMENT_LOG_LOST = -50609;
     
     /**
      * 退款关键信息不全
      * @var unknown
      */
     CONST REFUND_LOST_PRIMARY_INFO = -50610;
     
     /**
      * 订单状态不正确
      * @var unknown
      */
     CONST ORDER_STATUS_WRONG = -50611;
     
     /**
      * 赏金单状态不正确
      * @var unknown
      */
     CONST BOUNTY_STATUS_WRONG = -50612;

     
     /**
      * 退款单找不大流水号
      * @var unknown
      */
     CONST REFUND_CANT_FIND_TN = -50613;
     
     /**
      * 赏金单没有id传值
      */
     CONST BOUNTY_ID_NOT_PASS = -50614;
     
     /**
      * 找不到赏金单
      * @var unknown
      */
     CONST BOUNTY_NOT_FOUND = -50615;
     
     /**
      * 赏金单搜索无此类别关键词
      * @var unknown
      */
     CONST BOUNTY_SEARCH_KEYWORD_WRONG = -50616;
     
     /**
      * 赏金单搜索暂不支持该支付方式搜索
      * @var unknown
      */
     CONST BOUNTY_SEARCH_PAYTYPE_WRONG = -50617;
     
     /**
      * 赏金单搜索付款状态不正确
      * @var unknown
      */
     CONST BOUNTY_SEARCH_ISPAY_WRONG = -50618;
     
     /**
      * 赏金单搜索暂不支持该赏金单状态搜索
      * @var unknown
      */
     CONST BOUNTY_SEARCH_BTSTATUS_WRONG = -50619;
     
     /**
      * 赏金单查询退款状态不正确
      * @var unknown
      */
     CONST BOUNTY_SEARCH_REFUNDSTATUS_WRONG = -50620;
     
     /**
      * 拒接退款需要理由
      * @var unknown
      */
     CONST BOUNTY_REJECT_NOREASON = -50621;
	 
	 
	 ////////////消息模块//////////////
     /**
     *@param 参数错误
     */
     CONST PARAMETER_ERROR =  -50900;
	 /**
     *@param 更新失败
     */
     CONST UPDATE_FAILED =  -50901;
	 /**
     *@param 消息ID不正确
     */
     CONST MESSAGE_ID_IS_ERROR  =  -50902;
     
     
     	 ////////////消息盒子模块//////////////
     /**
     *@param 参数错误
     */
     CONST MessageBox_PARAMETER_ERROR =  -52000;
	 /**
     *@param 更新失败
     */
     CONST MessageBox_UPDATE_FAILED =  -52001;
      /**
     *@param 添加失败
     */
     CONST MessageBox_ADD_FAILED =  -52002;
     
     ////////////系统配置预警查询黑名单模块//////////////
     /**
     *@param keywordType没有传递
     */
     CONST Warning_KeywordType_Notfound =  -53000;
	 /**
     *@param 黑名单已存在
     */
     CONST Blacklist_Exist =  -53001;
      /**
     *@param RedisKey没有传递
     */
     CONST Blacklist_RedisKey_Notfound =  -53002;
     /**
     *@param 黑名单提交失败
     */
     CONST Blacklist_UPLOAD_FAILED =  -53003;
	 /**
     *@param 找不到id
     */
     CONST Blacklist_Id_Notfound =  -53004;
      /**
     *@param 移除黑名单失败
     */
     CONST Blacklist_Remove_FAILED =  -53005;
     /**
     *@param keywordType没有传递
     */
     CONST Blacklist_KeywordType_Notfound =  -53006;
     /**
     *@param 移入黑名单失败
     */
     CONST Blacklist_Block_FAILED =  -53007;
     

	 ////////////图片风格模块//////////////
     /**
     *@param 插入风格失败
     */
     CONST STYLE_CREATE_FAILED =  -51200;
	 /**
     *@param 更新风格失败
     */
     CONST STYLE_UPDATE_FAILED =  -51201;
      /**
     *@param 删除风格失败
     */
     CONST STYLE_DELETE_FAILED  =  -51202;	 

     /**
     *@param 未知图片
     */
     CONST STYLE_NOT_FOUND  =  -51203;


     ////////////用户等级模块//////////////
     /**
     *@param 等级设置为空
     */
     CONST LEVEL_EMPTY =  -50600;

    /**
     *@param 等级更新失败
     */
     CONST LEVEL_UPDATE_FAILED =  -50601;

    /**
     *@param 未知等级
     */
     CONST LEVEL_NOT_FOUND =  -50602;



     ////////////反馈模块//////////////
     /**
     *@param 未知反馈
     */
     CONST FEED_NOT_FOUND =  -50700;

     /**
     *@param 反馈删除失败
     */
     CONST FEED_DELETE_FAILED =  -50701;


     /**
     *@param 反馈为空
     */
     CONST FEED_EMPTY =  -50702;

     
     ////////////收款模块//////////////
     /**
      *@param 参数错误
      */
     CONST RECEIVABLES_ERROR =  -50800;
     /**
      *@param 更新失败
      */
     CONST RECEIVABLES_UPDATE_FAILED =  -50801;
     /**
      *@param ID不正确
      */
     CONST RECEIVABLES_ID_IS_ERROR  =  -50802;
     
      
     ////////////店铺商户操作模块//////////////
     /**
      *@param 参数错误
      */
     CONST MERCHANT_ERROR =  -51000;
     /**
      *@param 更新失败
      */
     CONST MERCHANT_UPDATE_FAILED =  -51001;
     /**
      *@param ID不正确
      */
     CONST MERCHANT_ID_IS_ERROR  =  -51002;
     /**
      *@param 编号重复已经存在
      */
     CONST MERCHANT_SN_IS_ERROR  =  -51003;
     /**
      *@param 该商户还有正在合作的店铺请先终止该商户所有店铺合作，再删除商户
      */
     CONST MERCHANT_STATUS_IS_ERROR  =  -51004;
     /**
      *@param 该用户名重复，请重新修改
      */
     CONST MERCHANT_ACCOUNT_NAME_IS_ERROR  =  -51010;
     /**
      *@param 店铺账号冲突（当前店铺已存在普通用户（或超级管理员），请查询）
      */
     CONST MERCHANT_ACCOUNT_CONFLICT_IS_ERROR  =  -51011;
     /**
      *@param 店铺状态错误
      */
     CONST MERCHANT_SALON_STATUS_IS_ERROR  =  -51020;
     /**
      *@param 删除造型师错误
      */
     CONST MERCHANT_STYLIST_DELETE_ERROR  =  -51021;
      /**
      *@param 启用造型师错误
      */
     CONST MERCHANT_STYLIST_ENABLE_ERROR  =  -51022; 
      /**
      *@param 禁用造型师错误
      */
     CONST MERCHANT_STYLIST_DESABLED_ERROR  =  -51023; 
      /**
      *@param 未选择修改所属店铺
      */
     CONST MERCHANT_STYLIST_SELECT_ERROR  =  -51024; 
      /**
      *@param 你有已接单未完成打赏的悬赏单
      */
     CONST MERCHANT_STYLIST_NOREWARD_ERROR  =  -51025; 
      /**
      *@param 修改造型师错误
      */
     CONST MERCHANT_STYLIST_UPDATE_ERROR  =  -51026; 
      /**
      *@param 店铺名称不存在
      */
     CONST MERCHANT_NOTNAME_ERROR  =  -51027; 
      /**
      *@param 没有所属商户
      */
     CONST MERCHANT_NOT_MERCHANT_ERROR  =  -51028; 
      /**
      *@param 店铺ID出错
      */
     CONST MERCHANT_STYLIST_ID_ERROR  =  -51029; 
      /**
      *@param 创建造型师错误
      */
     CONST MERCHANT_STYLIST_CREATE_ERROR  =  -51030; 
      /**
      *@param 手机号码重复
      */
     CONST MERCHANT_MOBILEPHONE_ERROR  =  -51031; 
           /**
      *@param 作品ID出错
      */
     CONST MERCHANT_WORKS_ID_ERROR  =  -51032; 
      /**
      *@param 删除作品失败
      */
     CONST MERCHANT_WORKS_DELETE_ERROR  =  -51033; 
      /**
      *@param 修改作品失败
      */
     CONST MERCHANT_WORKS_SAVE_ERROR  =  -51034; 
      /**
      *@param 创建作品失败
      */
     CONST MERCHANT_WORKS_CREATE_ERROR  =  -51035; 
          
       
     ////////////项目模块//////////////
     /**
      *@param 参数错误
      */
     CONST ITEM_ERROR =  -51100;
     /**
      *@param 当前快剪等级下面无对应等级的造型师，请修改造型师界面中的快剪等级后再添加快剪项目
      */
     CONST ITEM_GRADE_ERROR =  -51101;
     /**
      *@param 项目有效期时间不正确
      */
     CONST ITEM_EXPTIME_ERROR =  -51102;
     /**
      *@param 限制次数不正确
      */
     CONST ITEM_RESTRICT_ERROR =  -51103;
     /**
      *@param 项目id不存在
      */
     CONST ITEM_DATA_ERROR =  -51104;
     /**
      *@param 项目总库存不正确
      */
     CONST ITEM_TOTALREP_ERROR =  -51105;



     ////////////APP店铺配置模块//////////////
     /**
      *@param 请输入有效积分数值
      */
     CONST STAR_CONF_SCORE_IS_ERROR=-51100;
     /**
      *@param 更新星级积分失败
      */
     CONST STAR_CONF_UPDATE_IS_ERROR=-51101;
     /**
      *@param 未找到相应的星级积分等级
      */
     CONST STAR_CONF_LEVEL_IS_ERROR=-51102;
     /**
      *@param 未找到该店铺信息
      */
      CONST STAR_CONF_SALON_INFO_IS_ERROR=-51103;
      /**
      *@param 修改店铺积分失败
      */
      CONST STAR_CONF_UPDATE_SALON_SCORE_FAILED=-51104;
      /**
      *@param 未找到该评分类型
      */
      CONST SCORE_CONF_FAILED=-51105;
      /**
      *@param 更新评分分值配置失败
      */
      CONST SCORE_CONF_UPDATE_FAILED=-51106;
     

     

     /////////////付款单相关//////////////////
     /**
      * @param 提现单原数据不存在
      */
     CONST PAY_WITHDRAW_NOT_EXIST = -51300;
     
     /**
      * @param 提现单原状态不正确
      */
     CONST PAY_WITHDRAW_WRONG_STATE = -51301;



     ////////////活动管理模块//////////////
     /**
      *@param 活动不存在
      */
     CONST PROMOTION_NOT_FOUND=-51200;

     /**
      *@param 活动下线失败
      */
     CONST PROMOTION_OFFLINE_FAILED=-51201;

     /**
      *@param 活动关闭失败
      */
     CONST PROMOTION_CLOSED_FAILED=-51202;


     ////////////项目仓库//////////////
     /**
      *@param 项目不存在或者状态有误
      */
     CONST ITEM_LOST_OR_WRONG_STATE =  -51200;
     
     /**
      *@param 项目有效期有误
      */
     CONST ITEM_WRONG_EXP_TIME =  -51201;
     

     /**
      *@param 项目库存有误
      */
     CONST ITEM_WRONG_TOTAL_REQ =  -51202;
     
     /**
      *@param 项目价格有误
      */
     CONST ITEM_WRONG_PRICE =  -51207;

     /**
      *@param 项目不存在
      */
     CONST ITEM_NOT_FOUND =  -51106;
     
     
      
     ////////////韩式定妆活动模块//////////////
     /***
      * @param 活动添加失败
      */
     CONST POWDER_ARTICLE_ADD_FIELD = 51300;
     
      /***
      * @param 活动名称已存在
      */
     CONST POWDER_ARTICLE_NAME_EXIST = 51301;
     
     /***
      * @param 活动开关失败
      */
     CONST POWDER_ARTICLE_SWITCH_STATUS= 51302;
     
     /***
      * @param 活动验证开关失败
      */
     CONST POWDER_ARTICLE_SWITCH_VERIFY_STATUS= 51303;

     
     
     ////////////韩式定妆项目/////////////
     /**
      *@param 参数不全
      */
     CONST BEAUTY_ITEM_ERROR =  -51500;
      
     /**
      *@param 更新失败
      */
     CONST BEAUTY_ITEM_UPDATE_FAIL =  -51501;
      
     
     /**
      *@param 项目价格有误
      */
     CONST BEAUTY_ITEM_WRONG_PRICE =  -51502;
     
     /**
      *@param 项目不存在
      */
     CONST BEAUTY_ITEM_NOT_FOUND =  -51503;
     

     /**
      *@param 项目名称重复
      */
     CONST BEAUTY_ITEM_NAME_REOEAT =  -51504;
     
     /**
      *@param banner的ID不存在
      */
     CONST BEAUTY_BANNER_NOT_ID =  -51505;
     
     /**
      *@param banner的查询错误
      */
     CONST BEAUTY_BANNER_SELECT_ERROR =  -51506;
     
     /**
      *@param banner的创建错误
      */
     CONST BEAUTY_BANNER_CREATE_ERROR =  -51507;
     
     /**
      *@param banner的修改错误
      */
     CONST BEAUTY_BANNER_UPDATE_ERROR =  -51508;
     
     /**
      *@param banner的删除错误
      */
     CONST BEAUTY_BANNER_DELETE_ERROR =  -51509;
     
     /**
      *@param 专家编辑的名称重复
      */
     CONST ARTIFICER_NAME_EXISTS_ERROR =  -51510;
     
      ////////////定妆中心/////////////
     /**
      *@param 参数不全
      */
     CONST Beauty_DATA_NOTFOUND =  -51800;
      
     /**
      *@param data格式不是json
      */
     CONST Beauty_DATA_ISNOTJSON =  -51801;
}