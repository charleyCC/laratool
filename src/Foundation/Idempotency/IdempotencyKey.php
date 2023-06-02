<?php
/**
 * User: CharleyChan
 * Date: 2023/6/2
 * Time: 4:34 下午
 **/
declare(strict_types=1);

namespace Charleychan\Laratool\Foundation\Idempotency;


use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Charleychan\Laratool\Exception\BusinessException;
use Charleychan\Laratool\Http\HttpCodeResponse;

/**
 * 幂等性接口KEY工具
 *
 * Class IdempotencyKey
 * @package Morejoy\Larabase\Foundation\Idempotency
 */
class IdempotencyKey
{
    /**
     * @var string 幂等性缓存前缀键
     */
    const IDEMPOTENCY_PREFIX_KEY = 'idempotency:';

    /**
     * @var int 键值缓存有效期【单位：秒】
     */
    private $expires = 0;

    /**
     * @var string 模块
     */
    private $module = '';

    /**
     * @var string 对象
     */
    private $object = '';

    /**
     * @var string 操作方法
     */
    private $action = '';

    /**
     * @var array 【请求或方法】参数
     */
    private $params = [];

    /**
     * @var Connection Redis缓存连接【这里先耦合redis驱动缓存】
     */
    private $redisConnection;

    /**
     * @var callable 拦截回调处理
     */
    private $interceptCallback;

    /**
     * @var bool 是否触发了拦截
     */
    private $hasIntercept = false;

    /**
     * IdempotencyKey constructor.
     *
     * @param string $module 模块
     * @param string $object 对象
     * @param string $action 方法
     */
    public function __construct(string $module, string $object, string $action)
    {
        $this->module = $module;
        $this->object = $object;
        $this->action = $action;

        $this->redisConnection = Redis::connection(config('laratool.idempotency.redis.database'));
    }

    /**
     * 设置参数缓存
     *
     * @param array $params
     * @return $this
     */
    public function setCacheParams(array $params, array $fields): self
    {
        if (empty($fields)) {
            $this->params = $params;
            return $this;
        }

        //过滤需要缓存哪些参数
        foreach ($params as $fieldKey => $fieldValue) {
            if (!in_array($fieldKey, $fields)) {
                unset($params[$fieldKey]);
            }
        }

        $this->params = $params;

        return $this;
    }

    /**
     * 设置键值有效期
     *
     * @param int $second
     * @return $this
     */
    public function setCacheExpires(int $second): self
    {
        $this->expires = $second;

        return $this;
    }

    /**
     * 对参数数组json_encode格式化
     *
     * @param array $params
     * @return string
     */
    private function formatCacheValue(array $params): string
    {
        return json_encode($params, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取锁前缀key
     *
     * @return string
     */
    private function getPrefixCacheKey(): string
    {
        return self::IDEMPOTENCY_PREFIX_KEY . $this->module . ':' . $this->object . ':' . $this->action;
    }

    /**
     * 获取锁完整key
     *
     * @return string
     */
    private function getFormatCacheKey(): string
    {
        return $this->getPrefixCacheKey();
    }

    /**
     * 获取锁key应该存进去的value
     *
     * @return string
     */
    private function getFormatCacheValue(): string
    {
        return $this->formatCacheValue($this->params);
    }

    /**
     * 根据key获取集合中的元素数据
     *
     * @param string $key
     * @return array
     */
    public function getCacheValueByKey(string $key): array
    {
        return $this->redisConnection->zrange($key, 0, -1, true);//返回所有元素并带上scores
    }

    /**
     * 删除集合中的某个目标元素
     *
     * @param string $key
     * @param string $targetValue
     * @return bool
     */
    private function deleteCacheValueByValue(string $key, string $targetValue): bool
    {

        foreach ($this->getCacheValueByKey($key) as $valueKey => $expires) {
            if ($valueKey === $targetValue) {
                $this->redisConnection->zrem($key, $targetValue);
            }
        }

        return true;
    }

    /**
     * 删除集合中超过了有效期的元素
     *
     * @param string $key
     * @return bool
     */
    private function deleteCacheValueWhenPassExpires(string $key, array $cacheValue = []): bool
    {
        //扫描集合中的元素是否超过了有效期，是的话就直接删除对应的元素
        $timestamp = Carbon::now()->getTimestamp();

        if (empty($cacheValue)) {
            $cacheValue = $this->getCacheValueByKey($key);
        }

        foreach ($cacheValue as $valueKey => $expires) {
            if ($timestamp > intval($expires)) {
                $this->redisConnection->zrem($key, $valueKey);
            }
        }

        return true;
    }

    /**
     * 判断操作是否已经处理成功并被锁住了
     *
     * @param string $key
     * @return bool
     */
    private function hasLock(string $key): bool
    {

        //判断前先删除已经到期的集合元素
        $this->deleteCacheValueWhenPassExpires($key);

        //这次操作传递过来的参数
        $paramsValue = $this->getFormatCacheValue();

        //如果该操作传递的参数在集合中存在匹配的元素，则说明已经操作过了，直接判断为锁定
        foreach ($this->getCacheValueByKey($key) as $valueKey => $expires) {
            if ($valueKey === $paramsValue) {
                return true;
            }
        }

        return false;
    }

    /**
     * 自定义拦截处理
     *
     * @param callable $callback
     * @return $this
     */
    public function setInterceptCallback(callable $callback): self
    {
        $this->interceptCallback = $callback;

        return $this;
    }

    /**
     * 拦截处理
     *
     * @throws BusinessException
     */
    public function intercept()
    {

        $idempotencyKey = $this->getFormatCacheKey();

        //存在请求处理方法的key，说明已经处理成功了，拦截后面的处理
        if ($this->hasLock($idempotencyKey)) {

            //记录已经触发拦截
            $this->hasIntercept = true;

            //如果客户端有自己定义的拦截回调处理，就调用它
            if (!is_null($this->interceptCallback)) {

                try {
                    call_user_func($this->interceptCallback);
                } catch (\Throwable $throwable) {
                    throw new BusinessException(HttpCodeResponse::IDEMPOTENCY_LIMIT, $throwable->getMessage());
                }

            } else {
                throw new BusinessException(HttpCodeResponse::IDEMPOTENCY_LIMIT);
            }

        }

    }

    /**
     * 放行处理，记录标志
     */
    public function release(): void
    {
        //记录放行处理成功的标志
        $idempotencyKey = $this->getFormatCacheKey();

        //查询出现在key中对应集合的元素，后面重新写入并刷新有效时间
        foreach ($this->getCacheValueByKey($idempotencyKey) as $valueKey => $valueExpires) {
            $this->redisConnection->zadd($idempotencyKey, $valueExpires, $valueKey);
        }

        //存储有效期
        $expires = $this->expires ?: config('laratool.idempotency.redis.expires');

        //用有序集合存储每个操作的值，score用时间戳来表示，达到设置集合中每个元素的有效期的目的，到期用程序来删除
        $timestamp = Carbon::now()->getTimestamp() + $expires;
        $this->redisConnection->zadd($idempotencyKey, $timestamp, $this->getFormatCacheValue());

        //整个集合也给设置个有效期，比每个元素的有效期多60s即可
        $zSetExpires = $expires + 60;
        $this->redisConnection->expire($idempotencyKey, $zSetExpires);
    }

    /**
     * 调用客户端处理方法
     *
     * @param object $object
     * @param $method
     * @param ...$args
     * @return false|mixed
     * @throws BusinessException
     * @throws \Throwable
     */
    public function callAction(object $object, $method, ...$args)
    {

        try {

            //触发拦截，抛出异常不能继续往下执行业务逻辑
            $this->intercept();

            //触发放行，记录可以执行业务逻辑的标志
            $this->release();

            //调用客户端方法
            $result = call_user_func_array([$object, $method], ...$args);

            return $result;

        } catch (\Throwable $throwable) {

            //客户端方法逻辑处理失败(抛出异常)时，删除已经记录执行业务的标志，可以让客户端重试直到成功【排除是拦截抛出的异常】
            if (!$this->hasIntercept) {
                //删除已放行的缓存
                $this->deleteCacheValueByValue($this->getFormatCacheKey(), $this->getFormatCacheValue());
            }

            throw $throwable;

        }
    }

}
