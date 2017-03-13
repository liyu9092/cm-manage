<?php 
namespace App\Http\Controllers\Item;
use App\Http\Controllers\Controller;
use App\User;
use App\SalonItemComment;
use App\OrderTicket;
use App\Salon;
use App\FileImage;
use App\Hairstylist;
use Event;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use DB;

class CommentController extends Controller {
    /**
	 * @api {post} /comment/index 1.用户评价列表
	 * @apiName index
	 * @apiGroup Comment
	 *
	 * @apiParam {String} itemMainNum       可选,主体搜索条件类型。  1.臭美券密码 2.用户手机号 3.店铺名称
	 * @apiParam {String} keyword           可选,对应主体搜索关键词.
	 * @apiParam {String} date              可选,查找的评价日期。 默认为当天 格式为（2015-11-20）
	 * @apiParam {Number} satisfyType       可选,评价结果。 0. 全部 1.很满意 2.满意 3.不满意 默认为全部（0）
	 * @apiParam {String} status            可选,状态。 0. 全部 1.正常 2.已隐藏 3.已删除
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
	 * @apiSuccess {String} mobilePhone     用户手机号
	 * @apiSuccess {String} ticketNo        臭美券密码
	 * @apiSuccess {String} salonName       店铺名称.
	 * @apiSuccess {String} time            评价时间
	 * @apiSuccess {String} satisfyType     评价结果.
	 * @apiSuccess {String} statusName      状态名
	 * @apiSuccess {Number} status          状态标识. 1.正常 2.已隐藏 3.已删除
	 * @apiSuccess {Number} id              评论id
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
	 *                  "time": "2015-01-02 10:29:15",
     *                  "satisfyType": "很满意",
     *                  "status": 1,
     *                  "id": 10,
     *                  "statusName": "正常",
     *                  "salonName": "名作造型",
     *                  "ticketNo": "10062381",
     *                  "mobilePhone": "15079190498"
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
        $commentDate = isset( $param['date'] ) ? $param['date'] : date('Y-m-d');
        $satisfyType = isset( $param['satisfyType'] ) ? $param['satisfyType'] : 0;
        $status = isset( $param['status'] ) ? $param['status'] : 0;
        $page = isset($param['page']) ? max($param['page'],1) : 1;
        $pageSize = isset($param['page_size'])?$param['page_size']:20;
        
        $commentDateStart = strtotime( $commentDate . " 00:00:00" );
        $commentDateEnd = strtotime( $commentDate . " 23:59:59" );
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
        $obj = SalonItemComment::select(['add_time as time','satisfyType','status','order_ticket_id','salonid','itemcommentid as id','user_id']);
        $obj->whereBetween('add_time',[$commentDateStart,$commentDateEnd]);
        if( $satisfyType !== 0 && in_array( $satisfyType , [1,2,3])) $obj->where(['satisfyType'=>$satisfyType]);
        if( $status !== 0 && in_array( $status , [1,2,3] ) ) $obj->where(['status'=>$status]);
        if( $pageSize>500 ) $pageSize = 500;
        if( $keyword !== '' && $itemMainNum !== 0 && in_array($itemMainNum,[1,2,3]) ){
            switch ( $itemMainNum ){
                case 1:
                    $orderTicketId = $this->_getByTicketNo( $keyword );
                    if( !$orderTicketId ) return $this->success();
                    $obj->where(['order_ticket_id'=>$orderTicketId]);
                    break;
                case 2:
                    $userId = $this->_getByMobilePhone( $keyword );
                    if( !$userId ) return $this->success();
                    $obj->where(['user_id'=>$userId]);
                    break;
                case 3:
                    $salonId = $this->_getBySalonName( $keyword );
                    if( !$salonId ) return $this->success();
                    $obj->where(['salonid'=>$salonId]);
                    break;
                default :
                    break;
            }
        }
        $result = $obj->orderBy('id','desc')->paginate($pageSize)->toArray();
        $result = $this->_formatListData( $result );
        return $this->success( $result );
    }
    /**
	 * @api {get} /comment/show/:id 2.评价详情
	 * @apiName show
	 * @apiGroup Comment
	 *
	 * @apiParam {Number} id 必填,评价ID.
	 *
	 *
	 * @apiSuccess {String} mobilePhone         用户手机号.
	 * @apiSuccess {String} ticketNo            臭美券密码.
	 * @apiSuccess {String} salonName           店铺名称
	 * @apiSuccess {String} time                评价时间
	 * @apiSuccess {String} satisfyType         评价结果
	 * @apiSuccess {String} hairstylist         评价对象
	 * @apiSuccess {String} content             评价详情内容
	 * @apiSuccess {Array} imgsrc               图片数组
	 * @apiSuccess {Number} id                  评论ID.
	 * @apiSuccess {Number} status              状态标识. 1.正常 2.已隐藏 3.已删除
	 * 
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
	 *	        "id": 11,
     *           "time": "2015-01-02 15:15:27",
     *           "satisfyType": "很满意",
     *           "content": "二号设计师kevin很好，听他说上过沙宣学院，技术不错哦！",
     *           "imgsrc": [
     *                  "http://newapi.choumei.cn/Uploads/choumeicomment/2015-01-01/54a546bb65006.jpg",
     *                  "http://newapi.choumei.cn/Uploads/choumeicomment/2015-01-01/54a546bb65d9b.jpg",
     *           ],
     *           "status": 1,
     *           "salonName": "星偶像",
     *           "ticketNo": "10028345",
     *           "mobilePhone": "15079190498",
     *           "hairstylist": "康总剪12 001 高级形象设计师"
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
    public function show( $id ){
        $info = SalonItemComment::select(['itemcommentid as id','order_ticket_id','salonid','add_time as time','satisfyType','hairstylistid','content','imgsrc','user_id','status','image_ids'])
                ->where(['itemcommentid'=>$id])
                ->first();
        if(empty($info)) return $this->error('数据错误');
        $info = $info->toArray();
        $info = $this->_formatInfo( $info );
        return $this->success( $info );
    }
    /**
	 * @api {get} /comment/hidden/:id 3.隐藏评价
	 * @apiName hidden
	 * @apiGroup Comment
	 *
	 * @apiParam {Number} id 必填,评价ID.
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
    public function hidden( $id ){
        SalonItemComment::where(['itemcommentid'=>$id])->where('status','<>',3)->update(['status'=>2]);
        Event::fire('comment.hidden','隐藏用户评论：'.$id);
        return $this->success();
    }
    /**
	 * @api {get} /comment/delete/:id 4.删除评价
	 * @apiName delete
	 * @apiGroup Comment
	 *
	 * @apiParam {Number} id 必填,评价ID.
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
    public function delete( $id ){
        SalonItemComment::where(['itemcommentid'=>$id])->where('status','<>',2)->update(['status'=>3]);
        Event::fire('comment.delete','删除用户评论：'.$id);
        return $this->success();
    }
    // 获取臭美劵密码
    private function _getByOrderTicketId( $orderTicketId=0 ){
        $ticketno = OrderTicket::select(['ticketno'])->where(['order_ticket_id'=>$orderTicketId])->where('status','<>',2)->first();
        if( empty($ticketno) ) return false;
        $ticketno = $ticketno['ticketno'];
        return $ticketno;
    }
    // 臭美卷号获取 Order_ticket_id
    private function _getByTicketNo( $ticketNo='' ){
        $orderTicketId = OrderTicket::select(['order_ticket_id'])->where(['ticketNo'=>$ticketNo])->where('status','<>',2)->first();
        if( empty($orderTicketId) ) return false;
        $orderTicketId = $orderTicketId['order_ticket_id'];
        return $orderTicketId;
    }
    // 通过手机号码获取用户id
    private function _getByMobilePhone( $mobilePhone = '' ){
        $userId = User::select(['user_id'])->where(['mobilephone'=>$mobilePhone])->first();
        if( empty($userId) ) return false;
        $userId = $userId['user_id'];
        return $userId;
    }
    // 通过用户id获取手机号码
    private function _getByUserId( $userId = 0){
        $mobilePhone = User::select(['mobilephone'])->where(['user_id'=>$userId])->first();
        if( empty($mobilePhone) ) return false;
        $mobilePhone = $mobilePhone['mobilephone'];
        return $mobilePhone;
    }
    // 通过店铺名字获取店铺id
    private function _getBySalonName( $salonName = '' ){
        $salonId = Salon::select(['salonid'])->where(['salonname'=>$salonName,'status'=>1])->first();
        if( empty($salonId) ) return false;
        $salonId = $salonId['salonid'];
        return $salonId;
    }
    // 通过店铺id获取店铺名字
    private function _getBySalonId( $salonId = 0 ){
        $salonName = Salon::select(['salonname'])->where(['salonid'=>$salonId,'status'=>1])->first();
        if( empty($salonName) ) return false;
        $salonName = $salonName['salonname'];
        return $salonName;
    }
    // 格式化返回列表
    private function _formatListData( $data ){
        if( empty($data) || empty($data['data']) ) return $data;
        $satisfyType = ['','很满意','满意','不满意'];
        $status = ['','正常','隐藏','删除'];
        foreach( $data['data'] as $key => $val ){
            $salonName = $this->_getBySalonId( $val['salonid'] );
            $ticketNo = $this->_getByOrderTicketId( $val['order_ticket_id'] );
            $mobilePhone = $this->_getByUserId( $val['user_id'] );
            $satisfyName = $satisfyType[ $val['satisfyType'] ];
            $statusName = $status[ $val['status'] ];
            $time = date('Y-m-d H:i:s',$val['time']);
            
            $data['data'][$key]['time'] = $time;
            $data['data'][$key]['satisfyType'] = $satisfyName;
            $data['data'][$key]['statusName'] = $statusName;
            $data['data'][$key]['salonName'] = $salonName;
            $data['data'][$key]['ticketNo'] = $ticketNo;
            $data['data'][$key]['mobilePhone'] = $mobilePhone;
            
            unset( $data['data'][$key]['salonid'] );
            unset( $data['data'][$key]['order_ticket_id'] );
            unset( $data['data'][$key]['user_id'] );
        }
        return $data;
    }
    // 格式化返回详情
    private function _formatInfo( $data ){
        if( empty($data) ) return $data;
        $satisfyType = ['','很满意','满意','不满意'];
//        $status = ['','正常','隐藏','删除'];
        
        $salonName = $this->_getBySalonId( $data['salonid'] );
        $ticketNo = $this->_getByOrderTicketId( $data['order_ticket_id'] );
        $mobilePhone = $this->_getByUserId( $data['user_id'] );
        $satisfyName = $satisfyType[ $data['satisfyType'] ];
        $images = [];
//        $statusName = $status[ $data['status'] ];
        $time = date('Y-m-d H:i:s',$data['time']);
        $hairstylistStr = '';
        if( !empty($data['hairstylistid']) ){
            $stylistInfo = Hairstylist::select(['stylistName','sNumber','job'])->where(['stylistId'=>$data['hairstylistid'],'status'=>1])->first();
            if(!empty($stylistInfo)){
                $stylistInfo = $stylistInfo->toArray();
                $hairstylistStr = $stylistInfo['stylistName'] . " ". $stylistInfo['sNumber'] . " " .$stylistInfo['job'];
            }
        }
        if( !empty( $data['image_ids'] ) ){
            $ids = explode(',', $data['image_ids']);
            $temp = FileImage::select(['url'])->whereIn('id',$ids)->where(['status'=>1])->get()->toArray();
            foreach($temp as $val){
                $images[] = $val['url'];
            }
        }
        $data['time'] = $time;
        $data['satisfyType'] = $satisfyName;
//        $data['statusName'] = $statusName;
        $data['salonName'] = $salonName;
        $data['ticketNo'] = $ticketNo;
        $data['mobilePhone'] = $mobilePhone;
        $data['hairstylist'] = $hairstylistStr;
        $data['imgsrc'] = $images;

        unset( $data['salonid'] );
        unset( $data['order_ticket_id'] );
        unset( $data['user_id'] );
        unset( $data['hairstylistid'] );
        unset( $data['image_ids'] );
        return $data;
    }
}