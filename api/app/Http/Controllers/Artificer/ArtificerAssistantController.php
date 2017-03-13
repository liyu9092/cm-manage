<?php

namespace App\Http\Controllers\Artificer;

use App\Http\Controllers\Controller;
use DB;
use Event;
use Excel;
use App\Artificer;
use App\BeautyOrderItem;
use App\BeautyItem;
use App\BookingOrder;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ERROR;

class ArtificerAssistantController extends Controller{
    /**
	 * @api {post} /assistant/index     1.专家助手列表
	 * @apiName index
	 * @apiGroup Assistant
	 *
	 * @apiParam {String} itemMainNum       可选,主体搜索条件类型。  1.专家姓名 2.在职编号 3.手机号码 4.所属专家姓名
	 * @apiParam {String} keyword           可选,对应主体搜索关键词.
	 * @apiParam {String} orderField        可选, 排序字段支持 status 状态
	 * @apiParam {String} orderBy           可选, 排序方式 desc 降序 asc 升序
	 * @apiParam {Number} page              可选,第几页。 默认为第一页
	 * @apiParam {Number} page_size         可选,取多少条。 默认为20条
	 *
	 *
	 * @apiSuccess {Number} total           总数据量.
	 * @apiSuccess {Number} per_page        分页大小.
	 * @apiSuccess {Number} current_page    当前页面.
	 * @apiSuccess {Number} last_page       当前页面.
	 * @apiSuccess {Number} from            起始数据.
	 * @apiSuccess {Number} to              结束数据.
	 * @apiSuccess {Number} id              专家id
	 * @apiSuccess {String} name            专家名称
	 * @apiSuccess {String} sex             性别 1男； 2女
	 * @apiSuccess {String} country         国家名称
	 * @apiSuccess {String} workingLife     工作年限
	 * @apiSuccess {String} level           级别 1明星院长； 2院长
	 * @apiSuccess {String} number          在职编号
	 * @apiSuccess {String} relegation      归属专家
	 * @apiSuccess {String} mobilePhone     手机号码
	 * @apiSuccess {Number} status          状态标识. 1:正常启用，0:禁用
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
	 *	        "total": 43353,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 2168,
	 *  	    "from": 1,
	 *	        "to": 20,
	 *	        "data": [
	 *	            {
	 *                  "id": 1,
     *                  "name": "满意",
     *                  "sex": 1,
     *                  "country": "韩国",
     *                  "workingLife": 5,
     *                  "level": 1,
     *                  "number": 6,
     *                  "relegation": "XIAOd，DDD",
     *                  "mobilePhone": "15988829966",
     *                  "status": 1
	 *	            }
	 *	        ]
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
    public function index(){
        $param = $this->param;
        $itemMainNum = isset( $param['itemMainNum'] ) ? $param['itemMainNum'] : 0;
        $keyword = isset( $param['keyword'] ) ? $param['keyword'] : '';
        $orderField = isset( $param['orderField'] ) ? $param['orderField'] : '';
        $orderBy = isset( $param['orderBy'] ) ? $param['orderBy'] : '';
        $page = isset($param['page']) ? max($param['page'],1) : 1;
        $pageSize = isset($param['page_size']) ? $param['page_size'] : 20;
        if( $pageSize <= 0 ) $pageSize = 20;
        if( $pageSize > 500 ) $pageSize = 500;
        if( (!$orderField && $orderBy) || ($orderField && !$orderBy) ) return $this->error('排序传值错误');
        $field = ['artificer_id as id','name','sex','country','working_life AS workingLife','level','number','pid','mobilephone as mobilePhone','status'];
        $obj = Artificer::select( $field )->whereRaw('type=2');
        //手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
        if( $itemMainNum != 0 && $keyword ){
            switch( $itemMainNum ){
                case 1:
                    $obj->where('name','like','%'.$keyword.'%');
                    break;
                case 2:
                    $obj->where(['number'=>$keyword]);
                    break;
                case 3:
                    $obj->where(['mobilephone'=>$keyword]);
                    break;
                case 4:
                    $artificer = Artificer::select(['artificer_id'])->where(['type'=>2,'name'=>$keyword])->first();
                    if(empty($artificer)) return $this->success();
                    $artificerId = $artificer->toArray()['artificer_id'];
                    $obj->whereRaw(' pid like "%'.$artificerId.'%"');
                    break;
                default:
                    break;
            }
        }
        if( !$orderField || !$orderBy )   $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
        if( $orderField && $orderBy ){ 
            switch( $orderField ){
                case 'status':
                    // 降序为 启用（1）在前 禁用（0）在后
                    $temp = ['asc'=>'desc','desc'=>'asc'];
                    $result = $obj->orderBy( $orderField,$temp[$orderBy])->paginate($pageSize)->toArray();
                    break;
                default:
                    $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
                    break;
            }
        }
        $result = $this->_formatListData( $result );
        $result = $this->_formatGetArtificerItemOver( $result );
//        var_dump($result);exit;
        return $this->success( $result );
    }
    /**
    * @api {Post} /assistant/add    2.专家助理添加
    * @apiName add
    * @apiGroup Assistant
    *
    * @apiParam {String} photo          必填,个人照片.
    * @apiParam {String} name           必填,姓名.
    * @apiParam {Number} sex            必填,性别 1.男 2.女
    * @apiParam {String} birthday       必填,生日 格式如 2015-02-22.
    * @apiParam {Number} level          必填,级别 1明星院长； 2院长.
    * @apiParam {String} number         必填,在职编号.
    * @apiParam {Number} workingLife    必填,工作年限.
    * @apiParam {String} pid            选填,所属专家. 如 1,2,3
    * @apiParam {String} introduce      必填,个性签名.
    * @apiParam {Number} credential     必填,证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
    * @apiParam {Number} cardId         必填,证件类型所对应的证件号码.
    * @apiParam {Number} mobilePhone    必填,电话.
    * @apiParam {Number} wechat         选填,微信.
    * @apiParam {Number} qq             选填,qq.
    * @apiParam {Number} email          选填,电子邮箱.
    *
    *
    * 
    * 
    * 
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
    *	{
    *	    "result": 1,
    *	    "token": "",
    *	    "data": []
    *	}
    *
    *
    * @apiErrorExample Error-Response:
    *		{
    *		    "result": 0,
    *		    "msg": "未授权访问"
    *		}
    */
    public function add(){
        $param = $this->param;
        $addData = $this->_formatReceiveData( $param );
        $addData['created_at'] = time();
        $lastId = Artificer::insertGetId( $addData );
        Event::fire('assistant.add','添加专家助理 id: '.$lastId);
        return $this->success();
    }
    /**
    * @api {Post} /assistant/update  3.专家助手编辑
    * @apiName update
    * @apiGroup Assistant
    *
    * @apiParam {Number} id             必填,专家助手id.
    * @apiParam {String} photo          必填,个人照片.
    * @apiParam {String} name           必填,姓名.
    * @apiParam {Number} sex            必填,性别 1.男 2.女
    * @apiParam {String} birthday       必填,生日 格式如 2015-02-22.
    * @apiParam {Number} level          必填,级别 1明星院长； 2院长.
    * @apiParam {String} number         必填,在职编号.
    * @apiParam {Number} workingLife    必填,工作年限.
    * @apiParam {String} introduce      必填,个性签名.
    * @apiParam {String} experience     必填,从业经历.
    * @apiParam {String} detail         必填,个人介绍JSON.
    * @apiParam {String} credential     必填,证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
    * @apiParam {String} cardId         必填,证件类型所对应的证件号码.
    * @apiParam {String} mobilePhone    必填,电话.
    * @apiParam {String} wechat         选填,微信.
    * @apiParam {String} qq             选填,qq.
    * @apiParam {String} email          选填,电子邮箱.
    *
    *
    * 
    * 
    * 
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
    *	{
    *	    "result": 1,
    *	    "token": "",
    *	    "data": []
    *	}
    *
    *
    * @apiErrorExample Error-Response:
    *		{
    *		    "result": 0,
    *		    "msg": "未授权访问"
    *		}
    */
    public function save(){
        $param = $this->param;
        $id = isset( $param['id'] ) ? $param['id'] : $this->error('职工id未填写');
        $saveData = $this->_formatReceiveData( $param );
        $saveData['updated_at'] = time();
        Artificer::where(['artificer_id'=>$id])->update( $saveData );
        Event::fire('assistant.update','编辑专家助理 id: '.$id);
        return $this->success();
    }
    /**
    * @api {Get} /assistant/show/:id  4.专家助理信息
    * @apiName show
    * @apiGroup Assistant
    *
    * @apiSuccess {Number} id             专家助手id
    * @apiSuccess {String} photo          个人照片.
    * @apiSuccess {String} name           姓名.
    * @apiSuccess {Number} sex            性别 1.男 2.女
    * @apiSuccess {String} birthday       生日 格式如 2015-02-22.
    * @apiSuccess {Number} level          级别 1明星院长； 2院长.
    * @apiSuccess {String} number         在职编号.
    * @apiSuccess {Number} workingLife    工作年限.
    * @apiSuccess {String} introduce      个性签名.
    * @apiSuccess {Number} credential     证件类型  1身份证； 2军官证； 3驾驶证； 4护照.
    * @apiSuccess {String} cardId         证件类型所对应的证件号码.
    * @apiSuccess {String} mobilePhone    电话.
    * @apiSuccess {String} wechat         微信.
    * @apiSuccess {String} qq             qq.
    * @apiSuccess {String} email          电子邮箱.
	* @apiSuccess {Number} status         状态标识. 1:正常启用，0:禁用
	* @apiSuccess {String} pid            选中的专家id
    * @apiSuccess {String} note           备注
	* @apiSuccess {Number} itemOver        完成项目数
	* @apiSuccess {String} createTime      录入时间
	* @apiSuccess {String} beautyItem['isGift']      赠送项目
	* @apiSuccess {String} beautyItem['noGift']      正常购买项目
	* @apiSuccess {String} beautyItem[xxx]['name']   分类项目名
	* @apiSuccess {String} beautyItem[xxx]['quantity']   分类项目消费数量
    *
    *
    * 
    * 
    * 
    * 
    *
    * 
    * @apiSuccessExample Success-Response:
    *	{
    *	    "result": 1,
    *	    "token": "",
    *	    "data": {
    *                   "id": 2,
    *                    "name": "LFR-T001",
    *                    "photo": "http://img01.choumei.cn/1/785973/201509281548144342652083878597311884.jpg",
    *                    "sex": 1,
    *                    "country": "韩国",
    *                    "birthday": "1968-06-15",
    *                    "credential": 1,
    *                    "cardId": "5555",
    *                    "mobilePhone": "13699854682",
    *                    "wechat": "155669877",
    *                    "qq": 888,
    *                    "email": "88@66.cn",
    *                    "level": 1,
    *                    "number": 1001,
    *                    "workingLife": 12,
    *                    "introduce": "我来介绍0001",
    *                    "status": 0,
    *                    "pid": "5,1,2",
    *                    "note": '',
    *                    "createTime": '2015-12-02',
    *                    "itemOver": 3,
    *                    "beautyItem": {
	*			            "isGift": [
	*			                {
	*			                    "name": "韩式无痛水光针（赠送）",
	*			                    "quantity": 1
	*			                }
	*			            ],
	*			            "noGift": [
	*			                {
	*			                    "name": "韩式无痛水光针",
	*			                    "quantity": 1
	*			                },
	*			                ...
	*			           ]
	*			        }
    *      }
    *	}
    *
    *
    * @apiErrorExample Error-Response:
    *		{
    *		    "result": 0,
    *		    "msg": "未授权访问"
    *		}
    */
    public function show($id){
        $field = [
            'artificer_id as id','name',
            'photo','sex','country',
            'birthday','credential','card_id as cardId',
            'mobilephone as mobilePhone','wechat','qq',
            'email','level','number',
            'working_life as workingLife','introduce',
            'status','pid','created_at as createTime','note'/*'detail','experience'*/
        ];
        $info = Artificer::select( $field )->where(['artificer_id'=>$id])->first();
        if( empty($info) ) return $this->error('没有找到专家信息哦');
        $info = $info->toArray();
        $info['mobilePhone'] = $info['mobilePhone'] ?:'';
        $info['qq'] = $info['qq'] ?:'';
        $info = $this->_formatShow( $info );
        return $this->success( $info );
    }
    /**
	 * @api {get} /assistant/up/:id 5.启用
	 * @apiName up
	 * @apiGroup Assistant
	 *
	 * @apiParam {Number} id 必填,职工id.
	 *
	 *
	 * 
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": []
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
    public function start($id){
        Artificer::where(['artificer_id'=>$id,'status'=>0])->update(['status'=>1]);
        Event::fire('assistant.up','启用专家助理 id: '.$id);
        return $this->success();
    }
    /**
	 * @api {get} /assistant/down/:id 6.禁用
	 * @apiName     down
	 * @apiGroup    Assistant
	 *
	 * @apiParam {Number} id 必填,职工id.
	 *
	 *
	 * 
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": []
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
    public function close($id){
        Artificer::where(['artificer_id'=>$id,'status'=>1])->update(['status'=>0]);
        Event::fire('assistant.down','禁用专家助理 id: '.$id);
        return $this->success();
    }
    /**
	 * @api {get} /assistant/checkNumberExists/:id 7.获取助理专家编码是否存在
	 * @apiName     checkNumberExists
	 * @apiGroup    Assistant
	 *
	 * @apiParam {Number} id              选填（新增的时候可不传）,职工id.
	 * @apiParam {Number} number          必填,助理专家编号.
	 *
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
     *          "exists":0
     *      }
	 *	}
	 */
    public function checkNumberExists( $id = 0 ){
        $param = $this->param;
        $number = isset( $param['number'] ) ? $param['number'] : $this->error('未填写助理专家编码');
        $number = 'M'.$number;
        $flag = $this->_checkNumberExists( $id , $number );
        if( !$flag ) return $this->success();
        return $this->error( '专家助理编号已存在', ERROR::ARTIFICER_NAME_EXISTS_ERROR );
    }
    /**
	 * @api {get} /assistant/checkNameExists/:id 8.获取助理专家名字是否存在
	 * @apiName checkNameExists
	 * @apiGroup Assistant
	 *
	 * @apiParam {Number} id            选填（新增的时候可不传）,职工id.
	 * @apiParam {String} name          必填,助理专家名字.
	 *
	 *
	 * @apiSuccess {Number} exists      状态标识. 0:不存在，1:存在
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
     *          "exists":0
     *      }
	 *	}
	 */
    public function checkNameExists( $id = 0 ){
        $param = $this->param;
        $name = isset( $param['name'] ) ? $param['name'] : $this->error('未填写专家名字');
        $flag = $this->_checkNameExists( $id , $name );
        if( !$flag ) return $this->success();
        return $this->error( '专家助理编号已存在', ERROR::ARTIFICER_NAME_EXISTS_ERROR );
    }
    /**
	 * @api {get} /assistant/getArtificer   9.获取专家名字
	 * @apiName     getArtificer
	 * @apiGroup    Assistant
	 *
     * 
	 *@apiSuccess {Number} id            选填（新增的时候可不传）,职工id.
	 *@apiSuccess {String} name          必填,助理专家名字.
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
     *          {
     *              "id": 1,
     *              "name": "XIAOd"
     *          }
     *      ]
	 *	}
	 */
    public function getArtificer(){
        $field = ['artificer_id as id','name'];
        $result = Artificer::select($field)->where(['type'=>1,'status'=>1])->get()->toArray();
        return $this->success( $result );
    }
    /**
	 * @api {post} /assistant/export     10.导出专家助手列表
	 * @apiName export
	 * @apiGroup Assistant
	 *
	 * @apiParam {String} itemMainNum       可选,主体搜索条件类型。  1.专家姓名 2.在职编号 3.手机号码 4.所属专家姓名
	 * @apiParam {String} keyword           可选,对应主体搜索关键词.
	 * @apiParam {String} orderField        可选, 排序字段支持 status 状态
	 * @apiParam {String} orderBy           可选, 排序方式 desc 降序 asc 升序
	 * @apiParam {Number} page              可选,第几页。 默认为第一页
	 * @apiParam {Number} page_size         可选,取多少条。 默认为20条
	 *
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": null
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
    public function export(){
        $param = $this->param;
        $itemMainNum = isset( $param['itemMainNum'] ) ? $param['itemMainNum'] : 0;
        $keyword = isset( $param['keyword'] ) ? $param['keyword'] : '';
        $orderField = isset( $param['orderField'] ) ? $param['orderField'] : '';
        $orderBy = isset( $param['orderBy'] ) ? $param['orderBy'] : '';
        $page = isset($param['page']) ? max($param['page'],1) : 1;
        $pageSize = isset($param['page_size']) ? $param['page_size'] : 20;
        if( $pageSize <= 0 ) $pageSize = 20;
        if( $pageSize > 500 ) $pageSize = 500;
        if( (!$orderField && $orderBy) || ($orderField && !$orderBy) ) return $this->error('排序传值错误');
        $field = ['artificer_id as id','name','sex','country','working_life AS workingLife','level','number','pid','mobilephone as mobilePhone','status'];
        $obj = Artificer::select( $field )->whereRaw('type=2');
        //手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
        if( $itemMainNum != 0 && $keyword ){
            switch( $itemMainNum ){
                case 1:
                    $obj->where(['name'=>$keyword]);
                    break;
                case 2:
                    $obj->where(['number'=>$keyword]);
                    break;
                case 3:
                    $obj->where(['mobilephone'=>$keyword]);
                    break;
                case 4:
                    $artificer = Artificer::select(['artificer_id'])->where(['type'=>1,'status'=>'NOM','name'=>$keyword])->first();
                    if(empty($artificer)) return $this->success();
                    $artificerId = $artificer->toArray()['artificer_id'];
                    $obj->whereRaw(' pid like "%'.$artificerId.'%"');
                    break;
                default:
                    break;
            }
        }
        if( !$orderField || !$orderBy )   $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
        if( $orderField && $orderBy ){ 
            switch( $orderField ){
                case 'status':
                    // 降序为 启用（1）在前 禁用（0）在后
                    $temp = ['asc'=>'desc','desc'=>'asc'];
                    $result = $obj->orderBy( $orderField,$temp[$orderBy])->paginate($pageSize)->toArray();
                    break;
                default:
                    $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
                    break;
            }
        }
        $result = $this->_formatListData( $result );
        $result = $this->_formatGetArtificerItemOver( $result );
        $t = $result['data'];
        $title = '专家查询列表' .date('Ymd');
        $header = ['助理姓名','性别','工作年限','在职编号','所属专家','手机号码','完成项目数','状态 '];
        $t1 = ['','男','女'];
        $t2 = ['','明星院长','院长'];
        $t3 = ['禁用','启用'];
        $tempData = [];
        foreach( $t as $key=>$val ){
            $tempData[$key][] = $val['name'];
            $tempData[$key][] = $t1[ $val['sex'] ];
            $tempData[$key][] = $val['workingLife'];
            $tempData[$key][] = $val['number'];
            $tempData[$key][] = $val['relegation'];
            $tempData[$key][] = $val['mobilePhone'];
            $tempData[$key][] = $val['itemOver'];
            $tempData[$key][] = $t3[ $val['status'] ];
            
        }
        Event::fire('assistant.export','导出专家助手查询列表');
        Excel::create($title, function($excel) use($tempData,$header){
            $excel->sheet('Sheet1', function($sheet) use($tempData,$header){
                $sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header);//添加表头
            });
        })->export('xls');
        exit;
    }
    /**
	 * @api {get} /assistant/search/:keyword   11.搜索专家助理名字
	 * @apiName     search
	 * @apiGroup    Assistant
	 *
     * 
	 * @apiParam {String} keyword           搜索关键词.
	 *
	 * @apiSuccess {Number} id             职工id.
	 * @apiSuccess {String} name           助理专家名字.
	 * @apiSuccess {String} number         助理专家编号.
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
    public function searchNameAndNumber( $fixStr = ''){
        if( empty( $fixStr ) ) return $this->success();
        $field = ['artificer_id as id','name','number'];
        $result = Artificer::select( $field )->whereRaw('(name like "%'. $fixStr .'%" OR number like "%'. $fixStr .'%") AND status=1 AND type=2')->get();
        return $this->success($result);
    }
    // 接收天界或者修改的数据
    private function _formatReceiveData( $param ){
        $data = [];
        $data['photo'] = $photo = isset( $param['photo'] ) ? $param['photo'] : $this->error('个人图片未填写');
        $data['name'] = $name = isset( $param['name'] ) ? $param['name'] : $this->error('专家姓名未填写');
        $data['sex'] = $gender = isset( $param['sex'] ) ? $param['sex'] : $this->error('性别未填写');
        $data['birthday'] = $birthday = isset( $param['birthday'] ) ? $param['birthday'] : $this->error('出生日期未填写');
        $data['number'] = $jobNumber = isset( $param['number'] ) ? $param['number'] : $this->error('在职编号未填写');
        $data['working_life'] = $jobYear = isset( $param['workingLife'] ) ? $param['workingLife'] : $this->error('工作年限未填写');
//         $data['pid'] = $pid = isset( $param['pid'] ) ? $param['pid'] : $this->error('所属专家未填写');
        $data['introduce'] = $signature = isset( $param['introduce'] ) ? $param['introduce'] : $this->error('个性签名未填写');
        // 证件类型
        $credentialType = isset( $param['credential'] ) ? $param['credential'] : 0;
        $credentialValue = isset( $param['cardId'] ) ? $param['cardId'] : '';
        $data['mobilephone'] = $mobilePhone = isset( $param['mobilePhone'] ) ? $param['mobilePhone'] : '';
        $pid = '';
        if( empty($credentialType) && empty($credentialValue) ) return $this->error('身份证件未填写');
        if( empty($mobilePhone)) return $this->error('手机号码未填写');
        if( isset( $param['pid'] ) && !empty($param['pid']) ) {
        	$pid = $data['pid'] = $param['pid'];
        }
        if( !empty($pid) && count(explode(',',$pid))>3 ) return $this->error('pid传值错误，归属专家只能最多只能选择3个');
        $data['wechat'] = $wechat = isset( $param['wechat'] ) ? $param['wechat'] : '';
        $data['qq'] = $qq = isset( $param['qq'] ) ? $param['qq'] : '';
        $data['email'] = $email = isset( $param['email'] ) ? $param['email'] : ''; 
        $data['note'] = $note = isset( $param['note'] ) ? $param['note'] : '';
        $data['country'] = '';
        $data['level'] = '';
        if( $credentialType && $credentialValue ){ 
            $data['credential'] = $credentialType;
            $data['card_id'] = $credentialValue;
        }
        // 检验 专家姓名 和 专家编号是否存在
        if( isset( $param['id'] ) && !empty( $param['id'] ) ){
            $nameExists = $this->_checkNameExists( $param['id'] , $name );
            $numberExists = $this->_checkNumberExists( $param['id'] , $jobNumber );
        }else{
            $nameExists = $this->_checkNameExists( 0 , $name );
            $numberExists = $this->_checkNumberExists( 0 , $jobNumber );
        }
        if( $nameExists || $numberExists ){
            return $this->error( '专家名字或者编号有重复哦~' );
        }
        return $data;
    }
    // 格式化返回列表信息
    private function _formatListData( $result ){
        $tempAll = [];
        $tempItem = [];
        $tempName = [];
        foreach( $result['data'] as $val ){
           $pid = explode( ',',$val['pid'] );
           array_push( $tempItem , $pid );
           $tempAll = array_merge( $tempAll , $pid );
        }
        $uniqueArr = array_unique($tempAll);
        foreach( $uniqueArr as $val ){
            $name = $this->_getArtificerNameById( $val );
            if($name)  $tempName[ $val ] = $name;
        }
        return $this->_formatSigleToString( $result,$tempItem,$tempName );
    }
    // 根据职工id获取职工的姓名
    private function _getArtificerNameById( $artificerId ){
        $field = ['name'];
        $name = Artificer::select($field)->where(['artificer_id'=>$artificerId,'status'=>1])->first();
        if(empty($name)) return false;
        return $name->toArray()['name'];
    }
    // 工具方法，将 101,102 这样的格式转换为对应的名字 如 101对应"我" 102对应"他" ===> 我,他
    private function _formatSigleToString($result,$tempItem,$tempName){
        foreach( $tempItem as $key => $val ){
            $str = '';
            foreach( $val as $v ){
                if( isset( $tempName[$v] ) ) $str .= '，'.$tempName[ $v ];
            }
            $result['data'][$key]['relegation'] = ltrim($str,'，');
            unset( $result['data'][$key]['pid'] );
        }
        return $result;
    }
    private function _checkNumberExists( $id=0 , $number='' ){
        if(empty($number)) return false;
        $exists = Artificer::select(['artificer_id as id'])->where(['number'=>$number])->whereRaw('type=2')->first();
        if( empty($exists) ) return false;
        $exists = $exists->toArray();
        if( $id == $exists['id'] ) return false;
        return true;
    }
    private function _checkNameExists( $id = 0 , $name='' ){
        if(empty($name)) return false;
        $exists = Artificer::select(['artificer_id as id'])->where(['name'=>$name])->whereRaw('type=2')->first();
        if( empty($exists) ) return false;
        $exists = $exists->toArray();
        if( $id == $exists['id'] ) return false;
        return true;
    }
    private function _formatGetArtificerItemOver( $result ){
    
    	$temp = [];
    	$field = ['order_sn as orderSn','quantity','assistantId as id'];
    	$tempOrder = [];
    	$queryOrder = [];
    
    	foreach( $result['data'] as $k => $v ){
    		$temp[] = $v['id'];
    	}
    	$allArtificer = BeautyOrderItem::select($field)->whereIn('assistantId',$temp)->get()->toArray();
    	
    	if(empty($allArtificer)) {
    		foreach($result['data'] as $k => $v){
    			$result['data'][$k]['itemOver'] = 0;
    			$result['data'][$k]['itemOverApp'] = 0;
//     			$result['data'][$k]['createTime'] = date('Y-m-d',$v['createTime']);
    		}
    		return $result;
    	}
    	foreach($allArtificer as $k => $v ){
    		$tempOrder[] = $v['orderSn'];
    	}
    	$overItem = [];
    	$orderSn = BookingOrder::select(['order_sn as orderSn'])->whereIn('ORDER_SN',$tempOrder)->where(['STATUS'=>'CSD'])->get()->toArray();
    	foreach($orderSn as $k => $v ){
    		$queryOrder[ $v['orderSn'] ] = $v['orderSn'];
    	}
    	foreach($allArtificer as $k => $v ){
    		if( !isset($overItem[ $v['id'] ]) ) $overItem[ $v['id'] ] = 0;
    		if( isset($queryOrder[ $v['orderSn'] ]) && !empty($queryOrder[ $v['orderSn'] ]) ){
    			$overItem[ $v['id'] ] += $v['quantity'];
    		}
    	}
    	foreach($result['data'] as $k => $v){
    		if( isset($overItem[ $v['id'] ]) ){
    			$result['data'][$k]['itemOver'] = $overItem[ $v['id'] ];
    		}else{
    			$result['data'][$k]['itemOver'] = 0;
    		}
//     		$result['data'][$k]['createTime'] = date('Y-m-d',$v['createTime']);
    	}
    	return $result;
    }
    private function _formatShow( $info ){
    	$field = ['order_sn as orderSn','quantity','assistantId as id','item_id as itemId'];
    	$tempOrder = [];
    	$queryOrder = [];
    	$allArtificer = BeautyOrderItem::select($field)->where(['assistantId'=>$info['id']])->get()->toArray();
    	$beautyItem = $this->_getBeautyItem();
    	if( empty($allArtificer) ){
    		$info['itemOver'] = 0;
    
    		foreach( $beautyItem as $k => $v ){
    			foreach( $v as $k1 => &$v1 ){
    				unset( $beautyItem[$k][$k1]['itemId'] );
    			}
    		}
    		$info['beautyItem'] = $beautyItem;
    		return $info;
    	}
    	 
    	foreach($allArtificer as $k => $v ){
    		$tempOrder[] = $v['orderSn'];
    	}
    	$overItem = [];
    	$orderSn = BookingOrder::select(['order_sn as orderSn'])->whereIn('ORDER_SN',$tempOrder)->where(['STATUS'=>'CSD'])->get()->toArray();
    	 
    	foreach($orderSn as $k => $v ){
    		$queryOrder[ $v['orderSn'] ] = $v['orderSn'];
    	}
    	$tempItem = [];
    	foreach( $allArtificer as $k => $v ){
    		if( !isset($overItem[ $v['id'] ]) ) $overItem[ $v['id'] ] = 0;
    		if( isset($queryOrder[ $v['orderSn'] ]) && !empty($queryOrder[ $v['orderSn'] ]) ){
    			$overItem[ $v['id'] ] += $v['quantity'];
    			if( !isset( $tempItem[ $v['itemId'] ] ) ) $tempItem[ $v['itemId'] ] = 0;
    			$tempItem[ $v['itemId'] ] += $v['quantity'];
    		}
    	}
    	if( empty( $overItem ) ){
    		$info['itemOver'] = 0;
    		foreach( $beautyItem as $k => $v ){
    			foreach( $v as $k1 => &$v1 ){
    				unset( $beautyItem[$k][$k1]['itemId'] );
    			}
    		}
    		$info['beautyItem'] = $beautyItem;
    		return $info;
    	}
    	 
    	$info['itemOver'] = $overItem[ $info['id'] ];
    	foreach( $beautyItem as $k => $v ){
    		foreach( $v as $k1 => &$v1 ){
    			if( isset($tempItem[ $v1['itemId'] ]) ) $beautyItem[$k][$k1]['quantity'] = $tempItem[ $v1['itemId'] ];
    			unset( $beautyItem[$k][$k1]['itemId'] );
    		}
    	}
    	$info['beautyItem'] = $beautyItem;
    	$info['createTime'] = date('Y-m-d',$info['createTime']);
    	return $info;
    }
    private function _getBeautyItem(){
    	$beautyItem = BeautyItem::select(['item_id as itemId','name','is_gift as isGift'])->get()->toArray();
    	$groupItem = [];
    	$groupItem['isGift'] = [];
    	$groupItem['noGift'] = [];
    	foreach( $beautyItem as $k => &$v ){
    		$isGift = $v['isGift'];
    		//     		对应分类已完成的数量
    		$v['quantity'] = 0;
    		unset( $v['isGift'] );
    		if( $isGift ){
    			$groupItem['isGift'][] = $v;
    		}else{
    			$groupItem['noGift'][] = $v;
    		}
    	}
    	return $groupItem;
    }
}