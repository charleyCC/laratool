<?php
/**
 * User: CharleyChan
 * Date: 2022/4/23
 * Time: 10:39 下午
 **/

namespace Charley\Laratool\Service;


use Charley\Laratool\Exception\BusinessException;

class BaseService
{
    protected static $instance = [];

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    public static function getInstance(bool $shared = true)
    {
        if ($shared && ((static::$instance[static::class] ?? null) instanceof static)) {
            return static::$instance[static::class];
        }

        unset(static::$instance[static::class]);

        return static::$instance[static::class] = new static();
    }

    public function throwBusinessException(array $codeResponse = [], string $message = '')
    {
        throw new BusinessException($codeResponse, $message);
    }
}
