<?php
/**
 * User: CharleyChan
 * Date: 2022/4/23
 * Time: 10:41 下午
 **/

namespace Charleychan\Laratool\Exception;


class BusinessException extends \Exception
{
    public function __construct(array $codeResponse = [], string $message = '')
    {
        list($code, $msg) = $codeResponse;
        parent::__construct($message ?: $msg, $code);
    }
}
