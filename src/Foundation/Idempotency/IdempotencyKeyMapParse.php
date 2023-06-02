<?php
/**
 * User: CharleyChan
 * Date: 2023/6/2
 * Time: 4:34 下午
 **/
declare(strict_types=1);

namespace Charleychan\Laratool\Foundation\Idempotency;


use Illuminate\Support\Arr;

/**
 * 幂等性key=>map配置信息解析工具
 *
 * Class IdempotencyKeyMapParse
 * @package Morejoy\Larabase\Foundation\Idempotency
 */
class IdempotencyKeyMapParse
{
    /**
     * @var array 操作名字对应 [module.object.action] key
     */
    private $keys = [];

    /**
     * @var array [module.object.action] key 对应 操作方法参数
     */
    private $maps = [];

    /**
     * IdempotencyKeyMapParse constructor.
     * @param array $keys
     * @param array $maps
     */
    public function __construct(array $keys = [], array $maps = [])
    {
        $this->keys = $keys ?: config('idempotency_key');
        $this->maps = $maps ?: config('idempotency_map');
    }

    /**
     * 解析幂等性key=>map配置信息
     *
     * @param string $key
     * @return array
     */
    public function parse(string $key): array
    {

        //根据key找出keys中 module.object.action
        $moduleObjectActionStr = $this->keys[$key] ?? '';

        if (empty($moduleObjectActionStr)) {
            return [
                'module' => '',
                'object' => '',
                'action' => '',
                'action_param_field' => [],
            ];
        }

        //再根据module.object.action从map配置文件中找出本操作对应的参数params
        $actionMaps = Arr::get($this->maps, $moduleObjectActionStr, []);

        //params 字段参数
        $actionParamFields = $actionMaps['params'] ?? [];

        list($module, $object, $action) = explode('.', $moduleObjectActionStr);

        return [
            'module' => $module,
            'object' => $object,
            'action' => $action,
            'action_param_field' => $actionParamFields,
        ];
    }

}
