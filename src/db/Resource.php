<?php
namespace paw\db;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;

abstract class Resource extends Model implements ActiveQueryInterface
{
    protected $_dataprovider = null;

    protected $_query = null;

    protected $_pagination = false;

    protected $_sort = false;

    protected $_findMethod = 'find';

    protected $_listFormat = null;

    abstract public static function modelClass();

    public function rules()
    {
        $modelClass = static::modelClass();
        return (new $modelClass)->rules();
    }

    public function search($query)
    {

    }

    public function map($from, $to = null, $group = null)
    {
        if (is_array($from)) {
            $to = function ($model) use ($from) {
                $format = [];
                foreach ($from as $key => $value) {
                    $format[$key] = $model->{$value};
                }
                return $format;
            };
            $from = 'id';
            $this->_listFormat = [$from, $to, $group];
        } else {
            $this->_listFormat = [$from, $to, $group];
        }
        return $this;
    }

    public static function getInstance($config = [])
    {
        return new static($config);
    }

    public function setFindMethod($findMethod)
    {
        $this->_query = null;
        $this->_findMethod = $findMethod;
    }

    public function getFindMethod()
    {
        return $this->_findMethod;
    }

    public function getQuery()
    {
        if ($this->_query === null) {
            $modelClass = static::modelClass();
            $this->_query = call_user_func([$modelClass, $this->getFindMethod()]);
        }
        return $this->_query;
    }

    public function withTrashed()
    {
        $this->setFindMethod('findWithTrashed');
        return $this;
    }

    public function trashed()
    {
        $this->setFindMethod('findTrashed');
        return $this;
    }

    public function getDataProvider()
    {
        if ($this->_dataprovider === null) {
            $query = $this->getQuery();

            $this->loadQueryParams();

            $this->search($query);

            $this->_dataprovider = new ActiveDataProvider([
                'query' => $query,
                'pagination' => $this->_pagination,
                'sort' => $this->_sort,
            ]);
        }
        return $this->_dataprovider;
    }

    protected function loadQueryParams()
    {
        if (Yii::$app instanceof \yii\web\Application) {
            $this->load(Yii::$app->request->queryParams);
            // print_r(Yii::$app->request->queryParams);die;
        }
    }

    public function sort($sort)
    {
        $this->_sort = $sort;
        return $this;
    }

    public function all($db = null)
    {
        $dataProvider = $this->getDataProvider();
        $models = $dataProvider->models;

        if ($models && $this->_listFormat) {
            $params = \yii\helpers\ArrayHelper::merge([$models], $this->_listFormat);
            $models = call_user_func_array([\yii\helpers\ArrayHelper::class, 'map'], $params);
        }

        return $models;
    }

    public function one($db = null)
    {
        $dataProvider = $this->getDataProvider();
        return $dataProvider->query->one();
    }

    public function count($q = '*', $db = null)
    {
        $dataProvider = $this->getDataProvider();
        return $dataProvider->count;
    }

    public function exists($db = null)
    {
        $query = $this->getQuery();
        return $query->exists($db);
    }

    public function indexBy($column)
    {
        $query = $this->getQuery();
        $query->indexBy($column);
        return $this;
    }

    public function where($condition)
    {
        $query = $this->getQuery();
        $query->where($condition);
        return $this;
    }

    public function andWhere($condition)
    {
        $query = $this->getQuery();
        $query->andWhere($condition);
        return $this;
    }

    public function orWhere($condition)
    {
        $query = $this->getQuery();
        $query->orWhere($condition);
        return $this;
    }

    public function filterWhere(array $condition)
    {
        $query = $this->getQuery();
        $query->filterWhere($condition);
        return $this;
    }

    public function andFilterWhere(array $condition)
    {
        $query = $this->getQuery();
        $query->andFilterWhere($condition);
        return $this;
    }

    public function orFilterWhere(array $condition)
    {
        $query = $this->getQuery();
        $query->orFilterWhere($condition);
        return $this;
    }

    public function orderBy($columns)
    {
        $query = $this->getQuery();
        $query->orderBy($columns);
        return $this;
    }

    public function addOrderBy($columns)
    {
        $query = $this->getQuery();
        $query->addOrderBy($columns);
        return $this;
    }

    public function limit($limit)
    {
        $this->_pagination = ['pageSize' => $limit];
        return $this;
    }

    public function offset($offset)
    {
        $query = $this->getQuery();
        $query->offset($offset);
        return $this;
    }

    public function emulateExecution($value = true)
    {
        $query = $this->getQuery();
        $query->emulateExecution($value);
        return $this;
    }

    public function asArray($value = true)
    {
        $query = $this->getQuery();
        $query->asArray($value);
        return $this;
    }

    public function with()
    {
        $query = $this->getQuery();
        $query->with();
        return $this;
    }

    public function via($relationName, callable $callable = null)
    {
        $query = $this->getQuery();
        $query->via($relationName, $callable);
        return $this;
    }

    public function findFor($name, $model)
    {
        $query = $this->getQuery();
        $query->findFor($name, $model);
        return $this;
    }

    public function __call($method, $params)
    {
        $whiteListedMethods = ['getQuery', 'hasMethod'];
        if (!in_array($method, $whiteListedMethods)) {
            $query = $this->getQuery();
            if (!$this->hasMethod($method) && $query->hasMethod($method)) {
                return call_user_func_array([$query, $method], $params);
            }
        }
        return parent::__call($method, $params);
    }
}
