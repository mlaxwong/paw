<?php
namespace paw\db;

use Yii;
use yii\base\Model;
use yii\db\ActiveQueryInterface;
use yii\data\ActiveDataProvider;

abstract class Resource extends Model implements ActiveQueryInterface
{
    protected $_query = null;

    protected $_pagination = false;

    protected $_sort = false;

    abstract public static function modelClass();

    public function rules()
    {
        $modelClass = static::modelClass();
        return (new $modelClass)->rules();
    }

    public function search($query)
    {

    }

    public static function getInstance($config = [])
    {
        return new static($config);
    }

    public function getQuery()
    {
        if ($this->_query === null)
        {
            $modelClass = static::modelClass();
            $this->_query = $modelClass::find();
        }
        return $this->_query;
    }

    public function getDataProvider()
    {
        $query = $this->getQuery();
        
        $this->loadQueryParams();

        $this->search($query);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => $this->_pagination,
            'sort' => $this->_sort,
        ]);
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
        return $dataProvider->models;
    }

    public function one($db = null)
    {
        $dataProvider = $this->getDataProvider();
        return isset($dataProvider->models[0]) ? $dataProvider->models[0] : null;
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
        $query->orderBy($condition);
        return $this;
    }

    public function addOrderBy($columns)
    {
        $query = $this->getQuery();
        $query->addOrderBy($condition);
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
}