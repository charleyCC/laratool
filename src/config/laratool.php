<?php
/**
 * User: CharleyChan
 * Date: 2023/6/2
 * Time: 4:34 下午
 **/
return [

    /**
     * 幂等性操作
     */
    'idempotency' => [
        //驱动缓存
        'redis' => [
            'database' => 1,
            'expires' => 3600,
        ],
    ],

];
