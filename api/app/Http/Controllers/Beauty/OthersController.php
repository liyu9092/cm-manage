<?php

namespace App\Http\Controllers\Beauty;

use App\Http\Controllers\Controller;
use DB;
use Event;
use Excel;
use App\BeautyOthers;
use App\Exceptions\ApiException;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ERROR;
use Log;

class OthersController extends Controller {

    /**
     * @api {post} /others/index     1.其他人员列表
     * @apiName index
     * @apiGroup Others
     *
     * @apiParam {Number} key       可选,主体搜索条件类型。  1.人员姓名 2.在职编号 3.手机号码 4.类型
     * @apiParam {String} keyword           可选,对应主体搜索关键词; 类型给对应数字 1美导A 2美容师B；3收银C；4前台D；
     * @apiParam {String} sort_key          可选, 排序字段支持 status 状态
     * @apiParam {String} sort_type         可选, 排序方式 desc 降序 asc 升序
     * @apiParam {Number} page              可选,第几页。 默认为第一页
     * @apiParam {Number} page_size         可选,取多少条。 默认为20条
     *
     *
     * @apiSuccess {Number} total           总数据量.
     * @apiSuccess {Number} per_page        分页大小.
     * @apiSuccess {Number} current_page    当前页面.
     * @apiSuccess {Number} last_page       最后页面.
     * @apiSuccess {Number} from            起始数据.
     * @apiSuccess {Number} to              结束数据.
     * @apiSuccess {Number} id              人员id
     * @apiSuccess {String} name            人员名称
     * @apiSuccess {String} sex             性别 1男； 2女
     * @apiSuccess {String} add_time        录入时间
     * @apiSuccess {String} workingLife     工作年限
     * @apiSuccess {String} number          在职编号
     * @apiSuccess {Number} type            1美导A 2美容师B；3收银C；4前台D；
     * @apiSuccess {String} mobilePhone     手机号码
     * @apiSuccess {Number} status          状态标识. 1:正常启用，0:禁用
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": {
     * 	        "total": 43353,
     * 	        "per_page": 20,
     * 	        "current_page": 1,
     * 	        "last_page": 2168,
     *  	    "from": 1,
     * 	        "to": 20,
     * 	        "data": [
     * 	            {
     *                  "id": 6,
     *                  "name": "小楠",
     *                  "sex": 1,
     *                  "workingLife": 6,
     *                  "number": "B1165",
     *                  "type": 2,
     *                  "mobilePhone": "16877698584",
     *                  "status": 1
     * 	            }
     * 	        ]
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function index() {
        $param = $this->param;
        $key = isset($param['key']) ? $param['key'] : 0;
        $keyword = isset($param['keyword']) ? $param['keyword'] : '';
        $sort_key = isset($param['sort_key']) ? $param['sort_key'] : '';
        $sort_type = isset($param['sort_type']) ? $param['sort_type'] : '';
        $page = isset($param['page']) ? max($param['page'], 1) : 1;
        $pageSize = isset($param['page_size']) ? $param['page_size'] : 20;
        if ($pageSize <= 0)
            $pageSize = 20;
        if ($pageSize > 500)
            $pageSize = 500;
        if ((!$sort_key && $sort_type) || ($sort_key && !$sort_type))
            return $this->error('排序传值错误');
        $field = ['id', 'name', 'sex', 'working_life AS workingLife','add_time', 'number', 'type', 'mobilephone as mobilePhone', 'status'];
        $obj = BeautyOthers::select($field);
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        if ($key != 0 && $keyword) {
            switch ($key) {
                case 1:
                    $obj->where('name', 'like', '%' . $keyword . '%');
                    break;
                case 2:
                    $obj->where('number', 'like', '%' . $keyword . '%');
                    break;
                case 3:
                    $obj->where('mobilephone', 'like', '%' . $keyword . '%');
                    break;
                case 4:
                    $obj->where('type', $keyword);
                    break;
                default:
                    break;
            }
        }
        if (!$sort_key || !$sort_type)
            $result = $obj->orderBy('id', 'desc')->paginate($pageSize)->toArray();
        if ($sort_key && $sort_type) {
            switch ($sort_key) {
                case 'status':
                    // 降序为 启用（1）在前 禁用（0）在后
                    $temp = ['asc' => 'desc', 'desc' => 'asc'];
                    $result = $obj->orderBy($sort_key, $temp[$sort_type])->paginate($pageSize)->toArray();
                    break;
                default:
                    $result = $obj->orderBy('id', 'desc')->paginate($pageSize)->toArray();
                    break;
            }
        }
//        $result = $this->_formatListData($result);
//        var_dump($result);exit;
        return $this->success($result);
    }

    /**
     * @api {Post} /others/add    2.其他人员添加
     * @apiName add
     * @apiGroup Others
     *
     * @apiParam {String} photo          必填,个人照片.
     * @apiParam {String} name           必填,姓名.
     * @apiParam {Number} sex            必填,性别 1.男 2.女
     * @apiParam {String} birthday       必填,生日 格式如 2015-02-22.          
     * @apiParam {Number} credential     必填,证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
     * @apiParam {Number} cardId         必填,证件类型所对应的证件号码.
     * @apiParam {Number} mobilePhone    必填,电话.
     * @apiParam {Number} wechat         选填,微信.
     * @apiParam {Number} qq             选填,qq.
     * @apiParam {Number} email          选填,电子邮箱.
     * @apiParam {String} number         必填,在职编号.
     * @apiParam {Number} type           必填,类型. 1美导A 2美容师B； 3收银C； 4前台D
     * @apiParam {String} introduce      必填,自我描述.
     * @apiParam {Number} workingLife    必填,工作年限.
     *
     * 
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": null
     * 	    }
     *
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": []
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function add() {
        $param = $this->param;
        $addData = $this->_formatReceiveData($param);
        $addData['created_at'] = time();
        $addData['add_time'] = date("Y-m-d",time());
        $lastId = BeautyOthers::insertGetId($addData);
        Event::fire('others.add', '其他人员 id: ' . $lastId);
        return $this->success();
    }

    /**
     * @api {Post} /others/update  3.其他人员编辑
     * @apiName update
     * @apiGroup Others
     *
     * @apiParam {String} photo          必填,个人照片.
     * @apiParam {String} name           必填,姓名.
     * @apiParam {Number} sex            必填,性别 1.男 2.女
     * @apiParam {String} birthday       必填,生日 格式如 2015-02-22.          
     * @apiParam {Number} credential     必填,证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
     * @apiParam {Number} cardId         必填,证件类型所对应的证件号码.
     * @apiParam {Number} mobilePhone    必填,电话.
     * @apiParam {Number} wechat         选填,微信.
     * @apiParam {Number} qq             选填,qq.
     * @apiParam {Number} email          选填,电子邮箱.
     * @apiParam {String} number         必填,在职编号.
     * @apiParam {Number} type           必填,类型. 1美导A 2美容师B； 3收银C； 4前台D
     * @apiParam {String} introduce      必填,自我描述.
     * @apiParam {Number} workingLife    必填,工作年限.
     *
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": null
     * 	    }
     *
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": []
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function save() {
        $param = $this->param;
        $id = isset($param['id']) ? $param['id'] : $this->error('人员id未填写');
        $saveData = $this->_formatReceiveData($param);
        $saveData['updated_at'] = time();
        BeautyOthers::where(['id' => $id])->update($saveData);
        Event::fire('others.update', '编辑人员 id: ' . $id);
        return $this->success();
    }

    /**
     * @api {Get} /others/show/:id  4.人员信息
     * @apiName show
     * @apiGroup Others
     *
     * @apiSuccess {Number} id             专家助手id
     * @apiSuccess {String} photo          个人照片.
     * @apiSuccess {String} name           姓名.
     * @apiSuccess {Number} sex            性别 1.男 2.女
     * @apiSuccess {String} add_time       录入时间
     * @apiSuccess {String} birthday       生日 格式如 2015-02-22.
     * @apiSuccess {String} number         在职编号.
     * @apiSuccess {Number} workingLife    工作年限.
     * @apiSuccess {String} introduce      自我描述.
     * @apiSuccess {Number} credential     证件类型  1身份证； 2军官证； 3驾驶证； 4护照.
     * @apiSuccess {String} cardId         证件类型所对应的证件号码.
     * @apiSuccess {String} mobilePhone    电话.
     * @apiSuccess {String} wechat         微信.
     * @apiSuccess {String} qq             qq.
     * @apiSuccess {String} email          电子邮箱.
     * @apiSuccess {Number} status         状态标识. 1:正常启用，0:禁用
     * @apiSuccess {String} type           类型 1美导A 2美容师B； 3收银C； 4前台D
     *
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": {
     *                  "id": 3,
     *                  "name": "小红",
     *                  "photo": "{\"thumbimg\":\"http://img01.choumei.cn/1/7/2015120211081449025727167732700.jpg?imageMogr2/crop/!250x167a250a584/thumbnail/750x500\"}",
     *                  "sex": 2,
     *                  "birthday": "2015-02-01",
     *                  "credential": 1,
     *                  "cardId": "4944564541584",
     *                  "mobilePhone": "16877698585",
     *                  "wechat": "15877698585",
     *                  "qq": "15877698585",
     *                  "email": "15877698585@qq.com",
     *                  "number": "C1158",
     *                  "workingLife": 0,
     *                  "introduce": "我来介绍0003",
     *                  "status": 1,
     *                  "type": 3
     *      }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function show($id) {
        $field = [
            'id', 'name',
            'photo', 'sex','add_time',
            'birthday', 'credential', 'card_id as cardId',
            'mobilephone as mobilePhone', 'wechat', 'qq',
            'email', 'number',
            'working_life as workingLife', 'introduce',
            'status', 'type'
        ];
        $info = BeautyOthers::select($field)->where(['id' => $id])->first();
        if (empty($info))
            return $this->error('没有找到人员信息哦');
        $info = $info->toArray();
        $info['mobilePhone'] = $info['mobilePhone'] ? : '';
        $info['qq'] = $info['qq'] ? : '';
        return $this->success($info);
    }

    /**
     * @api {get} /others/up/:id 5.启用
     * @apiName up
     * @apiGroup Others
     *
     * @apiParam {Number} id 必填,职工id.
     *
     *
     * 
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": []
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function start($id) {
        BeautyOthers::where(['id' => $id, 'status' => 0])->update(['status' => 1]);
        Event::fire('others.up', '启用人员 id: ' . $id);
        return $this->success();
    }

    /**
     * @api {get} /others/down/:id 6.禁用
     * @apiName     down
     * @apiGroup    Others
     *
     * @apiParam {Number} id 必填,职工id.
     *
     *
     * 
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": []
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function close($id) {
        BeautyOthers::where(['id' => $id, 'status' => 1])->update(['status' => 0]);
        Event::fire('others.down', '禁用人员 id: ' . $id);
        return $this->success();
    }

    /**
     * @api {post} /assistant/export     7.导出人员列表
     * @apiName export
     * @apiGroup Others
     *
     * @apiParam {String} key       可选,主体搜索条件类型。  1.专家姓名 2.在职编号 3.手机号码 4.所属专家姓名
     * @apiParam {String} keyword           可选,对应主体搜索关键词.
     * @apiParam {String} sort_key          可选, 排序字段支持 status 状态
     * @apiParam {String} sort_type         可选, 排序方式 desc 降序 asc 升序
     * @apiParam {Number} page              可选,第几页。 默认为第一页
     * @apiParam {Number} page_size         可选,取多少条。 默认为20条
     *
     *
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": null
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function export() {
        $param = $this->param;
        $key = isset($param['key']) ? $param['key'] : 0;
        $keyword = isset($param['keyword']) ? $param['keyword'] : '';
        $sort_key = isset($param['sort_key']) ? $param['sort_key'] : '';
        $sort_type = isset($param['sort_type']) ? $param['sort_type'] : '';
        $page = isset($param['page']) ? max($param['page'], 1) : 1;
        $pageSize = isset($param['page_size']) ? $param['page_size'] : 20;
        if ($pageSize <= 0)
            $pageSize = 20;
        if ($pageSize > 500)
            $pageSize = 500;
        if ((!$sort_key && $sort_type) || ($sort_key && !$sort_type))
            return $this->error('排序传值错误');
        $field = ['id', 'name', 'sex', 'working_life AS workingLife','add_time', 'number', 'type', 'mobilephone as mobilePhone', 'status'];
        $obj = BeautyOthers::select($field);
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        if ($key != 0 && $keyword) {
            switch ($key) {
                case 1:
                    $obj->where('name', 'like', '%' . $keyword . '%');
                    break;
                case 2:
                    $obj->where('number', 'like', '%' . $keyword . '%');
                    break;
                case 3:
                    $obj->where('mobilephone', 'like', '%' . $keyword . '%');
                    break;
                case 4:
                    $obj->where('type', $keyword);
                    break;
                default:
                    break;
            }
        }
        if (!$sort_key || !$sort_type)
            $result = $obj->orderBy('id', 'desc')->paginate($pageSize)->toArray();
        if ($sort_key && $sort_type) {
            switch ($sort_key) {
                case 'status':
                    // 降序为 启用（1）在前 禁用（0）在后
                    $temp = ['asc' => 'desc', 'desc' => 'asc'];
                    $result = $obj->orderBy($sort_key, $temp[$sort_type])->paginate($pageSize)->toArray();
                    break;
                default:
                    $result = $obj->orderBy('id', 'desc')->paginate($pageSize)->toArray();
                    break;
            }
        }
//        $result = $this->_formatListData($result);
        $t = $result['data'];
        $title = '其他人员查询列表' . date('Ymd');
        $header = ['人员姓名', '性别', '工作年限','录入时间', '在职编号', '类型', '手机号码', '状态 '];
        $t1 = ['', '男', '女'];
        $t2 = ['', '美导', '美容师', '收银', '前台'];
        $t3 = ['禁用', '启用'];
        $tempData = [];
        foreach ($t as $key => $val) {
            $tempData[$key][] = $val['name'];
            $tempData[$key][] = $t1[$val['sex']];
            $tempData[$key][] = $val['workingLife'];
            $tempData[$key][] = $val['add_time'];
            $tempData[$key][] = $val['number'];
            $tempData[$key][] = $t2[$val['type']];
            $tempData[$key][] = $val['mobilePhone'];
            $tempData[$key][] = $t3[$val['status']];
        }
        Event::fire('others.export', '导出其他人员查询列表');
        Excel::create($title, function($excel) use($tempData, $header) {
            $excel->sheet('Sheet1', function($sheet) use($tempData, $header) {
                $sheet->fromArray($tempData, null, 'A1', false, false); //第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header); //添加表头
            });
        })->export('xls');
        exit;
    }

    /**
     * @api {get} /others/checkNumberExists/number 8.获取人员编码是否存在
     * @apiName     checkNumberExists
     * @apiGroup    Others
     *
     * @apiParam {Number} number          必填,人员编号.
     *
     *
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "token": "",
     * 	    "data": []
     * 	}
     * 
     * * @apiErrorExample Error-Response:
     * 		{
     *          "result": 0,
     *          "code": -51510,
     *          "msg": "人员编号已存在",
     *          "token": ""
     *      }
     */
    public function checkNumberExists($number) {
    
        if(!isset($number))
            return $this->error('未填写人员编码');
        $flag = $this->_checkNumberExists(0, $number);
        if (!$flag)
            return $this->success();
        return $this->error('人员编号已存在', ERROR::ARTIFICER_NAME_EXISTS_ERROR);
    }

    // 接收天界或者修改的数据
    private function _formatReceiveData($param) {
        $data = [];
        $data['photo'] = $photo = isset($param['photo']) ? $param['photo'] : $this->error('个人图片未填写');
        $data['name'] = $name = isset($param['name']) ? $param['name'] : $this->error('专家姓名未填写');
        $data['sex'] = $gender = isset($param['sex']) ? $param['sex'] : $this->error('性别未填写');
        $data['birthday'] = $birthday = isset($param['birthday']) ? $param['birthday'] : $this->error('出生日期未填写');
        $data['number'] = $jobNumber = isset($param['number']) ? $param['number'] : $this->error('在职编号未填写');
        $data['working_life'] = $jobYear = isset($param['workingLife']) ? $param['workingLife'] : $this->error('工作年限未填写');
        $data['type'] = $pid = isset($param['type']) ? $param['type'] : $this->error('类型未填写');
        $data['introduce'] = $signature = isset($param['introduce']) ? $param['introduce'] : $this->error('个人描述未填写');
        // 证件类型
        $credentialType = isset($param['credential']) ? $param['credential'] : 0;
        $credentialValue = isset($param['cardId']) ? $param['cardId'] : '';
        $data['mobilephone'] = $mobilePhone = isset($param['mobilePhone']) ? $param['mobilePhone'] : '';

        if (empty($credentialType) && empty($credentialValue))
            return $this->error('身份证件未填写');
        if (empty($mobilePhone))
            return $this->error('手机号码未填写');
        $data['wechat'] = $wechat = isset($param['wechat']) ? $param['wechat'] : '';
        $data['qq'] = $qq = isset($param['qq']) ? $param['qq'] : '';
        $data['email'] = $email = isset($param['email']) ? $param['email'] : '';
        if ($credentialType && $credentialValue) {
            $data['credential'] = $credentialType;
            $data['card_id'] = $credentialValue;
        }
        // 检验 专家姓名 和 专家编号是否存在
        if (isset($param['id']) && !empty($param['id'])) {
            $nameExists = $this->_checkNameExists($param['id'], $name);
            $numberExists = $this->_checkNumberExists($param['id'], $jobNumber);
        } else {
            $nameExists = $this->_checkNameExists(0, $name);
            $numberExists = $this->_checkNumberExists(0, $jobNumber);
        }
        if ($nameExists || $numberExists) {
            return $this->error('人员或者编号有重复哦~');
        }
        return $data;
    }

    private function _checkNumberExists($id = 0, $number = '') {
        if (empty($number))
            return false;
        $exists = BeautyOthers::select(['id'])->where(['number' => $number])->first();
        if (empty($exists))
            return false;
        $exists = $exists->toArray();
        if ($id == $exists['id'])
            return false;
        return true;
    }

    private function _checkNameExists($id = 0, $name = '') {
        if (empty($name))
            return false;
        $exists = BeautyOthers::select(['id'])->where(['name' => $name])->first();
        if (empty($exists))
            return false;
        $exists = $exists->toArray();
        if ($id == $exists['id'])
            return false;
        return true;
    }
    
    /**
	 * @api {get} /others/search/type/number   9.搜索美导，美容师名字
	 * @apiName     searchNameAndNumber
	 * @apiGroup    Others
	 *
     * 
	 * @apiParam {Number} type           1美导 2美容师；3收银；4前台.
         * @apiParam {String} number         在职编号或姓名
	 *
	 * @apiSuccess {Number} id              id号.
	 * @apiSuccess {String} name            美导名字.
	 * @apiSuccess {String} number          美导编号.
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
     *          {
     *              "id": 1,
     *              "name": "XIAOd",
     *              "number": "M001"
     *          }
     *      ]
	 *	}
	 */
    public function searchNameAndNumber( $type = 0,$nameOrNumber = ''){
        if( empty( $nameOrNumber ) ) return $this->success();
        $field = ['id','name','number'];
        $result = BeautyOthers::select( $field )->whereRaw('(name like "%'. $nameOrNumber .'%" OR number like "%'. $nameOrNumber .'%") AND type = '.$type)->get();
        return $this->success($result);
    }

}
