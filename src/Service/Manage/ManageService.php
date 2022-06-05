<?php

/**
 * User: CharleyChan
 * Date: 2022/6/5
 * Time: 10:43
 **/

use Illuminate\Http\Request;
use Charleychan\Laratool\Http\HttpCodeResponse;
use Charleychan\Laratool\Service\BaseService;

class ManageService extends BaseService
{
    /**
     * 模型
     * @var string
     */
    protected $model = '';

    /**
     * 模型查询作用域
     * @var string
     */
    protected $modelScope = '';

    /**
     * 关联模型
     * @var array
     */
    protected $with = [];

    /**
     * 枚举字段类型配置
     * @var array
     */
    public $config = [];

    /**
     * 设置关联模型with方法
     * @var string
     */
    protected $listsSetWithMethod = 'listsSetWith';

    /**
     * 处理循环列表数据方法
     * @var string
     */
    protected $listsMapMethod = 'listsMap';

    /**
     * 列表查询字段列举
     * @var string
     */
    protected $listsSelect = [];

    protected function __construct()
    {
        parent::__construct();

        if (!$this->model) {
            throw new \InvalidArgumentException('Property model is null!');
        }

    }

    protected function methodExists(string $method = '')
    {
        return method_exists($this, $method);
    }

    protected function listsBuilder(Request $request)
    {
        if (!$request) {
            return null;
        }

        if ($this->modelScope) {
            $builder = $this->model::{$this->modelScope}($request);
        } else {
            $builder = $this->model::query();
        }

        if (empty($this->listsSelect)) {
            $this->throwBusinessException(HttpCodeResponse::HTTP_FAIL);
        }

        $this->methodExists($this->listsSetWithMethod) && call_user_func([$this, $this->listsSetWithMethod]);

        if ($this->with) {
            $builder = $builder->with($this->with);
        }

        return $builder->select($this->listsSelect);
    }

    public function findConfigValByKey($val, string $key = '', string $default = '')
    {
        return isset($this->config[$key]) ? ($this->config[$key][$val] ?? $default) : $default;
    }

    public function fetchRow(array $where = [], array $select = ['*'], array $with = [], bool $limitOne = true)
    {
        if (!$where || !$select) {
            return [];
        }

        $queryBuilder = $this->model::query();

        if ($with) {
            $queryBuilder = $queryBuilder->with($with);
        }

        if ($limitOne) {
            return $queryBuilder->select($select)->where($where)->first();
        }

        return $queryBuilder->select($select)->where($where)->get();

    }

    /**
     * 列表
     *
     * @param Request $request
     * @return array
     */
    public function lists(Request $request)
    {
        $builder = $this->listsBuilder($request);
        if (is_null($builder)) {
            return [];
        }

        $limit = $request->limit ?: 30;
        $lists = $builder->paginate($limit);

        $this->methodExists($this->listsMapMethod) && $lists->map(function ($item) {
            return call_user_func_array([$this, $this->listsMapMethod], [$item]);
        });

        $data['total'] = $lists->total();
        $data['per_page'] = $lists->perPage();
        $data['current_page'] = $lists->currentPage();
        $data['data'] = $lists->items();

        return $data;

    }

    /**
     * 新增
     *
     * @param array $data
     * @return bool
     */
    public function store(array $data = [])
    {
        if (empty($data)) {
            $this->throwBusinessException(HttpCodeResponse::HTTP_FAIL);
        }

        $result = $this->model::query()->create($data);

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * 更新
     *
     * @param int $id
     * @param array $data
     * @param null $entity
     * @return bool
     */
    public function update(int $id, array $data = [], $entity = null)
    {
        if (empty($data)) {
            $this->throwBusinessException(HttpCodeResponse::HTTP_FAIL);
        }

        if ($entity) {
            foreach ($data as $field => $value) {
                $entity->{$field} = $value;
            }
            $result = $entity->save();

        } else {
            $result = $this->model::query()->where(['id' => $id])->update($data);
        }

        if ($result) {
            return true;
        }

        return false;
    }

    /**
     * 删除
     *
     * @param int $id
     * @return bool
     */
    public function destroy(int $id)
    {

        if (!$id) {
            throw new \InvalidArgumentException('ID is null!');
        }

        $entity = $this->fetchRow(['id' => $id]);
        if (empty($entity)) {
            throw new \InvalidArgumentException('There is no record match id ' . $id . '!');
        }

        if ($entity->delete()) {
            return true;
        }

        return false;
    }

    /**
     * 下拉框筛选项
     *
     * @return array
     */
    public function selector()
    {
        return $this->config;
    }
}