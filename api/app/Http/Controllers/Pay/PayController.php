<?php
/**
*财务  付款管理
*/
namespace App\Http\Controllers\Pay;

use App\Http\Controllers\Controller;
use App\PayManage;
use Illuminate\Pagination\AbstractPaginator;
use App\Utils;
use Event;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\Mapping;

class PayController extends Controller
{    
    /**
     * @api {get} /pay_manage/index 1.付款单列表
     * @apiName index
     * @apiGroup PayManage
     *
     * @apiParam {Number} key  1 店铺搜索   2 商户搜索 3 店铺编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 付款最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 付款最大时间 YYYY-MM-DD
     * @apiParam {String} type 0 全部 1 付交易代收款 2 付业务投资款
     * @apiParam {String} pay_type 0 全部 1 银行存款 2账扣支付 3现金  4支付宝 5财付通
     * @apiParam {String} state 0 全部 1 待提交 2待审批 3待付款  4已付款
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','updated_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'cost_money'(换算消费额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} code 单号
     * @apiSuccess {String} type 付款类型 1 付交易代收款 2 付业务投资款
     * @apiSuccess {String} data.from 来源 1 本系统 2 商家后台
     * @apiSuccess {String} money 付款金额
     * @apiSuccess {String} pay_type 付款方式   1 银行存款 2账扣支付 3现金  4支付宝 5财付通
     * @apiSuccess {String} require_day 要求付款日期 
     * @apiSuccess {String} pay_day 实际付款日期 
     * @apiSuccess {String} remark 备注 
     * @apiSuccess {String} cycle 回款周期
     * @apiSuccess {String} cycle_day 回款日期 
     * @apiSuccess {String} cycle_money 周期回款金额 
     * @apiSuccess {String} make_user 制单人信息
     * @apiSuccess {String} confirm_user 审批人信息
     * @apiSuccess {String} cash_user 出纳人信息
     * @apiSuccess {String} salon 店铺信息
     * @apiSuccess {String} state 订单状态  1待提交 2待审批 3:待付款 4:已付款
     * @apiSuccess {String} confirm_at 审批日期
     * @apiSuccess {String} salon_user 制单人信息(为商家后台时)
     *
     * @apiSuccessExample Success-Response:
     *       {
     *          "result": 1,
     *          "data": {
     *              "total": 1,
     *              "per_page": 10,
     *              "current_page": 1,
     *              "last_page": 1,
     *              "from": 1,
     *              "to": 1,
     *              "data": [
     *               {
     *                   "id": 2,
     *                   "code": "FTZ-150814190145001",
     *                   "type": 2,
     *                   "salon_id": 1,
     *                   "merchant_id": 2,
     *                   "money": "333.66",
     *                   "from":1,
     *                   "pay_type": 1,
     *                   "require_day": "2015-08-14",
     *                   "pay_day": "0000-00-00",
     *                   "cycle": 30,
     *                   "cycle_day": 1,
     *                   "cycle_money": "100.00",
     *                   "make_uid": 1,
     *                   "cash_uid": 0,
     *                   "prepay_bill_code": "",
     *                   "receive_bill_code": "",
     *                   "state": 2,
     *                   "created_at": "2015-08-14 19:01:45",
     *                   "confirm_uid": 0,
     *                   "confirm_at": "0000-00-00",
     *                   "updated_at": "2015-08-14 19:01:45",
     *                   "remark":"备注",
     *                   "make_user": {
     *                       "id": 1,
     *                       "name": "这是用户名Admin"
     *                   },
     *                   "salon_user": {
     *                       "salon_user_id": 1,
     *                       "username": "商家后台用户名"
     *                   },
     *                   "confirm_user": null,
     *                   "cash_user": null,
     *                   "salon": {
     *                       "salonid": 1,
     *                       "salonname": "嘉美专业烫染",
     *                       "sn": "SZ0320001"
     *                   }
     *               },
     *               {
     *                   "id": 2,
     *                   "code": "FTZ-150814190145001",
     *                   "type": 2,
     *                   "salon_id": 1,
     *                   "merchant_id": 2,
     *                   "money": "333.66",
     *                   "pay_type": 1,
     *                   "from":1,
     *                   "require_day": "2015-08-14",
     *                   "pay_day": "0000-00-00",
     *                   "cycle": 30,
     *                   "cycle_day": 1,
     *                   "cycle_money": "100.00",
     *                   "make_uid": 1,
     *                   "cash_uid": 0,
     *                   "prepay_bill_code": "",
     *                   "receive_bill_code": "",
     *                   "state": 2,
     *                   "created_at": "2015-08-14 19:01:45",
     *                   "confirm_uid": 0,
     *                   "confirm_at": "0000-00-00",
     *                   "updated_at": "2015-08-14 19:01:45",
     *                   "make_user": {
     *                       "id": 1,
     *                       "name": "这是用户名Admin"
     *                   },
     *                   "salon_user": {
     *                       "salon_user_id": 1,
     *                       "username": "商家后台用户名"
     *                   },
     *                   "confirm_user": null,
     *                   "cash_user": null,
     *                   "salon": {
     *                       "salonid": 1,
     *                       "salonname": "嘉美专业烫染",
     *                       "sn": "SZ0320001"
     *                   }
     *               }
     *           ]
     *       }
     *      }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function index()
    {
        $options = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'pay_time_min' => self::T_STRING,
            'pay_time_max' => self::T_STRING,
            'type' => self::T_INT,
            'pay_type' => self::T_INT,
            'state' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
        ]);
        $page = isset($options['page']) ? max(intval($options['page']), 1) : 1;
        $size = isset($options['page_size']) ? max(intval($options['page_size']), 1) : 20;
        
        $pay = PayManage::search($options);
        
        AbstractPaginator::currentPageResolver(function () use($page)
        {
            return $page;
        });
        
        $res = $pay->paginate($size)->toArray();
        unset($res['next_page_url']);
        unset($res['prev_page_url']);
        return $this->success($res);
    }

    /**
     * @api {get} /pay_manage/create 2.付款单新增
     * @apiName create
     * @apiGroup PayManage
     *
     * @apiParam {Number} type 付款类型 1 付交易代收款 2 付业务投资款
     * @apiParam {Number} salon_id  店铺id
     * @apiParam {Number} merchant_id  商户id
     * @apiParam {Number} money 付款金额
     * @apiParam {Number} pay_type 付款方式   1 银行存款 2账扣支付 3现金  4支付宝 5财付通
     * @apiParam {Number} cycle 回款周期 
     * @apiParam {Number} cycle_day 回款日期 
     * @apiParam {Number} cycle_money 周期回款金额 
     * @apiParam {String} remark 备注
     * 
     * 
     * @apiSuccess {Number} id 成功的id.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *          "result": 1,
     *          "data": {
     *                  "id":1
     *                  }
     *              ]
     *          }
     *      }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function store()
    {
        $params = $this->parameters([
            'type' => self::T_INT,
            'salon_id' => self::T_INT,
            //'merchant_id' => self::T_INT,
            'money' => self::T_FLOAT,            
            'pay_type' => self::T_INT,
            'remark'=>self::T_STRING,            
        ],true);
        $param_plus = $this->parameters([
            'cycle' => self::T_INT,
            'cycle_day' => self::T_INT,
            'cycle_money' => self::T_FLOAT,
        ]);
        $params['make_uid'] = $this->user->id;  
        $params = array_merge($params,$param_plus);    
        $res = PayManage::make($params);
        if($res)
        {
            Event::fire("pay.store",$res['code']);
            return $this->success(['id'=>$res['id']]);
        }
        else 
        {
            throw new ApiException("创建失败", ERROR::UNKNOWN_ERROR);
        }
    }

    /**
     * @api {post} /pay_manage/show/{id} 3.付款单  查看
     * @apiName show
     * @apiGroup PayManage
     * 
     * @apiSuccess {String} code 单号
     * @apiSuccess {String} r_code 关联收款单号
     * @apiSuccess {String} p_code  关联转付单号
     * @apiSuccess {String} type 付款类型 1 付交易代收款 2 付业务投资款
     * @apiSuccess {String} money 付款金额
     * @apiSuccess {String} pay_type 付款方式   1 银行存款 2账扣支付 3现金  4支付宝 5财付通
     * @apiSuccess {String} data.from 来源 1 本系统 2 商家后台
     * @apiSuccess {String} pay_day 实际付款日期 
     * @apiSuccess {String} remark 备注
     * @apiSuccess {String} cycle 回款周期
     * @apiSuccess {String} cycle_day 回款日期 
     * @apiSuccess {String} cycle_money 周期回款金额 
     * @apiSuccess {String} make_user 制单人信息
     * @apiSuccess {String} confirm_user 审批人信息
     * @apiSuccess {String} cash_user 出纳人信息
     * @apiSuccess {String} salon 店铺信息
     * @apiSuccess {String} state 订单状态  1待提交 2待审批 3待付款 4已付款
     * @apiSuccess {String} confirm_at 审批日期
     * @apiSuccess {String} salon_user 制单人信息(为商家后台时)
     * 
     * @apiSuccessExample Success-Response:
     *        {
     *            "result": 1,
     *            "data": {
     *                "id": 2,
     *                "code": "FTZ-150814190145001",
     *                "r_code": "FTZ-150814190145001",
     *                "p_code": "FTZ-150814190145001",
     *                "type": 2,
     *                "salon_id": 1,
     *                "merchant_id": 2,
     *                "from":1,
     *                "money": "333.66",
     *                "pay_type": 1,
     *                "pay_day": "0000-00-00",
     *                "remark": "备注",
     *                "cycle": 30,
     *                "cycle_day": 1,
     *                "cycle_money": "100.00",
     *                "make_uid": 1,
     *                "cash_uid": 0,
     *                "prepay_bill_code": "",
     *                "receive_bill_code": "",
     *                "state": 2,
     *                "created_at": "2015-08-14 19:01:45",
     *                "confirm_uid": 0,
     *                "confirm_at": "0000-00-00",
     *                "updated_at": "2015-08-14 19:01:45",
     *                "make_user": {
     *                    "id": 1,
     *                    "name": "这是用户名Admin"
     *                },
     *                 "salon_user": {
     *                     "salon_user_id": 1,
     *                     "username": "商家后台用户名"
     *                },
     *                "confirm_user": null,
     *                "cash_user": null,
     *                "salon": {
     *                    "salonid": 1,
     *                    "salonname": "嘉美专业烫染",
     *                    "sn": "SZ0320001"
     *                }
     *            }
     *        }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,删除失败"
     *		}
     */
    public function show($id)
    {
        $item = PayManage::detail($id);
        
        return $this->success($item);
    }

    /**
     * @api {get} /pay_manage/update/{id} 4.付款单修改
     * @apiName update
     * @apiGroup PayManage
     *
     * @apiParam {Number} salon_id  店铺id
     * @apiParam {Number} merchant_id  商户id
     * @apiParam {Number} money 付款金额
     * @apiParam {Number} pay_type 付款方式   1 银行存款 2账扣支付 3现金  4支付宝 5财付通
     * @apiParam {String} remark 备注
     * @apiParam {Number} cycle 回款周期
     * @apiParam {Number} cycle_day 回款日期
     * @apiParam {Number} cycle_money 周期回款金额 
     *
     * @apiSuccess {Number} id 成功的id.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *          "result": 1,
     *          "data": {
     *                  "id":1
     *                  }
     *              ]
     *          }
     *      }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function update($id)
    {
        $params = $this->parameters([
            'salon_id' => self::T_INT,
            'merchant_id' => self::T_INT,
            'money' => self::T_FLOAT,
            'pay_type' => self::T_INT,
            'cycle' => self::T_INT,
            'cycle_day' => self::T_INT,
            'cycle_money' => self::T_FLOAT,
            'remark'=>self::T_STRING,
        ]);
        //#@todo for debug
        //$params['make_uid'] = 1;
        $params['make_uid'] = $this->user->id;
        
        $ret = PayManage::change($id, $params);
        if(!$ret)
        {
            throw new ApiException("修改失败", ERROR::UNKNOWN_ERROR);
        }
        Event::fire("pay.update",$ret['code']);
        return $this->success(['id'=>$id]);
    }

    /**
     * @api {post} /pay_manage/destroy/{id} 5.付款单  删除
     * @apiName destroy
     * @apiGroup PayManage
     * 
     * @apiSuccess {String} ret 1 成功删除
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "ret": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,删除失败"
     *		}
     */
    public function destroy($id)
    {
        $res = PayManage::destroy($id);
        if(!$res)
        {
            throw new ApiException("此单状态或类型不允许删除或者已经删除!", ERROR::UNKNOWN_ERROR);
        } 
        Event::fire("pay.destroy",$res['code']);
        return $this->success(["ret"=>1]);
    }
    
    /**
     * @api {post} /pay_manage/check 6.付款单  审批
     * @apiName check
     * @apiGroup PayManage
     *
     * @apiParam {String} ids  id1,id2,... 
     * @apiParam {Number} do  1 通过 0 拒绝
     * 
     * @apiSuccess {String} ret 1 执行成功
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "ret": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,执行失败"
     *		}
     */
    public function check()
    {
        $params = $this->parameters([
            'ids' => self::T_STRING,
            'do' => self::T_INT,
        ],true);
        
        //for test
        //$uid = $this->user->id;
        
        $uid = $this->user->id;
        $ids =  explode(",",$params['ids']);
        $ids = array_map("intval",$ids);
        $ret = PayManage::check($ids,$params['do'],$uid);
        if(!$ret)
        {
            throw new ApiException("单状态不正确或者不存在!",ERROR::UNKNOWN_ERROR);
        }
        Event::fire("pay.check",json_encode(['ids'=>$ids,'type'=>$params['do']],JSON_UNESCAPED_UNICODE));
        return $this->success(["ret"=>1]);
    }

    /**
     * @api {post} /pay_manage/confirm 7.付款单  确认
     * @apiName confirm
     * @apiGroup PayManage
     *
     * @apiParam {String} ids  id1,id2,...
     * @apiParam {Number} do  1 通过 0 拒绝
     *
     * @apiSuccess {String} ret 1 执行成功
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "ret": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,执行失败"
     *		}
     */
    public function confirm()
    {
       $params = $this->parameters([
            'ids' => self::T_STRING,
            'do' => self::T_INT,
        ],true);
        //for test
        //$uid = $this->user->id;
        $uid = $this->user->id;
        $ids =  explode(",",$params['ids']);
        $ids = array_map("intval",$ids);
        $ret = PayManage::confirm($ids,$params['do'],$uid);
        if(!$ret)
        {
            throw new ApiException("单状态不正确或者不存在!",ERROR::UNKNOWN_ERROR);
        }
        Event::fire("pay.confirm",json_encode(['ids'=>$ids,'type'=>$params['do']],JSON_UNESCAPED_UNICODE));
        return $this->success(["ret"=>1]);
    }

    /**
     * @api {get} /pay_manage/export 8.付款单导出
     * @apiName export
     * @apiGroup PayManage
     *
     * @apiParam {Number} key  1 店铺搜索  2 店铺编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 付款最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 付款最大时间 YYYY-MM-DD
     * @apiParam {String} type 0 全部 1 付交易代收款 2 付业务投资款
     * @apiParam {String} pay_type 0 全部 1 银行存款 2账扣支付 3现金  4支付宝 5财付通
     * @apiParam {String} state 0 全部 1 待提交 2待审批 3待付款  4已付款
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'cost_money'(换算消费额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     * 
     *
     */
    public function export()
    {
        $options = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'pay_time_min' => self::T_STRING,
            'pay_time_max' => self::T_STRING,
            'type' => self::T_INT,
            'pay_type' => self::T_INT,
            'state' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
        ]);    
        $header = ['店铺编号','店铺名称','付款单号','关联收款单号','关联转付单号','付款类型','付款金额','备注','实际付款日期','回款周期','回款日期','周期回款金额','创建日期','审批日期','制单人','审批人','出纳','付款方式','状态'];
        $items = PayManage::search($options)->with([
            'cash_user' => function ($q)
            {
                $q->lists('id','name');
            }
        ])->with([
            'confirm_user' => function ($q)
            {
                $q->lists('id','name');
            }
        ])->addSelect(['r_code','p_code','cycle','cycle_day','cycle_money','confirm_at'])->get()->toArray(); 
        Event::fire("pay.export");
        $this->export_xls("付款列表".date("Ymd"),$header,self::format_pay_data($items));
    }
    
    /**
     * @api {get} /pay_manage/withdraw 8.付款单导出
     * @apiName withdraw
     * @apiGroup PayManage
     *
     * @apiParam {Number} id  提款的id
     * @apiParam {String} token  加密的token
     * 
     *      
     * @apiSuccess {String} ret 1 执行成功
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "ret": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,执行失败"
     *		}
     *
     */
    public function withdraw()
    {
        $params = $this->parameters(
            [
                'id'=>self::T_INT,
                'token'=>self::T_STRING,
            ],true);
        $passed = Utils::checkToken($params);
        if(!$passed)
        {
            throw new ApiException("Unauthorized",ERROR::ACCOUNT_INVALID);
        }
        $id = $params['id'];
        $res = PayManage::makeByWithdraw($id);
        return $this->success($res);
    }
    
    
    public static function  format_pay_data($datas)
    {
        $res = [];
        foreach($datas as $data)
        {
            $salon_name = isset($data['salon']['salonname']) ? $data['salon']['salonname'] : '';
            $salon_sn = isset($data['salon']['sn']) ? $data['salon']['sn'] : '';
            $pay_manage_type_name = $data['type'] == 1?"付交易代收款 ":"付业务投资款";
            $make_user_name = '';
            if($data['from'] == PayManage::FROM_LOCAL)
            {
                $make_user_name = isset($data['make_user']['name'])?$data['make_user']['name']:"";
            }
            else 
            {
                $make_user_name = isset($data['salon_user']['username'])?$data['salon_user']['username']:"";
            }
            $check_user_name = isset($data['confirm_user']['name'])?$data['confirm_user']['name']:"";
            $cash_user_name = isset($data['cash_user']['name'])?$data['cash_user']['name']:"";
            $pay_type_name = Mapping::getPayTypeName($data['pay_type']);
            $state_name = Mapping::getPayManageStateName($data['state']);
            $cycle = empty($data['cycle'])?"":$data['cycle']."个月";
            $cycle_day = empty($data['cycle_day'])?"":$data['cycle_day']."号/月";
            $res[] = [
                $salon_sn,//店铺编号
                $salon_name,//店铺名称                
                $data['code'],//付款单号
                $data['r_code'],//关联收款单号
                $data['p_code'],//关联转付单号
                $pay_manage_type_name,//付款类型
                $data['money'],//付款金额
                $data['remark'],
                $data['pay_day'],//实际付款日期
                $cycle,//回款周期
                $cycle_day,//回款日期
                $data['cycle_money'],//周期回款金额
                date("Y-m-d",strtotime($data['created_at'])),//创建日期
                $data['confirm_at'],//审批日期
                $make_user_name,//制单人
                $check_user_name,//审批人
                $cash_user_name,//出纳
                $pay_type_name,//付款方式
                $state_name//状态
                
            ];
        }
        return $res;
    }
    

}
