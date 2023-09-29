<?php

namespace Marwa\Application\Migrations;

use Exception;
use Marwa\Application\DBForge\Forge;
use Marwa\Application\Exceptions\FileNotFoundException;

class TableSchema
{

	/**
	 * @var string
	 */
	var $table_name = null;

	/**
	 * [$engine description]
	 *
	 * @var string
	 */
	var $engine = 'InnoDB';

	/**
	 * [$charset description]
	 *
	 * @var string
	 */
	var $charset = 'utf8';

	/**
	 * [$collation description]
	 *
	 * @var string
	 */
	var $collation = 'utf8_general_ci';

	/**
	 * [$colSql description]
	 *
	 * @var array
	 */
	var $colSql = [];

	/**
	 * @var string
	 */
	protected $table_sql;

	/**
	 * TableSchema constructor.
	 * @param string $name
	 * @param array $tblOption
	 */
	public function __construct(string $name, $tblOption = [])
	{
		$this->table_name = $name;
		if (!empty($tblOption)) {
			if (array_key_exists('ENGINE', $tblOption)) {
				$this->engine = $tblOption['ENGINE'];
			}
			if (array_key_exists('CHARSET', $tblOption)) {
				$this->charset = $tblOption['CHARSET'];
			}
			if (array_key_exists('COLLATION', $tblOption)) {
				$this->collation = $tblOption['COLLATION'];
			}
		}
	}

	/**
	 * @param string|null $table
	 * @return mixed
	 */
	public function hasTable(string $table = null)
	{
		if (!is_null($table)) {
			$this->table_name = $table;
		}

		return Forge::tableExists($this->table_name);
	}

	/**
	 * @return int
	 */
	public function drop()
	{
		return Forge::dropIfExists($this->table_name);
	}

	/**
	 * @return mixed
	 */
	public function create()
	{
		return Forge::createTable($this->build());
	}

	/**
	 * @return string
	 */
	public function build(): string
	{
		$sql = implode(",", $this->colSql);
		$this->table_sql = "CREATE TABLE {$this->table_name} ({$sql}) ENGINE={$this->engine} CHARACTER SET={$this->charset} COLLATE {$this->collation}";

		return $this->table_sql;
	}

	/**
	 * @param $to
	 * @return mixed
	 */
	public function rename($to)
	{
		$sql = "RENAME TABLE {$this->table_name} TO {$to}";

		return app('DB')->rawQuery($sql);
	}

	/**
	 * @return mixed
	 */
	public function empty()
	{
		return Forge::empty($this->table_name);
	}

	/**
	 * @return $this
	 */
	public function id()
	{
		$this->addColumn('id', 'bigint', ['signed' => false, 'autoincre' => true, 'primary' => true]);

		return $this;
	}

	/**
	 * @param string $colName
	 * @param string $colType
	 * @param array $options
	 * @return $this
	 * @throws Exception
	 */
	public function addColumn(string $colName, string $colType, array $options = [])
	{
		$cols = new Column($colName, $colType, $options);
		$sql = $cols->getColumn();
		if (!$sql) {
			throw new Exception("Failed to build column sql");
		}
		$this->colSql[] = $sql;

		return $this;
	}

	/**
	 * @return $this
	 * @throws Exception
	 */
	public function timestamps()
	{
		$this->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP']);
		$this->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'update' => true]);

		return $this;
	}

	/**
	 * @param string $name
	 * @param int $limit
	 * @return $this
	 * @throws Exception
	 */
	public function integer(string $name, int $limit = 11)
	{
		$opt = [
			'limit' => $limit
		];
		$this->addColumn($name, 'INT', $opt);

		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws Exception
	 */
	public function bigInteger(string $name)
	{
		$this->addColumn($name, 'bigint', ['signed' => false]);

		return $this;
	}

	/**
	 * @param string $name
	 * @param int $limit
	 * @return $this
	 * @throws Exception
	 */
	public function tinyInteger(string $name, int $limit = 4)
	{
		$opt = [
			'limit' => $limit
		];
		$this->addColumn($name, 'TINYINT', $opt);

		return $this;
	}

	/**
	 * @param string $name
	 * @param int $limit
	 * @return $this
	 * @throws Exception
	 */
	public function unsignedInteger(string $name, int $limit = 11)
	{
		$opt = [
			'limit' => $limit,
			'signed' => false
		];
		$this->addColumn($name, 'INT', $opt);

		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws Exception
	 */
	public function boolean(string $name)
	{
		$opt = ['limit' => 1];
		$this->addColumn($name, 'TINYINT', $opt);

		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws Exception
	 */
	public function text(string $name)
	{
		$this->addColumn($name, 'TEXT');

		return $this;
	}

	/**
	 * @param string $name
	 * @return $this
	 * @throws Exception
	 */
	public function longtext(string $name)
	{
		$this->addColumn($name, 'LONGTEXT');

		return $this;
	}
	/**
	 * @param string $name
	 * @param $limit
	 * @param $scale
	 * @return $this
	 * @throws Exception
	 */
	public function decimal(string $name, $limit, $scale)
	{
		$opt = ['limit' => $limit, 'scale' => $scale];
		$this->addColumn($name, 'DECIMAL', $opt);

		return $this;
	}

	/**
	 * @param string $name
	 * @param array $opt
	 * @return $this
	 * @throws Exception
	 */
	public function timestamp(string $name, $opt = [])
	{
		$this->addColumn($name, 'TIMESTAMP', $opt);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function rememberToken()
	{
		return $this->strings('remember_token', 100);
	}

	/**
	 * @param string $name
	 * @param int $limit
	 * @return $this
	 * @throws Exception
	 */
	public function strings(string $name, int $limit = 100)
	{
		$opt = [
			'limit' => $limit
		];
		$this->addColumn($name, 'VARCHAR', $opt);

		return $this;
	}

	/**
	 * @param string $foreign_id
	 * @param string $reference_table
	 * @param string $id
	 * @param array $options
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	public function foreign($foreign_id, $reference_table, $id, $options = [])
	{
		$sql = "ALTER TABLE {$this->table_name} ADD";

		//custom constraint key
		if (array_key_exists('constraint', $options)) {
			$sql .= " CONSTRAINT {$options['constraint']}";
		}

		$sql .= " FOREIGN KEY ({$foreign_id}) REFERENCES {$reference_table}({$id})";

		/**
		 * If 'delete' keyword exists on option array
		 */
		if (array_key_exists('delete', $options)) {
			//RESTRICT | CASCADE | SET NULL | NO ACTION | SET DEFAULT
			$deleteOption = strtoupper($options['delete']);
			$sql .= " ON DELETE {$deleteOption}";
		}
		/**
		 * If 'update' keyword exists on option array
		 */
		if (array_key_exists('update', $options)) {
			//RESTRICT | CASCADE | SET NULL | NO ACTION | SET DEFAULT
			$updateOption = strtoupper($options['update']);
			$sql .= " ON UPDATE {$updateOption}";
		}

		return app('DB')->rawQuery($sql);

	}

	/**
	 * @param $fk
	 * @return mixed
	 * @throws FileNotFoundException
	 */
	public function dropForeign($fk)
	{

		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} DROP FOREIGN KEY {$fk}");
	}

	/**
	 * @param $pkey
	 * @return mixed
	 */
	public function primary($pkey)
	{
		$sql = "ALTER TABLE {$this->table_name} ADD";
		if (is_array($pkey)) {
			$sql .= " CONSTRAINT {$this->table_name}_id_primary";
			$pkey = implode(',', $pkey);
		}
		//ALTER TABLE Persons ADD PRIMARY KEY (ID);
		$sql .= " PRIMARY KEY ({$pkey})";

		return app('DB')->rawQuery($sql);
	}

	/**
	 * @return mixed
	 */
	public function dropPrimary()
	{
		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} DROP PRIMARY KEY");
	}

	/**
	 * @param $from
	 * @param $to
	 * @return mixed
	 */
	public function renameIndex($from, $to)
	{

		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} RENAME INDEX {$from} TO {$to}");
	}

	/**
	 * @param $indexname
	 * @return mixed
	 */
	public function unique($indexname)
	{
		return $this->index($indexname, true);
	}

	/**
	 * @param $column
	 * @param bool $unique
	 * @return mixed
	 */
	public function index($column, $unique = false)
	{
		$colStr = null;
		$lastStr = 'index';
		if ($unique) {
			$lastStr = 'unique';
		}

		if (is_array($column)) {
			$colStr = implode(',', $column);
			$indexname = "{$this->table_name}_{$column[0]}_{$lastStr}";
		} else {
			$colStr = $column;
			$indexname = "{$this->table_name}_{$colStr}_{$lastStr}";
		}
		if ($unique) {
			$sql = "CREATE UNIQUE INDEX {$indexname} ON {$this->table_name} ({$colStr})";
		} else {
			$sql = "CREATE INDEX {$indexname} ON {$this->table_name} ({$colStr})";
		}

		return app('DB')->rawQuery($sql);
	}

	/**
	 * @param $name
	 * @return mixed
	 */
	public function dropUnique($name)
	{
		return $this->dropIndex($name);
	}

	/**
	 * @param $indexname
	 * @return mixed
	 */
	public function dropIndex($indexname)
	{

		return app('DB')->rawQuery("DROP INDEX {$indexname} ON {$this->table_name}");
	}

	/**
	 * @param int $value
	 * @return mixed
	 */
	public function autoIncrement($value = 1)
	{
		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} AUTO_INCREMENT={$value}");
	}

	/**
	 * @param $columnName
	 * @return mixed
	 */
	public function dropColumn($columnName)
	{
		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} DROP COLUMN {$columnName}");
	}

	/**
	 * @param $columnName
	 * @param $dataType
	 * @return mixed
	 */
	public function newColumn($columnName, $dataType)
	{
		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} ADD COLUMN {$columnName} {$dataType}");
	}

	/**
	 * @param $columnName
	 * @param $dataType
	 * @return mixed
	 */
	public function modifyColumn($columnName, $dataType)
	{
		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} MODIFY COLUMN {$columnName} {$dataType}");
	}

	/**
	 * @param $from
	 * @param $to
	 * @return mixed
	 */
	public function renameColumn($from, $to)
	{

		return app('DB')->rawQuery("ALTER TABLE {$this->table_name} RENAME COLUMN {$from} TO {$to}");
	}
}