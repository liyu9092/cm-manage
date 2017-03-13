<?php  namespace App\Http\Controllers\LoginQuery;

use App\Http\Controllers\Controller;
use App\RequestLog;
use Log;
use Excel;
use Event;
/**
 * Description of LoginQueryController
 *
 * @author zhengjiangang
 */

class LoginQueryController  extends Controller {
    /**
	 * @api {post} /requestLog/index 1.列出登录日志列表
	 * 
	 * @apiName index
	 * @apiGroup LoginQuery
	 *
	 * @apiParam {String} mobilephone 可选,用户手机号.
	 * @apiParam {String} username 可选,用户臭美号.
	 * @apiParam {String} device_uuid 可选,用户设备号.
	 * @apiParam {String} openid 可选,微信OpenId.
	 * @apiParam {String} version 可选,APP版本.
	 * @apiParam {String} minTime 可选,最小时间.
    	 * @apiParam {String} maxTime 可选,最大时间.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * 
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {String} mobilephone 可选,用户手机号.
	 * @apiSuccess {String} username 可选,用户臭美号.
	 * @apiSuccess {String} device_uuid 可选,用户设备号.
	 * @apiSuccess {String} update_time 登录时间.
	 * @apiSuccess {String} device_os 手机系统.
	 * @apiSuccess {String} openid 微信OpenId.
	 * @apiSuccess {String} version APP版本.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * {
     *      "result":1,
     *      "token":"",
     *      "data":
     *          {
     *              "total":1,
     *              "per_page":20,
     *              "current_page":1,
     *              "last_page":1,
     *              "from":1,"to":1,
     *              "data":[
     *                      {
     *                           "mobilephone":"15102011866",
     *                           "username":"10000000",
     *                           "bundle":"100000",
     *                           "updateTime":"0000-00-00 00:00:00",
     *                           "deviceOs":"ios,
     *                           "version":"5.4.2"
     *                      }
     *                   ]
     *           }
     * }
     *
     *  @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
    */
    public function index()
    {
           $param = $this->param; 
           Log::info('LoginQueryController index param is: ', $param);
           $query=RequestLog::getLogSelect($param);
           return $this->success($query);
     }
     
    /**
	 * @api {post} /requestLog/export 2.导出日志列表
	 * 
	 * @apiName export
	 * @apiGroup LoginQuery
	 *
	 * @apiParam {String} mobilephone 可选,用户手机号.
	 * @apiParam {String} username 可选,用户臭美号.
	 * @apiParam {String} device_uuid 可选,用户设备号.
	 * @apiParam {String} minTime 可选,最小时间.
	 * @apiParam {String} maxTime 可选,最大时间.
	 * 
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {String} mobilephone 可选,用户手机号.
	 * @apiSuccess {String} username 可选,用户臭美号.
	 * @apiSuccess {String} device_uuid 可选,用户设备号.
	 * @apiSuccess {String} update_time 登录时间.
	 * @apiSuccess {String} device_os 手机系统.
	 * @apiSuccess {String} version APP版本.
     * 
     * 
     * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
     */
     
    public function export()
    {
           $param = $this->param; 
           Log::info('LoginQueryController index param is: ', $param);
           $query=RequestLog::getLogSelect($param);
           $header = ['用户手机号','用户臭美号','用户设备号','登录时间','手机系统','APP版本'];         
           Event::fire('LoginQuery.export');
           $this->export_xls("设备登录列表".date("Ymd"),$header,self::format_prepay_data($query["data"]));
           
     }
     
    protected static function format_prepay_data($datas)
    {
        $res = [];
        foreach ($datas as $data) {
            $mobilephone = isset($data->mobilephone) ? $data->mobilephone : '';
            $username = isset($data->username) ? $data->username : '';
            $update_time = $data->update_time;
            $device_uuid = isset($data->device_uuid)?$data->device_uuid:'';
            $device_os = isset($data->device_os)?$data->device_os:'';
            $version = isset($data->version)?$data->version:'';
            
            $res[] = [
                $mobilephone,
                $username,
                $device_uuid,
                $update_time,
                $device_os,
                $version
            ];
            
        }
        return $res;
    }
    
}
