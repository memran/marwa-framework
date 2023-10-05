<?php

namespace Marwa\Application\Models;

use ArrayAccess;
use Exception;
use Iterator;
use Marwa\Application\Exceptions\FileNotFoundException;
use Marwa\Application\Exceptions\InvalidArgumentException;
use Marwa\Application\Utils\
{
	Collection,
	Hash,
	SmartObject
};
use ReflectionClass;
use ReflectionException;

abstract class Model implements ArrayAccess, Iterator
{

	use SmartObject, Relation;

	/**
	 * [$_table description]
	 *
	 * @var null
	 */
	protected $table = null;

	/**
	 * [$_primaryKey description]
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * @var null Database connection name
	 */
	protected $connection = null;

	/**
	 * [$timestamps description]
	 *
	 * @var boolean
	 */
	protected $timestamps = false;

	/**
	 * @var Pagination
	 */
	protected $paginator;

	/**
	 * @var array
	 */
	protected $fillable = [];

	/**
	 * @var array
	 */
	protected $guarded = [];
	/**
	 * @var array
	 */
	protected $hidden = [];

	/**
	 * @var array
	 */
	protected $attributes = [];

	/**
	 * @var array
	 */
	protected $_condition = [];
	/**
	 * @var array
	 */
	protected $relation = [];
	/**
	 * @var array
	 */
	protected $cast = [];
	/**
	 * @var mixed
	 */
	protected $result = [];
	/**
	 * @var array
	 */
	private $dirty = [];
	/**
	 * @var mixed
	 */
	private $builder;

	/**
	 * AppModel constructor.
	 *
	 * @param array $data
	 * @throws Exception
	 */
	public function __construct(array $data = [])
	{
		if (!empty($data)) {
			$this->data = $data;
		}

		$this->bootModel();
	}

	/**
	 *
	 * @throws Exception
	 */
	protected function bootModel()
	{
		$this->fireEvent("booting");
		$this->createBuilder();
		$this->boot();
		$this->fireEvent("booted");
	}

	/**
	 * @param string $eventName
	 */
	protected function fireEvent(string $eventName)
	{
		app('event')->fire($eventName);
	}

	/**
	 * @throws Exception
	 */
	protected function createBuilder()
	{
		$this->builder = new QueryBuilder($this->getTable(), $this->getConnection());
	}

	/**
	 * @return string
	 * @throws ReflectionException
	 */
	protected function getTable(): string
	{
		if (!isset($this->table)) {
			$obj = new ReflectionClass($this);
			$this->table = strtolower($obj->getShortName());
		}

		return $this->table;
	}

	/**
	 * @param string $table
	 * @return $this
	 */
	protected function setTable(string $table)
	{
		if (!empty($table)) {
			$this->table = $table;
		}

		return $this;
	}

	/**
	 * @return bool|null
	 */
	protected function getConnection()
	{
		return $this->connection;
	}

	/**
	 *
	 */
	public function boot()
	{
		//boot function
	}

	/**
	 * @param string $method
	 * @param mixed $args
	 * @return mixed
	 * @throws Exception
	 */
	public static function __callStatic($method, $args)
	{
		$instance = new static();

		return call_user_func_array([$instance->buildCondition(), $method], $args);
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	private function buildCondition()
	{
		$builder = $this->getQueryBuilder();
		if (!empty($this->getCondition())) {
			foreach ($this->getCondition() as $index => $value) {
				$method = key($value);
				call_user_func_array([$builder, $method], $value[$method]);
			}
		}

		return $builder;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	protected function getQueryBuilder()
	{
		if (isset($this->builder)) {
			return $this->builder->getBuilder();
		}
		$this->createBuilder();

		return $this->builder->getBuilder();
	}

	/**
	 * @return array
	 */
	protected function getCondition()
	{
		return $this->_condition;
	}

	/**
	 * @param string $condition
	 * @param array $args
	 * @throws Exception
	 */
	protected function setCondition(string $condition, array $args)
	{
		if (nullEmpty($args)) {
			throw new Exception("Condition value is empty");
		}
		$tempCondition[$condition] = $args;
		array_push($this->_condition, $tempCondition);
	}

	/**
	 * @param array $columns
	 * @return $this
	 * @throws Exception
	 */
	public function all(array $columns = ['*'])
	{
		if (!empty($columns)) {
			$this->select($columns);
		}

		$this->fetchResult();

		return $this;
	}

	/**
	 * @return mixed
	 * @throws Exception
	 */
	protected function fetchResult()
	{
		$data = $this->buildCondition()->get();
		$this->setResult($this->hide($data));
	}

	/**
	 * @param  $result
	 * @return mixed
	 */
	protected function hide($result)
	{
		/**
		 * if result is not array then return result
		 */
		if (!is_array($result)) {
			return $result;
		}
		/**
		 *  if hidden is empty then no need to go further
		 */
		if (empty($this->hidden)) {
			return $result;
		}
		/**
		 * if result is array then It will loop through the result and
		 * hide the column which will not display
		 */
		$filtered = [];
		foreach ($result as $item => $value) {
			foreach ($value as $key => $val) {
				/**
				 * if Result key is in the hidden array then unset value from the result array
				 */
				if (in_array($key, $this->hidden)) {
					unset($value[$key]);
				}
			}
			$filtered[] = $value;
		}

		/**
		 * Return the filtered data
		 */
		return $filtered;
	}

	/**
	 * @param  $id
	 * @param string[] $columns
	 * @return array|mixed
	 * @throws InvalidArgumentException
	 * @throws ModelNotFoundException
	 */
	public function findOrFail($id, $columns = ['*'])
	{
		$res = $this->find($id, $columns);
		if (!$res) {
			throw new ModelNotFoundException("Model not found");
		}
		if (!$this->exists()) {
			throw new ModelNotFoundException("Model not found");
		}

		return $this;
	}

	/**
	 * @param int $id
	 * @param string[] $columns
	 * @return array|mixed
	 * @throws InvalidArgumentException
	 * @throws Exception
	 */
	public function find($id, $columns = ['*'])
	{
		if (is_array($id)) {
			$this->setCondition("whereIn", [$this->getPrimaryKey(), $id]);
		} else {
			$this->setCondition("where", [$this->getPrimaryKey(), '=', $id]);
		}
		if (!empty($columns)) {
			$this->select($columns);
		}
		$this->fetchResult();

		return $this;
	}

	/**
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function getPrimaryKey(): string
	{

		if (!isset($this->primaryKey)) {
			throw new InvalidArgumentException("Primary key not defined");
		}

		return $this->primaryKey;
	}

	/**
	 * @return bool
	 */
	public function exists()
	{
		if (empty($this->result)) {
			return false;
		}

		return true;
	}

	/**
	 * @param $id
	 * @param array $data
	 * @return $this|array|mixed
	 * @throws InvalidArgumentException
	 */
	public function findOrCreate($id, array $data)
	{
		$this->find($id);
		if (!$this->exists()) {
			return $this->create($data);
		}

		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 * @throws InvalidArgumentException
	 */
	public function create(array $data)
	{
		if (empty($data)) {
			throw new InvalidArgumentException('Model Data create failed.Empty data provided');
		}

		$this->fireEvent('creating');
		$this->setFillableData($data);
		//set default time stamp
		if ($this->timestamps) {
			$this->attributes['created_at'] = now()->toDateTimeString();
			$this->attributes['updated_at'] = now()->toDateTimeString();
		}

		return $this;
	}

	/**
	 * @param array $collection
	 */
	protected function setFillableData(array $collection = [])
	{
		if (!empty($collection)) {
			$this->attributes = $collection;
		} else {
			$this->attributes = $this->getResult();
		}
	}

	/**
	 * @return array
	 */
	public function getResult()
	{
		return $this->result;
	}

	/**
	 * @param array $result
	 */
	public function setResult(array $result)
	{
		if (is_array($result) && !empty($result) && count($result) === 1) {
			$this->result = $result[0];
			$this->setFillableData();
		} else {
			$this->result = $result;
		}
	}

	/**
	 * @param string $field
	 * @param mixed $attributes
	 * @param string $operator
	 * @return array|mixed
	 * @throws Exception
	 */
	public function findBy(string $field, $attributes, string $operator = '=')
	{
		$this->setCondition('where', [$field, $operator, $attributes]);
		$this->fetchResult();

		return $this;
	}

	/**
	 * @param string $method
	 * @param mixed $args
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($method, $args)
	{
		try {
			//return call_user_func_array([$this->buildCondition(), $method], $args);
			return call_user_func_array([$this->getQueryBuilder(), $method], $args);
		} catch (\Throwable $th) {
			throw new Exception($th);
		}
	}

	/**
	 * @return bool
	 */
	public function isClean(): bool
	{
		/**
		 *  Check if attributes are not touched , then return true other return false
		 */
		if (empty($this->dirty)) {
			return true;
		}

		return false;
	}

	/**
	 * @param string $key
	 * @return array|null
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
		/**
		 *  if attributes data is changed then store changed attribute key in the array for further usage
		 */
		if (array_key_exists($key, $this->attributes)) {
			array_push($this->dirty, $key);
		}

		$this->setAttribute($key, $value);
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function getAttribute(string $key)
	{
		if ($this->hasGetMutator($key)) {
			if (array_key_exists($key, $this->attributes)) {
				$value = $this->attributes[$key];
			} else {
				$value = null;
			}

			return $this->executeGetMutate($key, $value);
		}

		if (array_key_exists($key, $this->attributes)) {
			return $this->attributes[$key];
		} else {
			if (method_exists($this, $key)) {
				return $this->$key()->getRelation();
			}

			return null;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasGetMutator($key)
	{
		$method = 'get' . $this->str_studly($key) . 'Attribute';

		return method_exists($this, $method);
	}

	/**
	 * @param string $str
	 * @return string
	 */
	protected function str_studly($str)
	{
		$data = explode('_', $str);
		if (is_string($data)) {
			return lcfirst($data);
		} else {
			if (is_array($data) && !empty($data)) {
				$str = '';
				foreach ($data as $k => $v) {
					$str .= ucfirst($v);
				}

				return $str;
			} else {
				return $str;
			}
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	protected function executeGetMutate($key, $value = null)
	{
		$method = 'get' . $this->str_studly($key) . 'Attribute';

		return call_user_func_array([$this, $method], [$value]);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 */
	public function setAttribute(string $key, $value)
	{
		if ($this->hasSetMutator($key)) {
			$this->attributes[$key] = $this->executeSetMutate($key, $value);
		} else {
			$this->attributes[$key] = $value;
		}
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function hasSetMutator($key)
	{
		$method = 'set' . $this->str_studly($key) . 'Attribute';

		return method_exists($this, $method);
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	protected function executeSetMutate($key, $value = null)
	{
		$method = 'set' . $this->str_studly($key) . 'Attribute';

		return call_user_func_array([$this, $method], [$value]);
	}

	/**
	 * @param string $name
	 * @return $this
	 */
	public function on(string $name)
	{
		if (!empty($name)) {
			$this->connection = $name;
		}

		return $this;
	}

	/**
	 * @param $index
	 * @param $key
	 * @param $data
	 * @throws Exception
	 */
	public function appendResult($index, $key, $data)
	{
		if (isset($this->result[$index])) {
			$this->result[$index][$key] = array_values($data);
		} else {
			throw new Exception("Invalid Index for append result");
		}
	}

	/**
	 * @return mixed
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	public function getId()
	{
		return $this->getPrimaryKeyId();
	}

	/**
	 * @return mixed
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	protected function getPrimaryKeyId()
	{
		if (isset($this->result[$this->getPrimaryKey()])) {
			return $this->result[$this->getPrimaryKey()];
		} else {
			throw new InvalidPrimaryKey("Primary key not found");
		}
	}

	/**
	 * @return mixed
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 * @throws FileNotFoundException
	 */
	public function save()
	{
		$this->fireEvent('saving');
		if ($this->isDirty()) {
			$res = $this->performUpdate();
		} else {
			$res = $this->performInsert();
		}
		//trigger event
		$this->fireEvent('saved');

		//$this->reset();

		return $res;
	}

	/**
	 * @param string|null $field
	 * @return bool
	 */
	public function isDirty(string $field = null): bool
	{
		/**
		 * Check If data has changed
		 */
		if (empty($this->dirty)) {
			return false;
		}
		/**
		 *  if field name provided then it will check those field has changed or not
		 */
		if (!is_null($field)) {
			return in_array($field, $this->dirty);
		}

		return true;
	}

	/**
	 * @return mixed
	 * @throws InvalidArgumentException
	 * @throws InvalidPrimaryKey
	 */
	protected function performUpdate()
	{
		//trigger updating event
		$this->fireEvent('updating');
		//set primary key id
		$id = $this->getPrimaryKeyId();

		//remove id value from fillable data
		$this->removePrimaryKeyId();

		//data filter and ready for insert
		$data = $this->getFillableData();

		//enable timestamps
		if ($this->timestamps) {
			$data['updated_at'] = now()->toDateTimeString();
		}

		$res = $this->where($this->getPrimaryKey(), '=', $id)->update($data);

		//trigger event after update
		$this->fireEvent('updated');

		return $res;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	protected function removePrimaryKeyId()
	{
		unset($this->attributes[$this->getPrimaryKey()]);
	}

	/**
	 * @return array
	 */
	protected function getFillableData()
	{
		return $this->filter($this->attributes);
	}

	/**
	 * @param  $collection
	 * @return mixed
	 */
	protected function filter(array $collection)
	{
		/**
		 * if collection are not array then return same
		 */
		if (!is_array($collection)) {
			return $collection;
		}
		/**
		 *  if attribute fillable array is not empty then it will call guard function for whitelist check
		 *  and return the value
		 */
		if (empty($this->fillable)) {
			return $this->guard($collection);
		}

		foreach ($collection as $k => $value) {
			if (!in_array($k, $this->fillable)) {
				unset($collection[$k]);
			}
		}

		/**
		 *  return attributes after check fillable and guard finally
		 */
		return $this->guard($collection);
	}

	/**
	 *  Black list
	 *
	 * @param array $collection
	 * @return array
	 */
	protected function guard(array $collection)
	{
		if (empty($this->guarded) || !is_array($collection)) {
			return $collection;
		}
		foreach ($collection as $k => $value) {
			if (in_array($k, $this->guarded)) {
				unset($collection[$k]);
			}
		}

		//return fresh data after guarded
		return $collection;
	}

	/**
	 * @return mixed
	 */
	protected function performInsert()
	{
		//set result
		$res = $this->insertGetId($this->getFillableData());

		if ($res == !false) {
			$this->setAttribute('id', $res);
			$this->result = $this->attributes;
		}
		//trigger created event
		$this->fireEvent('created');

		return $res;
	}

	/**
	 * @return mixed
	 */
	public function getOriginal()
	{
		return $this->getResult();
	}

	/**
	 * @param  $id
	 * @return mixed
	 * @throws InvalidArgumentException
	 */
	public function destroy(...$id)
	{
		$this->fireEvent('deleting');
		$count = 0;
		//if attributes is array
		if (is_array($id) && !empty($id)) {
			foreach ($id as $item => $value) {
				//$this->setCondition("where", [$this->getPrimaryKey(), '=', $value]);
				$result = $this->where($this->getPrimaryKey(), '=', $value)->delete();
				if ($result) {
					$count++;
				}
			}
		} else {
			//$this->setCondition("where", [$this->getPrimaryKey(), '=', $id]);
			$count = $this->where($this->getPrimaryKey(), '=', $this->getPrimaryKeyId())->delete();
		}
		$this->fireEvent('deleted');
		$this->reset();

		return $count;
	}

	/**
	 *
	 */
	protected function reset()
	{
		//$this->attributes = [];
		$this->_condition = [];
		//$this->result = [];
		$this->dirty = [];
	}

	/**
	 * @return Collection
	 */
	public function collect()
	{
		return new Collection($this->getResult());
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		return (new Collection($this->getResult()))->toArray();
	}

	/**
	 * @return Collection
	 */
	public function toCollect()
	{
		return new Collection($this->getResult());
	}

	/**
	 * @return Hash
	 */
	public function toObject()
	{
		return Hash::from($this->getResult());
	}

	/**
	 * @return false|string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

	/**
	 * @param int $code
	 * @return false|string
	 */
	public function toJson($code = JSON_PRETTY_PRINT)
	{
		if (!empty($this->getResult())) {
			return json_encode($this->getResult(), $code);
		}

		return json_encode([], $code);
	}

	/**
	 * @param string|null $sql
	 * @param array $args
	 * @return mixed
	 */
	public function query(string $sql = null, $args = [])
	{
		if (!nullEmpty($sql)) {
			return $this->builder->getDb()->raw($sql, $args);
		}

		return $this->builder->getDb();
	}

	/**
	 * ArrayAccess Implementation
	 */
	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset): bool
	{
		return (bool) isset($this->result[$offset]);
	}

	/**
	 * @param mixed $offset
	 * @return array|mixed
	 */
	public function offsetGet($offset): mixed
	{
		return $this->result[$offset];

	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet($offset, $value): void
	{
		$this->result[$offset] = $value;
	}

	/**
	 * @param mixed $offset
	 */
	public function offsetUnset($offset): void
	{
		unset($this->result[$offset]);
	}

	/**
	 * Iterator Implementation
	 */

	/**
	 * @return mixed
	 */
	public function current(): mixed
	{
		return current($this->result);
	}

	/**
	 * @return bool|float|int|string|null
	 */
	public function key(): mixed
	{
		return key($this->result);
	}

	/**
	 * @return mixed|void
	 */
	public function next(): void
	{
		next($this->result);
	}

	/**
	 * @return mixed|void
	 */
	public function rewind(): void
	{
		reset($this->result);
	}

	/**
	 * @return bool
	 */
	public function valid(): void
	{
		key($this->result) !== null;
	}

	/**
	 * @param int $per_page
	 * @return Pagination
	 * @throws Exception
	 */
	public function paginate(int $per_page = 25)
	{
		$this->paginator = new Pagination($per_page, $this->count(), $this->buildCondition());

		$this->setResult($this->paginator->data());

		return $this->paginator;
	}

	/**
	 * @return int
	 * @throws Exception
	 */
	public function count()
	{
		if (empty($this->result)) {
			$total_rows = $this->buildCondition()->count()->get();

			return (int) reset($total_rows)['total'];
		}

		return count($this->result);
	}

	/**
	 * @param $scopeFunction
	 * @return $this
	 */
	protected function addGlobalScope($scopeFunction)
	{
		if (is_callable($scopeFunction)) {
			$scopeFunction($this);
		}

		return $this;
	}


}