<?php

namespace App\Http\Controllers\Artificer;

use App\Http\Controllers\Controller;
use DB;
use Event;
use Excel;
use App\Artificer;
use App\ArtificerWorks;
use App\FileImage;
use App\BeautyOrderItem;
use App\BookingOrder;
use App\BeautyItem;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ERROR;

class ArtificerController extends Controller{
	const OVER_BASE_NUM = 0;
	const OVER_FACTOR = 100;
    /**
	 * @api {post} /artificer/index     1.专家列表
	 * @apiName index
	 * @apiGroup Artificer
	 *
	 * @apiParam {String} itemMainNum       可选,主体搜索条件类型。  1.专家姓名 2.在职编号
	 * @apiParam {String} keyword           可选,对应主体搜索关键词.
	 * @apiParam {String} orderField        可选, 排序字段目前只有两个 level 级别 status 状态
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
	 * @apiSuccess {Number} status          状态标识. 1:正常启用，0:禁用
	 * @apiSuccess {Number} itemOver        完成项目数
	 * @apiSuccess {Number} itemOverApp     APP显示完成数
	 * @apiSuccess {String} createTime      录入时间
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
     *                  "status": 1,
     *                  "itemOver": 0,
     *                  "createTime": '2015-12-03',
     *                  "itemOverApp": 0
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
        $field = ['artificer_id as id','name','sex','country','working_life AS workingLife','level','number','status','created_at as createTime'];
        $obj = Artificer::select( $field )->where(['type'=>1]);
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
                case 'level':
                    $result = $obj->orderBy( $orderField,$orderBy)->paginate($pageSize)->toArray();
                    break;
                default:
                    $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
                    break;
            }
        }
//         增加项目完成数统计
//         print_r( $result );
		$result = $this->_formatGetArtificerItemOver( $result );
//        print_r( $result );exit;
        return $this->success( $result );
    }
    /**
    * @api {Post} /artificer/add    2.专家添加
    * @apiName add
    * @apiGroup Artificer
    *
    * @apiParam {String} photo          必填,个人照片.
    * @apiParam {String} pageImage    必填  主页图片
    * @apiParam {String} name           必填,姓名.
    * @apiParam {Number} sex            必填,性别 1.男 2.女
    * @apiParam {String} country        必填,韩国.
    * @apiParam {String} birthday       必填,生日 格式如 2015-02-22.
    * @apiParam {Number} level          必填,级别 1明星院长； 2院长.
    * @apiParam {String} number         必填,在职编号.
    * @apiParam {Number} workingLife    必填,工作年限.
    * @apiParam {String} introduce      必填,个性签名.
    * @apiParam {String} experience     必填,从业经历.
    * @apiParam {String} detail         必填,个人介绍JSON.[{"title":"我很好溜哦","content":"你好呀"},{"title":"ni我很好溜哦","content":"你好呀1"}] title:标题 content:内容
    * @apiParam {Number} credential     选填,证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
    * @apiParam {String} cardId         选填,证件类型所对应的证件号码.
    * @apiParam {String} mobilePhone    选填,电话.
    * @apiParam {String} wechat         选填,微信.
    * @apiParam {String} qq             选填,qq.
    * @apiParam {String} email          选填,电子邮箱.
    * @apiParam {String} note           备注
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
        Event::fire('artificer.add','添加专家数据: '.$lastId);
        return $this->success();
    }
    /**
    * @api {Post} /artificer/update  3.专家编辑
    * @apiName update
    * @apiGroup Artificer
    *
    * @apiParam {Number} id             必填,专家id.
    * @apiParam {String} photo          必填,个人照片.
    * @apiParam {String} pageImage    必填  主页图片
    * @apiParam {String} name           必填,姓名.
    * @apiParam {Number} sex            必填,性别 1.男 2.女
    * @apiParam {String} country        必填,韩国.
    * @apiParam {String} birthday       必填,生日 格式如 2015-02-22.
    * @apiParam {Number} level          必填,级别 1明星院长； 2院长.
    * @apiParam {String} number         必填,在职编号.
    * @apiParam {Number} workingLife    必填,工作年限.
    * @apiParam {String} introduce      必填,个性签名.
    * @apiParam {String} experience     必填,从业经历.
    * @apiParam {String} detail         必填,个人介绍JSON.
    * @apiParam {String} credential     选填,证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
    * @apiParam {String} cardId         选填,证件类型所对应的证件号码.
    * @apiParam {String} mobilePhone    选填,电话.
    * @apiParam {String} wechat         选填,微信.
    * @apiParam {String} qq             选填,qq.
    * @apiParam {String} email          选填,电子邮箱.
    * @apiParam {String} note           备注
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
        Event::fire('artificer.update','编辑专家数据: '.$id);
        return $this->success();
    }
    /**
    * @api {Get} /artificer/show/:id  4.专家信息
    * @apiName show
    * @apiGroup Artificer
    *
    * @apiSuccess {Number} id             专家id
    * @apiSuccess {String} photo          个人照片.
    * @apiSuccess {String} pageImage      主页照片.
    * @apiSuccess {String} name           姓名.
    * @apiSuccess {Number} sex            性别 1.男 2.女
    * @apiSuccess {String} country        韩国.
    * @apiSuccess {String} birthday       生日 格式如 2015-02-22.
    * @apiSuccess {Number} level          级别 1明星院长； 2院长.
    * @apiSuccess {String} number         在职编号.
    * @apiSuccess {Number} workingLife    工作年限.
    * @apiSuccess {String} introduce      个性签名.
    * @apiSuccess {String} experience     从业经历.
    * @apiSuccess {String} detail         个人介绍JSON.[{"title":"我很好溜哦","content":"你好呀"},{"title":"ni我很好溜哦","content":"你好呀1"}] title:标题 content:内容
    * @apiSuccess {String} credential     证件类型 0无填写类型 1身份证； 2军官证； 3驾驶证； 4护照.
    * @apiSuccess {String} cardId         证件类型所对应的证件号码.
    * @apiSuccess {String} mobilePhone    电话.
    * @apiSuccess {String} wechat         微信.
    * @apiSuccess {String} qq             qq.
    * @apiSuccess {String} email          电子邮箱.
    * @apiSuccess {String} note           备注
	* @apiSuccess {Number} status         状态标识. 1:正常启用，0:禁用
	* @apiSuccess {Number} itemOver        完成项目数
	* @apiSuccess {Number} itemOverApp     APP显示完成数
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
    * 
    * @apiSuccessExample Success-Response:
    *	{
    *	    "result": 1,
    *	    "token": "",
    *	    "data": {
    *                   "id": 2,
    *                    "name": "LFR-T001",
    *                    "photo": "http://img01.choumei.cn/1/785973/201509281548144342652083878597311884.jpg",
    *                    "pageImage": "http://img01.choumei.cn/1/785973/201509281548144342652083878597311884.jpg",
    *                    "sex": 1,
    *                    "country": "韩国",
    *                    "birthday": "1968-06-15",
    *                    "credential": 1,
    *                    "cardId": "5555",
    *                    "mobilePhone": "13699854682",
    *                    "wechat": "155669877",
    *                    "qq": 888,
    *                    "email": "88@66.cn",
    *                    "note": "88@66.cn",
    *                    "level": 1,
    *                    "number": 1001,
    *                    "workingLife": 12,
    *                    "introduce": "我来介绍0001",
    *                    "experience": "6",
    *                    "detail": "[{\"title\":\"我很好溜哦\",\"content\":\"你好呀\"},{\"title\":\"ni我很好溜哦\",\"content\":\"你好呀1\"}]",
    *                    "status": 0,
    *                    "note": '',
    *                    "createTime": '2015-12-02',
    *                    "itemOver": 3,
    *                    "itemOverApp": 300, 
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
            'photo','page_image as pageImage','sex','country',
            'birthday','credential','card_id as cardId',
            'mobilephone as mobilePhone','wechat','qq',
            'email','level','number',
            'working_life as workingLife','introduce','experience',
            'detail','status','note','created_at as createTime'
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
	 * @api {get} /artificer/up/:id 5.启用
	 * @apiName up
	 * @apiGroup Artificer
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
        Event::fire('artificer.up','启用专家 id: '.$id);
        return $this->success();
    }
    /**
	 * @api {get} /artificer/down/:id 6.禁用
	 * @apiName down
	 * @apiGroup Artificer
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
        Event::fire('artificer.down','禁用专家 id: '.$id);
        return $this->success();
    }
    /**
	 * @api {get} /artificer/checkNumberExists/:id 7.获取专家编码是否存在
	 * @apiName checkNumberExists
	 * @apiGroup Artificer
	 *
	 * @apiParam {Number} id            选填（新增的时候可不传）,职工id.
	 * @apiParam {Number} number        必填,专家编号.
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
    public function checkNumberExists( $id=0 ){
        $param = $this->param;
        $number = isset( $param['number'] ) ? $param['number'] : $this->error('未填写专家编码');
        $number = '1'.$number;
        $flag = $this->_checkNumberExists( $id , $number );
        if( !$flag ) return $this->success();
        return $this->error( '专家编号已存在', ERROR::ARTIFICER_NAME_EXISTS_ERROR );
    }
    /**
	 * @api {get} /artificer/checkNameExists/:id 8.获取专家名字是否存在
	 * @apiName checkNameExists
	 * @apiGroup Artificer
	 *
	 * @apiParam {Number} id            选填（新增的时候可不传）,职工id.
	 * @apiParam {Number} name          必填,专家名字.
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
    public function checkNameExists( $id = 0 ){
        $param = $this->param;
        $name = isset( $param['name'] ) ? $param['name'] : $this->error('未填写专家名字');
        $flag = $this->_checkNameExists( $id , $name );
        if( !$flag ) return $this->success();
        return $this->error( '专家名称已存在', ERROR::ARTIFICER_NAME_EXISTS_ERROR );
    }
     /**
	 * @api {post} /artificer/export     9.导出专家列表
	 * @apiName     export
	 * @apiGroup    Artificer
	 *
	 * @apiParam {String} itemMainNum       可选,主体搜索条件类型。  1.专家姓名 2.在职编号
	 * @apiParam {String} keyword           可选,对应主体搜索关键词.
	 * @apiParam {String} orderField        可选, 排序字段目前只有两个 level 级别 status 状态
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
	 *	}
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
        $field = ['name','sex','country','working_life AS workingLife','level','number','status','created_at as createTime'];
        $obj = Artificer::select( $field )->where(['type'=>1]);
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
                default:
                    break;
            }
        }
        if( !$orderField || !$orderBy )   $result = $obj->orderBy('artificer_id','desc')->paginate($pageSize)->toArray();
        if( $orderField && $orderBy ){ 
            switch( $orderField ){
                case 'status':
                    // 降序为 启用（1）在前 禁用（0）在后
                    $temp = ['asc'=>'desc','desc'=>'asc'];
                    $result = $obj->orderBy( $orderField,$temp[$orderBy])->paginate($pageSize)->toArray();
                    break;
                case 'level':
                    $result = $obj->orderBy( $orderField,$orderBy)->paginate($pageSize)->toArray();
                    break;
                default:
                    $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
                    break;
            }
        }
		$result = $this->_formatGetArtificerItemOver( $result );
        $temp = $result['data'];
        $title = '专家查询列表' .date('Ymd');
        $header = ['专家姓名','性别','国籍','工作年限','级别','在职编号','录入时间','完成项目数','APP显示完成数','状态 '];
        $t1 = ['','男','女'];
        $t2 = ['','明星院长','院长'];
        $t3 = ['禁用','启用'];
        $tempData = [];
        foreach( $temp as $k=>$v ){
        	$tempData[$k][] = $v['name'];
        	$tempData[$k][] = $t1[ $v['sex'] ];
        	$tempData[$k][] = $v['country'];
        	$tempData[$k][] = $v['workingLife'];
        	$tempData[$k][] = $t2[ $v['level'] ];
        	$tempData[$k][] = $v['number'];
        	$tempData[$k][] = $v['createTime'];
        	$tempData[$k][] = $v['itemOver'];
        	$tempData[$k][] = $v['itemOverApp'];
        	$tempData[$k][] = $t3[ $v['status'] ];
        }
        Event::fire('artificer.export','导出专家查询列表');
        Excel::create($title, function($excel) use($tempData,$header){
            $excel->sheet('Sheet1', function($sheet) use($tempData,$header){
                $sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header);//添加表头
            });
        })->export('xls');
        exit;
    }
    /**
	 * @api {get} /artificer/search/:keyword   10.搜索专家名字
	 * @apiName     search
	 * @apiGroup    Artificer
	 *
     * 
	 * @apiParam {String} keyword           搜索关键词.
	 *
	 * @apiSuccess {Number} id              职工id.
	 * @apiSuccess {String} name            专家名字.
	 * @apiSuccess {String} number          专家编号.
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
        $result = Artificer::select( $field )->whereRaw('(name like "%'. $fixStr .'%" OR number like "%'. $fixStr .'%") AND  status=1 AND type=1')->get();
        return $this->success($result);
    }
    /**
     * @api {post} /artificer/worksCreate  	11.新增作品集合
     * @apiName 	worksCreate
     * @apiGroup  	Artificer
     *
     * @apiParam {Number} id 必填,专家ID.
     * @apiParam {String} description 选填,作品描述.
     * @apiParam {String} img  必填,[originImg]作品集合. eg:"www,ee,qq,bb"    作品原图路径集合，以逗号隔开
     *
     *
     * @apiSuccessExample Success-Response:
     *	{
     *	    "result": 1,
     *	    "msg": "",
     *	    "data": {
     *	    }
     *	}
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "创建作品失败"
     *		}
     */
    public function uploadWorks(){
    	$param = $this->param;
    	
    	if( !isset($param['id']) || empty($param['id']) ) return $this->error('专家id未传递');
    	if( !isset($param['img']) || empty($param['img']) ) return $this->error('专家作品图片未传递');
    	$artificerId = $param['id'];
    	if( !$this->_checkArtificerExists($artificerId) ) return $this->error('此专家不存在或未被启用');
    	$insertImgFile['url'] = '';
    	$insertImgFile['file_name'] = '';
    	$insertImgFile['width'] = '';
    	$insertImgFile['height'] = '';
    	$insertImgFile['orientation'] = 'Top-LEFT';
    	$insertImgFile['create_time'] = time();
    	$insertImgFile['status'] = 1;
    	
    	$img = json_decode($param['img'],true);
    	if( !is_array($img) || empty($img) ) return $this->error('专家图片参数格式错误');
    	
    	$insertArtificerWorks['artificer_id'] = $artificerId;
    	$insertArtificerWorks['image_ids'] = '';
    	$insertArtificerWorks['status'] = 'ON';
    	$insertArtificerWorks['add_time'] = time();
    	if( isset($param['description'])  ) 
    		$insertArtificerWorks['description'] = $param['description'];
    	
    	$imageIds = [];
    	for($i=0,$len=count($img);$i<$len;$i++){
    		$urlParsed = parse_url($img[$i]);
    		$imageUrl = $urlParsed['scheme'].'://'.$urlParsed['host'].$urlParsed['path'];
    		if ($urlParsed['path']){
    			$insertImgFile['file_name']=$urlParsed['path'];
    		}
    		$aa=getimagesize($imageUrl);
    		$insertImgFile['width']=$aa["0"];	////获取图片的宽
    		$insertImgFile['height']=$aa["1"];	///获取图片的高
    		$insertImgFile['url']=$imageUrl;
    		$insertFileImageId = FileImage::insertGetId( $insertImgFile );
    		$imageIds[] = $insertFileImageId;
    	}
    	$insertArtificerWorks['image_ids'] = join(',', $imageIds);
    	$worksId = ArtificerWorks::insertGetId( $insertArtificerWorks );
    	Event::fire('assistant.updateIds','添加专家作品，作品集id: '.$worksId);
    	if(empty($worksId))	return $this->error('创建作品失败');
    	return $this->success();
    }
    /**
     * @api {post} /artificer/updateIds  12.修改作品集合
     * @apiName 	updateIds
     * @apiGroup  	Artificer
     *
     * @apiParam {Number} id 必填,专家id.
     * @apiParam {Number} recId 必填,作品id.
     * @apiParam {String} imgIds 必填,[workId]作品集合.eg:  "1,2,3"  作品ID集合，以逗号隔开
     *
     *
     * @apiSuccessExample Success-Response:
     *	{
     *	    "result": 1,
     *	    "msg": "",
     *	    "data": {
     *	    }
     *	}
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "修改作品失败"
     *		}
     */
   	public function updateWorks(){
   		$param = $this->param;
    	if( !isset($param['id']) || empty($param['id']) ) return $this->error('专家id未传递');
   		if( !isset( $param['recId'] ) || empty($param['recId'])) return $this->error('未传递作品id');
   		if( !isset( $param['imgIds'] ) ) return $this->error('未传递作品图片集');
   		
   		$artificerId = $param['id'];
    	if( !$this->_checkArtificerExists($artificerId) ) return $this->error('此专家不存在或未被启用');
   		
   		$recId = $param['recId'];
   		$imgIds = $param['imgIds'];

   		$imgIds = trim($imgIds,',');
   		$tempImgIds = explode(',', $imgIds);
   		$oldImgs = ArtificerWorks::select(['image_ids'])->where(['id'=>$recId,'status'=>'ON'])->first();
   		
   		if( empty($oldImgs) ) return $this->error('数据出错，请联系管理员');
   		$oldImgs = $oldImgs->toArray();
   		
   		$oldImgs = explode(',', $oldImgs['image_ids']);
   		
   		$diffImgs = array_diff($oldImgs,$tempImgIds);
   		if(empty($imgIds)){
   			$update = ArtificerWorks::where(['id'=>$recId,'status'=>'ON'])->update(['status'=>'OFF','image_ids'=>'']);
   		}else{
   			$update = ArtificerWorks::where(['id'=>$recId,'status'=>'ON'])->update(['image_ids'=>$imgIds]);
   		}
   		Event::fire('assistant.updateIds','编辑专家作品，作品集id: '.$recId);
   		if(!empty($diffImgs)) 	FileImage::whereIn('id',$diffImgs)->update(['status'=>0]);
   		if( empty($update) )	return $this->error('修改作品失败');
   		return $this->success();
   	}
   	/**
   	 * @api {post} /artificer/updateDesc  13.修改作品描述
   	 * @apiName 	updateDesc
   	 * @apiGroup  	Artificer
   	 *
   	 * @apiParam {Number} id 必填,专家id.
   	 * @apiParam {Number} recId 必填,作品id.
   	 * @apiParam {String} description 必填,作品描述
   	 *
   	 *
   	 * @apiSuccessExample Success-Response:
   	 *	{
   	 *	    "result": 1,
   	 *	    "msg": "",
   	 *	    "data": {
   	 *	    }
   	 *	}
   	 *
   	 *
   	 * @apiErrorExample Error-Response:
   	 *		{
   	 *		    "result": 0,
   	 *		    "msg": "修改作品描述失败"
   	 *		}
   	 */
   	public function updateWorksDesc(){
   		$param = $this->param;
   		if( !isset($param['id']) || empty($param['id']) ) return $this->error('专家id未传递');
   		if( !isset( $param['recId'] ) || empty($param['recId'])) return $this->error('未传递作品id');
   		if( !isset( $param['description'] ) ) return $this->error('未传递作品描述');
   		 
   		$artificerId = $param['id'];
   		if( !$this->_checkArtificerExists($artificerId) ) return $this->error('此专家不存在或未被启用');
   		
   		ArtificerWorks::where(['id'=>$param['recId'],'status'=>'ON'])->update(['description'=>$param['description']]);
   		Event::fire('assistant.updateDesc','编辑专家作品描述，作品集id: '.$param['recId']);
   		return $this->success();
   	}
   	/**
   	 * @api {post} /artificer/workIndex/:id 	14.专家的作品列表
   	 * @apiName 	workIndex
   	 * @apiGroup 	Artificer
   	 *
   	 * @apiParam {String} id 	必填,专家ID.
   	 *
   	 * @apiSuccess {Number} id 作品ID.
   	 * @apiSuccess {Number} artificerId 专家ID.
   	 * @apiSuccess {String} imageIds 	图片id排序 eg: 1,2,3,4
   	 * @apiSuccess {String} addTime 	添加时间
   	 * @apiSuccess {String} description 作品描述.
   	 * @apiSuccess {String} works['img'] 新版本作品集合  以（作品集和作品集缩略图）为一个单元.
   	 * @apiSuccess {String} works['img']['worksId'] 图片url对应图片id.
   	 * @apiSuccess {String} works['img']['originImg] 图片url.
   	 *
   	 * @apiSuccessExample Success-Response:
   	 * {
   	 *    "result":1,
   	 *    "token":"",
   	 *    "data":
   	 *    {
   	 *           "works":
   	 *           [
   	 *              {
   	 *                   "id":1,
   	 *                   "artificerId":38,
   	 *                   "imageIds":"1,2,3,4",
   	 *                   "addTime":1444820916,
   	 *                   "description":"\u8fd9\u662f\u4e00\u4e2a\u63cf\u8ff01",
   	 *                   "img":
   	 *                   [
   	 *                           {
   	 *                               "worksId":1,
   	 *                               "originImg":"http:\/\/img01.choumei.cn\/1\/785973\/201510081147144427607355578597387596.jpg"
   	 *                           },
   	 *                          ......
   	 *                           {
   	 *                               "worksId":4,
   	 *                               "originImg":"http:\/\/img01.choumei.cn\/1\/785973\/201509281548144342652083878597311884.jpg"
   	 *                           }
   	 *                   ]
   	 *               }
   	 *            ]
   	 *    }
   	 *  }
   	 *
   	 *
   	 * @apiErrorExample Error-Response:
   	 *		{
   	 *		    "result": 0,
   	 *		    "msg": "未授权访问"
   	 *		}
   	 */
   	public function showWorks( $id=0 ){
   		
   		$artificerId = $id;
   		
   		if( !$this->_checkArtificerExists($artificerId) ) return $this->error('此专家不存在或未被启用');
   		
   		$artificerWorks = ArtificerWorks::select(['image_ids as imageIds','description','add_time as addTime','id','artificer_id as artificerId'])->where(['status'=>'ON','artificer_id'=>$artificerId])->orderBy('add_time','desc')->get()->toArray();
   		
   		$works = [];
   		if(empty($artificerWorks)) return $this->success($works);
   		
   		$works = $this->_formatWorks( $artificerWorks );
   		return $this->success( $works );
   	}
    // 接收添加或者修改的数据
    private function _formatReceiveData( $param ){
        $data = [];
        $data['photo'] = $photo = isset( $param['photo'] ) ? $param['photo'] : $this->error('个人图片未填写');
        $data['page_image'] = $pageImage = isset( $param['pageImage'] ) ? $param['pageImage'] : $this->error('主页图片未填写');
        $data['name'] = $name = isset( $param['name'] ) ? $param['name'] : $this->error('专家姓名未填写');
        $data['sex'] = $gender = isset( $param['sex'] ) ? $param['sex'] : $this->error('性别未填写');
        $data['country'] = $country = isset( $param['country'] ) ? $param['country'] : $this->error('国籍未填写');
        $data['birthday'] = $birthday = isset( $param['birthday'] ) ? $param['birthday'] : $this->error('出生日期未填写');
        $data['level'] = $level = isset( $param['level'] ) ? $param['level'] : $this->error('级别未填写');
        $data['number'] = $jobNumber = isset( $param['number'] ) ? $param['number'] : $this->error('在职编号未填写');
        $data['working_life'] = $jobYear = isset( $param['workingLife'] ) ? $param['workingLife'] : $this->error('工作年限未填写');
        $data['introduce'] = $signature = isset( $param['introduce'] ) ? $param['introduce'] : $this->error('个性签名未填写');
        $data['experience'] = $jobEmpiric = isset( $param['experience'] ) ? $param['experience'] : $this->error('从业经验未填写');
        $data['detail'] = $jobDetail = isset( $param['detail'] ) ? $param['detail'] : $this->error('个人介绍未填写');
        // 证件类型
        $credentialType = isset( $param['credential'] ) ? $param['credential'] : 0;
        $credentialValue = isset( $param['cardId'] ) ? $param['cardId'] : '';
        $data['mobilephone'] = $mobilePhone = isset( $param['mobilePhone'] ) ? $param['mobilePhone'] : '';
        $data['wechat'] = $wechat = isset( $param['wechat'] ) ? $param['wechat'] : '';
        $data['qq'] = $qq = isset( $param['qq'] ) ? $param['qq'] : '';
        $data['email'] = $email = isset( $param['email'] ) ? $param['email'] : '';
        $data['note'] = $note = isset( $param['note'] ) ? $param['note'] : '';
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
    private function _checkNameExists( $id = 0 , $name='' ){
        if(empty($name)) return false;
        $exists = Artificer::select(['artificer_id as id'])->where(['name'=>$name])->where(['type'=>1])->first();
        if( empty($exists) ) return false;
        $exists = $exists->toArray();
        if( $id == $exists['id'] ) return false;
        return true;
    }
    private function _checkNumberExists( $id=0 , $number=''){
        if(empty($number)) return false;
        $exists = Artificer::select(['artificer_id as id'])->where(['number'=>$number])->where(['type'=>1])->first();
        if( empty($exists) ) return false;
        $exists = $exists->toArray();
        if( $id == $exists['id'] ) return false;
        return true;
    }
    private function _checkArtificerExists( $id = 0 ){
    	if( empty($id) ) return false;
    	$exists = Artificer::whereRaw('artificer_id='.$id.' AND type=1 AND status=1')->count();
    	if( empty($exists) ) return false;
    	return true;
    }
    private function _formatWorks( $artificerWorks = [] ){
    	$works = [];
    	foreach( $artificerWorks as $k => $v ){
    		$tempImgIds = '';
    		$works[$k] = [];
    		$works[$k]['id'] = $v['id']; // 作品集id
    		$works[$k]['artificerId'] = $v['artificerId']; // 专家id
    		$works[$k]['addTime'] = date('Y-m-d H:i:s',$v['addTime']); // 添加时间
    		$works[$k]['imageIds'] = $v['imageIds']; // 专家id
    		$works[$k]['description'] = $v['description']; // 作品集描述
    		
//     		$tempImgIds = explode(',', $v['imageIds'] );
    		$tempImgIds = $v['imageIds'] ;
    		$imgUrls = FileImage::select(['url as originImg','id as worksId'])->whereRaw('id in ('.$tempImgIds.') and status=1 order by field(id,'.$tempImgIds.')')->get()->toArray();
    		
    		$works[$k]['img'] = $imgUrls;
    	}
    	return $works;
    }
    private function _formatGetArtificerItemOver( $result ){

    	$temp = [];
    	$field = ['order_sn as orderSn','quantity','deanId as id'];
    	$tempOrder = [];
    	$queryOrder = [];

    	foreach( $result['data'] as $k => $v ){
    		$temp[] = $v['id'];
    	}
    	$allArtificer = BeautyOrderItem::select($field)->whereIn('deanId',$temp)->get()->toArray();
    	
    	if(empty($allArtificer)) {
    		foreach($result['data'] as $k => $v){
    			$result['data'][$k]['itemOver'] = 0;
    			$result['data'][$k]['itemOverApp'] = 0;
    			$result['data'][$k]['createTime'] = date('Y-m-d',$v['createTime']);
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
    			$result['data'][$k]['itemOverApp'] = self::OVER_BASE_NUM +  ( $overItem[ $v['id'] ]* self::OVER_FACTOR );
    		}else{
    			$result['data'][$k]['itemOver'] = 0;
    			$result['data'][$k]['itemOverApp'] = 0;
    		}
    		$result['data'][$k]['createTime'] = date('Y-m-d',$v['createTime']);
    	}
    	return $result;
    }
    private function _formatShow( $info ){
    	$field = ['order_sn as orderSn','quantity','deanId as id','item_id as itemId'];
    	$tempOrder = [];
    	$queryOrder = [];
    	$allArtificer = BeautyOrderItem::select($field)->where(['deanId'=>$info['id']])->get()->toArray();
    	$beautyItem = $this->_getBeautyItem();
    	if( empty($allArtificer) ){
    		$info['itemOver'] = 0;
    		$info['itemOverApp'] = 0;

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
    		$info['itemOverApp'] = 0;
    		foreach( $beautyItem as $k => $v ){
    			foreach( $v as $k1 => &$v1 ){
    				unset( $beautyItem[$k][$k1]['itemId'] );
    			}
    		}
    		$info['beautyItem'] = $beautyItem;
    		return $info;
    	}
    	
    	$info['itemOver'] = $overItem[ $info['id'] ];
    	$info['itemOverApp'] = self::OVER_BASE_NUM + ( $overItem[ $info['id'] ] * self::OVER_FACTOR );
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