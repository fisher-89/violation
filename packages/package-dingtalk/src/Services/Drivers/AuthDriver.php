<?php

namespace Fisher\Schedule\Services\Drivers;


class AuthDriver extends DriverAbstract
{
	/**
     * Get the provider.
     *
     * @author 28youth
     * @return string
     */
    public function provider(): string
    {
        return 'auth';
    }

    /**
     * 获取通讯录权限范围.
     *
     * @author 28youth
     * @docs https://open-doc.dingtalk.com/microapp/serverapi2/vt6v7m
     * @return array
     */
    public function scopes()
    {
        return $this->httpGet('auth/scopes');
    }
}
