<?php

namespace Fisher\SSO\Services;

use Cache;
use Illuminate\Http\Request;
use Fisher\SSO\Traits\UserHelper;
use Fisher\SSO\Traits\ResourceLibrary;

class RequestSSOService
{
    use UserHelper;
    use ResourceLibrary;

    public function __construct(Request $request)
    {
        $this->setHeader([
            'Accept' => 'application/json',
            'Authorization' => $request->header('Authorization')
        ]);
    }

    /**
     * oauth 客户端授权.
     *
     * @author 28youth
     * @return RequestSSOService
     */
    public function client()
    {
        $cacheKey = 'oauth_token';
        if (Cache::has($cacheKey)) {
            $response = Cache::get($cacheKey);
        } else {
            $response = $this->request('post', '/oauth/token', [
                'headers' => ['Accept' => 'application/json'],
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => config('sso.client_id'),
                    'client_secret' => config('sso.client_secret'),
                ]
            ]);
            Cache::put($cacheKey, $response, floor($response['expires_in'] / 60));
        }
        $this->setHeader([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $response['access_token']
        ]);

        return $this;
    }

    protected function getBaseUri(): string
    {
        return config('sso.host');
    }

    protected function getPointUri(): string
    {
        $ip = config('sso.point_host');
        return $ip == true ? $ip : abort(500,'服务器未配置积分制信息');
    }

    protected function getDingUri(): string
    {
        return config('sso.ding_host');
    }

    public function get($endpoint, $query = [], $header = [])
    {
        return $this->request('get', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'query' => $query,
        ]);
    }

    public function post($endpoint, $params = [], $header = [], $point = '')
    {
        return $this->request('post', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ], $point);
    }

    public function postDing($endpoint, $params = [], $header = [], $point = '')
    {
        return $this->request('post', $this->getDingUri() . $endpoint, [
            'Content-Type ' => 'application/json',
            'json' => [
                'chatid' => $params['chatid'],
                'msg' => [
                    'msgtype' => 'image',
                    'image' => [
                        'media_id' => $params['data']
                    ]
                ],
            ],
        ], $point);
    }

    public function postDingImage($endpoint, $url, $header = [], $point = '')
    {
        return $this->request('post', $this->getDingUri() . $endpoint, [//multipart
            'type' => 'image',
            'multipart' => [
                [
                    'name' => 'type',
                    'contents' => 'image',
                ],
                [
                    'name' => 'media',
                    'contents' => fopen($url, 'r'),
                ],
            ]
        ], $point);
    }

    public function postDingSentinel($endpoint, $arr, $header = [], $point = '')
    {
        return $this->request('post', $this->getDingUri() . $endpoint, [
            'sender' => '0156340823848080042',
            'cid' => '',
            'msg' => [
                'msgtype' => 'image',
                'image' => [
                    'media_id' => $arr['data']
                ]
            ],
        ], $point);
    }

    public function put($endpoint, $params = [], $header = [])
    {
        return $this->request('put', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ]);
    }

    public function patch($endpoint, $params = [], $header = [])
    {
        return $this->request('patch', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ]);
    }

    public function delete($endpoint, $params = [], $header = [], $point = '')
    {
        return $this->request('delete', $endpoint, [
            'headers' => array_merge($header, $this->headers),
            'json' => $params,
        ], $point);
    }
}