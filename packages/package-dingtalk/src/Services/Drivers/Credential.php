<?php

namespace Fisher\Schedule\Services\Drivers;

use Cache;
use Fisher\Schedule\Services\DingtalkDriverHelper;

class Credential
{
    use DingtalkDriverHelper;

    /**
     * Get credential token.
     *
     * @author 28youth
     * @return string
     */
    public function token(): string
    {
        // if (Cache::has($this->cacheKey())) {
        //     return Cache::get($this->cacheKey());
        // }
        $result = app('api')->client()->getAccessToken();

        // $this->setToken($token = $result['message'], 7000);

        return $result['message'];
    }

    /**
     * Set cache.
     * 
     * @author 28youth
     * @param string  $token
     * @param int|\DateInterval|null $ttl
     *
     * @return $this
     */
    public function setToken(string $token, $ttl = null)
    {
        Cache::put($this->cacheKey(), $token, $ttl);
    }

    /**
     * Get credentials.
     * 
     * @author 28youth
     * @return array
     */
    protected function credentials(): array
    {
        return [
            'corpid' => config('dingtalk.corp_id'),
            'corpsecret' => config('dingtalk.corp_secret')
        ];
    }

    /**
     * Set cache key.
     * 
     * @author 28youth
     * @return string
     */
    protected function cacheKey(): string
    {
        return 'access_token.'.md5(json_encode($this->credentials()));
    }

    /**
     * call static token.
     * 
     * @author 28youth
     * @return string
     */
    public static function __callStatic($funName, $arguments)
    {
        return (new self())->token();
    }
}
