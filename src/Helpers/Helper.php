<?php

namespace WorkerThink\Helpers;

use voku\helper\AntiXSS;

class Helper
{
    public static ?AntiXSS $xss = null;

    /**
     * 清理XSS
     * 内置的 http 服务可以使用 request()->get()->post() 获取变量，不自动使用此方法过滤
     * 内置的 WebSocket 服务，自动为输入变量使用此方法过滤
     */
    public static function cleanXss(mixed &$data): void
    {
        if (is_array($data)) {
            array_walk_recursive($data, ['WorkerThink\Helpers\Helper', 'cleanXss']);
        } else {
            self::$xss = is_null(self::$xss) ? new AntiXSS() : self::$xss;
            $data      = self::$xss->xss_clean($data);
        }
    }
}