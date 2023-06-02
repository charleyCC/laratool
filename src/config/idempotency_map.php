<?php
/**
 * User: CharleyChan
 * Date: 2023/6/2
 * Time: 4:34 下午
 **/

/**
 * 幂等性接口或操作key对应参数配置
 */
return [
    /**
     * module{模块}
     */
    'auth' => [

        /**
         * 对象{object}
         */
        'user' => [

            /**
             * 操作{action} => 对应请求参数字段
             */

            //入库
            'register' => ['id',],

            //质检
            'login' => ['id'],

        ],

    ],
];
