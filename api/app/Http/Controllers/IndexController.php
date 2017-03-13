<?php namespace App\Http\Controllers;

use App\Manager;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Captcha;
use Validator;
use Input;
use Auth;
use Response;
use Event;
use Illuminate\Support\Facades\Redis as Redis;
use App\Permission;
use DB;
use App\Role;
use Excel;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\Jobs\SendEmail;

class IndexController extends Controller{


	public function test(){
		$this->dispatch(new SendEmail());
		return $this->success();
	}


	/**
	 * @api {get} /captcha 1.获取验证码
	 * @apiDescription 此接口用于生成登录验证码,直接将img的src属于指向这里即可。
	 * @apiName captcha
	 * @apiGroup Login
	 *
	 * @apiParam {String} uniqid 必填,前端生成一个32位唯一标识,用于记录验证码.
	 * 
	 * @apiSuccess {Image} image 返回验证码图片.
	 *
	 * @apiSuccessExample Success-Response:
	 *     HTTP/1.1 200 OK
	 *     验证码
	 *
	 */
	public function captcha(){
		$captcha = new Captcha;
		$captcha = $captcha::create();
		return $captcha;
	}

	/**
	 * @api {post} /login 2.提交登录
	 * @apiName login
	 * @apiGroup Login
	 *
	 * @apiParam {String} username 必填,用户名.
	 * @apiParam {String} password 必填,密码.
	 * @apiParam {String} captcha 必填,验证码.
	 * @apiParam {String} uniqid 必填,唯一标识,用于验证验证码,与上面保持一致.
	 *
	 * @apiSuccess {String} token  加密过的token字符串.以后每次请求都需要将token加入到header中.格式如下：Authorization: Bearer {yourtokenhere},或者将token=xxx置于url中传递,否则将没有权限访问.错误码401表示token过期或者被加入黑名单(退出登录),400表示无效token.
	 * @apiSuccess {Int} uid 用户ID
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": {
	 *		        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXUyJ9.eyJzdWIiOjEsImlzcyI6Imh0dHA6XC9cL2xvY2FsaG9zdFwvbGFyYXZlbFwvcHVibGljXC9pbmRleC5waHBcL2xvZ2luIiwiaWF0IjoiMTQzMDkwNTQ4NyIsImV4cCI6IjE0MzA5MDkwODciLCJuYmYiOiIxNDMwOTA1NDg3IiwianRpIjoiYTQ4OWI3N2NmOWY4NmUxMWZjMWY1NTE3ZTQ4NjViZjYifQ.Njg2ZWQ3ZDNjZjFjMGY3ZGVmMDhmYjdkZjI0MDI2NTY4YjFjOTBmNzM4MzFhYzgzZjNkZTZmNTc3NGRhODI4Ng",
	 *		        "uid": 1
	 *		    }
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "验证码错误"
	 *		}
	 */
	public function login(){
		$captcha = new Captcha;
		$validator = $captcha::check(strtolower($this->param['captcha']));
        if (!$validator){
        	$captcha::create();//重新生成验证码
        	throw new ApiException('验证码错误', ERROR::CAPTCHA_ERROR);
        }
	       
		if (Auth::attempt(array('username' => $this->param['username'], 'password' => $this->param['password'])))
		{       
    		$user = Manager::where('username',$this->param['username'])->firstOrFail();
    		if($user->status==2)
    			throw new ApiException('当前帐户已停用', ERROR::ACCOUNT_INVALID);
    		if($user->status==3)
    			throw new ApiException('当前帐户已注销', ERROR::ACCOUNT_INVALID);
    		$this->user = $user;
    		$token = JWTAuth::fromUser($user);
    		Event::fire('login',$user);
    		return $this->success(['token'=>$token,'uid'=>$user->id,'name'=>$user->name,'username'=>$user->username]);
    	}
        else
        {
        	$captcha::create();//重新生成验证码
        	throw new ApiException('用户名或密码错误', ERROR::LOGIN_FAILED);
        }
	}


	/**
	 * @api {post} /logout 3.退出登录
	 * @apiName logout
	 * @apiGroup Login
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": null
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "token无效"
	 *		}
	 */
	public function logout()
	{
		$token = JWTAuth::getToken();
		$user = JWTAuth::parseToken()->authenticate();
		if(JWTAuth::invalidate($token))
		{
			Event::fire('logout',$user);
			$redis = Redis::connection();
			$redis->del('permissions:'.$token);
			return $this->success();
		}
		throw new ApiException('退出登录失败', ERROR::LOGOUT_FAILED);
	}


}
?>