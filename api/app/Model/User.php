<?php
/**
 * 用户表
 * @author Vincent
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Redis as Redis;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use Event;

const FIRST_KEY = 'recent.first.user';
const REGISTER_KEY = 'recent.register.user';

class User extends  Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $fillable = ['username','nickname','password','email','img','add_time','last_time','sex','status','hair_type','birthday','area','growth','grade','mobilephone','costpwd','companyId'];
    public $timestamps = false;

    public function recommendCodes(){
        return $this->hasMany('App\RecommendCodeUser');
    }

    public static function getQueryByParam($param=[]){
        $query = Self::leftJoin('company_code','user.companyId','=','company_code.companyId')
            ->leftJoin('recommend_code_user',function($join){
                $join->on('user.user_id','=','recommend_code_user.user_id')
                    ->where('recommend_code_user.type','=',1);
            })
            ->leftJoin('dividend','dividend.recommend_code','=','recommend_code_user.recommend_code');

        if(!empty($param['username'])){
            $username = '%'.$param['username'].'%';
            $query = $query->where('username','like',$username);
        }

        if(!empty($param['mobilephone'])){
            $query = $query->where('mobilephone','=',$param['mobilephone']);
        }

        if(!empty($param['companyCode'])){
            $query = $query->where('company_code.code','=',$param['companyCode']);
        }

        $query = $query->with(['recommendCodes'=>function($q){
            $q->leftJoin('dividend','dividend.recommend_code','=','recommend_code_user.recommend_code')
                ->select('recommend_code_user.user_id','recommend_code_user.recommend_code','recommend_code_user.type','dividend.activity');
        }]);

        if(!empty($param['recommendCode'])){
            $query = $query->whereHas('recommendCodes',function($q) use($param){
                $q->where('recommend_code','=',$param['recommendCode']);
            });
        }

        if(isset($param['sex'])){
            $query = $query->where('sex','=',$param['sex']);
        }

        if(!empty($param['start_at'])){
            $start_at = strtotime($param['start_at']);
            $query = $query->where('user.add_time','>=',$start_at);
        }

        if(!empty($param['end_at'])){
            $end_at = strtotime($param['end_at'])+3600*24;
            $query = $query->where('user.add_time','<=',$end_at);
        }

        if(!empty($param['area'])){
            $area = '%'.$param['area'].'%';
            $query = $query->where('area','like',$area);
        }

        //排序
        $sort_key = empty($param['sort_key'])?'user.user_id':$param['sort_key'];
        $sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);

        return $query;
    }

    public static function getLevel($growth=0){
        $level = DB::table('user_level')
            ->where('growth','<=',$growth)
            ->orderBy('level','DESC')
            ->pluck('level');
        return $level;
    }

    public static function getSex($sex=0){
        $sex = intval($sex);
        $mapping = [
            0=>'未知',
            1=>'女',
            2=>'男'
        ];
        if(empty($mapping[$sex]))
            return '未知';
        return $mapping[$sex];
    }

    public static function getUsersByIds($uids) {
        $users = self::whereIn("user_id", $uids)->get();
        return $users;
    }

    public static function getUserById($uid) {
        $user = Self::getQuery()->where("user_id", "=", $uid)->first();
        return $user;
    }

    //获取最近15天每天的用户注册数
    public static function getRecentRegister(){
        $redis = Redis::connection();
        $result = [];
        for ($i=14; $i >= 0; $i--){
            //当天的数据实时统计
            if($i==0){
                $day = date('Y-m-d',strtotime('today'));
                $count = Self::getRegisterByDay($day);
                $result[$day] = $count;
            }
            //昨天或以前的数据从redis取
            else{
                $day = date('Y-m-d',strtotime("- $i day"));
                $count = $redis->hGet(REGISTER_KEY,$day);
                if($count == NULL){
                    $count = Self::getRegisterByDay($day);
                    $redis->hSet(REGISTER_KEY,$day,$count);
                }
                $result[$day] = intval($count);
            }
        }
        //删除16天前的记录,控制hash不超过16个元素
        $last = date('Y-m-d',strtotime('-16 day'));
        $redis->hDel(REGISTER_KEY,$last);
        return $result;
    }

    //获取最近15天每天的首单用户数
    public static function getRecentFirst(){
        $redis = Redis::connection();
        $result = [];
        for ($i=14; $i >= 0; $i--){
            //当天的数据实时统计
            if($i==0){
                $day = date('Y-m-d',strtotime('today'));
                $count = Self::getFirstByDay($day);
                $result[$day] = $count;
            }
            //昨天或以前的数据从redis取
            else{
                $day = date('Y-m-d',strtotime("- $i day"));
                $count = $redis->hGet(FIRST_KEY,$day);
                if($count == NULL){
                    $count = Self::getFirstByDay($day);
                    $redis->hSet(FIRST_KEY,$day,$count);
                }
                $result[$day] = intval($count);
            }
        }
        //删除16天前的记录,控制hash不超过15个元素
        $last = date('Y-m-d',strtotime('-16 day'));
        $redis->hDel(FIRST_KEY,$last);
        return $result;
    }

    public static function getFirstByDay($day){
            $current = strtotime($day);
            $next = $current+3600*24;
            $users = Order::whereBetween('use_time',[$current,$next])->lists('user_id');
            $orders = Order::whereIn('user_id',$users)->where('use_time','!=',0)->orderBy('use_time','desc')->groupBy('user_id')->lists('orderid');
            $count = count($orders);
            return $count;
    }

    public static function getRegisterByDay($day){
        $current = strtotime($day);
        $next = $current+3600*24;
        $count = User::withTrashed()->whereBetween('add_time',[$current,$next])->count();
        return $count;
    }

    // 验证手机号码是否存在
    public static function verifyUserPhoneExists( $phone ){
        $exists = Self::select(['user_id','os_type'])->where(['mobilephone'=>$phone])->first();
        return $exists;
    }

    public static function resetCode($id,$type,$activity){
        if($type<5)
        {
            if($type=='1')
            {
                $result = DB::table('recommend_code_user')
                    ->leftJoin('dividend','dividend.recommend_code','=','recommend_code_user.recommend_code')
                    ->where('type','=',$type)
                    ->where('user_id','=',$id)
                    ->where('activity','=',$activity)
                    ->delete();
            }
            else
            {
                $result = DB::table('recommend_code_user')
                    ->where('user_id','=',$id)
                    ->where('type','=',$type)
                    ->delete();
            }  
        }

        if($type==5)
        {
            $result = self::resetCompanyCode($id);
        }
        return $result;

    }


    public static function setCode($id,$type,$code,$activity){
        $salonid = 0;
        if($type<'5')
        {
            $model =  DB::table('recommend_code_user');
            if($type=='1')
            {
                $exists = DB::table('dividend')
                    ->where('recommend_code','=',$code)
                    ->where('activity','=',$activity)
                    ->first();
                if(!$exists)
                    throw new ApiException('邀请码不存在', ERROR::CODE_NOT_FOUND);
                DB::table('recommend_code_user')
                    ->leftJoin('dividend','dividend.recommend_code','=','recommend_code_user.recommend_code')
                    ->where('type','=',$type)
                    ->where('user_id','=',$id)
                    ->where('activity','=',$activity)
                    ->delete();
                if($activity==2)
                    $salonid = DB::table('dividend')->where('recommend_code','=',$code)->first()->salon_id;
            }
            else
            {
                if($type=='2')
                {
                    $exists = DB::table('dividend')
                        ->where('recommend_code','=',$code)
                        ->where('activity','=',$activity)
                        ->first();
                    if(!$exists)
                        throw new ApiException('该推荐邀请码无效', ERROR::CODE_NOT_FOUND);
                }

                if($type=='3')
                {
                    $user= User::where('mobilephone','=',$code)->first();
                    if(!$user)
                        throw new ApiException('该推荐邀请码无效', ERROR::USER_NOT_FOUND);
                    $exists = DB::table('booking_order')
                        ->where('USER_ID','=',$user->user_id)
                        ->whereIn('STATUS',['PYD','CSD','RFN'])
                        ->first();
                    if(!$exists)
                        throw new ApiException('该推荐邀请码无效', ERROR::CODE_NOT_FOUND);
                }

                if($type=='4')
                {
                    $exists = DB::table('voucher_conf')
                        ->where('vcSn','=',$code)
                        ->where('status','=',1)
                        ->first();
                    if(!$exists)
                        throw new ApiException('该推荐邀请码无效', ERROR::CODE_NOT_FOUND);
                }
                $model->where('user_id','=',$id)
                    ->where('type','=',$type)
                    ->delete();
            }

            $result = $model->insert(['user_id'=>$id,'salon_id'=>$salonid,'type'=>$type,'recommend_code'=>$code,'add_time'=>time()]);
        }

        if($type=='5')
        {
            $result = self::setCompanyCode($id,$code);
        }
        return $result;

    }

    public static function resetCompanyCode($id){
        $user = User::find($id);
        if(!$user)
            throw new ApiException('用户不存在', ERROR::USER_NOT_FOUND);
        DB::beginTransaction();
        $delete = DB::table('company_code_user')->where('user_id','=',$id)->delete();
        $update = $user->update(['companyId'=>0]);
        if($update){
            DB::commit();
            //触发事件，写入日志
            Event::fire('user.resetCode',array($user));
            return true;
        }
        else
        {
            DB::rollBack();
            throw new ApiException('集团码解除失败', ERROR::USER_UPDATE_FAILED);
        }
    }

    public static function setCompanyCode($id,$code){
        $user = User::find($id);
        if(!$user)
            throw new ApiException('用户不存在', ERROR::USER_NOT_FOUND);

        $company = DB::table('company_code')->where('code','=',$code)->first();
        if(!$company)
            throw new ApiException('集团码不存在', ERROR::CODE_NOT_FOUND);

        DB::beginTransaction();
        $update = $user->update(['companyId'=>$company->companyId]);
        $model = DB::table('company_code_user');
        //解绑原集团码
        $model->where('user_id','=',$id)->delete();
        $insert = $model->insert([
                'user_id'=>$id,
                'companyId'=>$company->companyId,
                'companyCode'=>$code,
                'addTime'=>time()
            ]);
        if($update&&$insert){
            DB::commit();
            //触发事件，写入日志
            // Event::fire('user.setCode',array($user));
            return true;
        }
        else
        {
            DB::rollBack();
            throw new ApiException('集团码绑定失败', ERROR::USER_UPDATE_FAILED);
        }
    }


    //判断用户能否解绑或绑定邀请码
    public static function canResetCode($id,$type)
    {
        if(in_array($type, ['2','3','4']))
        {
            $books = DB::table('booking_order')
                ->where('user_id','=',$id)
                ->whereIn('status',['PYD','RFN'])
                ->first();
            return !$books;
        }

        if(in_array($type, ['1','5']))
        {
            $tickets = DB::table('order_ticket')
                ->whereIn('status',[2,6])
                ->where('user_id','=',$id)
                ->first();
            return !$tickets;
        }
        return false;
    }

}