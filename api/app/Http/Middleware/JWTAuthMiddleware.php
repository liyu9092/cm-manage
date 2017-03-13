<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Tymon\JWTAuth\Middleware\BaseMiddleware;
use Route;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class JWTAuthMiddleware extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (! $token = $this->auth->setRequest($request)->getToken()) {
            Throw new ApiException('token无效',ERROR::TOKEN_INVILD);
        }

        try {
            $user = $this->auth->authenticate($token);
        } catch (TokenExpiredException $e) {
            Throw new ApiException('token过期',ERROR::TOKEN_EXPIRED);
        } catch (\Exception $e) {
            Throw new ApiException('token无效',ERROR::TOKEN_INVILD);
        }

        $this->events->fire('tymon.jwt.valid', $user);

        return $next($request);
    }
}
