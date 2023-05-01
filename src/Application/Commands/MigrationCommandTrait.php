<?php

namespace Marwa\Application\Commands;

use Marwa\Application\DBForge\Forge;
use Marwa\Application\Facades\DB;

trait MigrationCommandTrait
{

	/**
	 * [hasTable description]
	 *
	 * @return boolean [description]
	 */
	public function hasTable()
	{
		return Forge::tableExists('migration');
	}

	/**
	 * @param $id
	 * @param $fileName
	 * @param $msg
	 */
	public function migrationHistory($id, $fileName, $msg)
	{
		if ($this->db("INSERT INTO migration(id,version,applied_at,description) VALUES ({$id},'{$fileName}','pending','{$msg}')")) {
			$this->info("Migration table log created.");
		} else {
			$this->error("Migration table log failed to create");
		}
	}

	/**
	 * @param string $sql
	 * @return mixed
	 */
	protected function db(string $sql)
	{
		return DB::rawQuery($sql);
	}

	/**
	 * @param $id
	 * @param $applied
	 */
	public function updateMigrationHistory($id, $applied)
	{
		if ($this->db("UPDATE migration SET applied_at='{$applied}' WHERE id = '{$id}'")) {
			$this->info("Migration table log status updated for " . $id);
		} else {
			$this->error("Migration table log status not updated for " . $id);
		}
	}

	/**
	 * @param null $id
	 * @return mixed
	 */
	public function getAllMigration($id = null)
	{
		$sql = 'SELECT * FROM migration';
		if (!is_null($id)) {
			$sql .= " WHERE id ='{$id}'";
		}

		return $this->db($sql);
	}

	/**
	 * Delete migration row from migration table by id
	 * @param $id integer
	 * */
	public function deleteMigrationRowById($id)
	{
		return $this->db("DELETE FROM migration WHERE id='$id'");
	}

	/**
	 * @param $id
	 * @param bool $down
	 * @return array
	 */
	public function getMigrateFilesById($id, $down = false)
	{
		$sql = "SELECT * FROM migration where id = '{$id}'";
		$rows = $this->db($sql);
		if (count($rows) > 0) {
			if ($rows[0]['applied_at'] != 'pending' && !$down) {
				$this->error("Migration already applied for " . $id);
			}

			$files = [];
			foreach ($rows as $key => $value) {
				$files[$value['id']] = $value['version'];
			}

			return $files;
		} else {
			$this->info("Migration ID not found");
		}
	}

	/**
	 * @param bool $applied
	 * @return array|bool
	 */
	public function getMigrationPending($applied = false)
	{
		$sql = "SELECT * FROM migration where applied_at !='pending'";
		if (!$applied) {
			$sql = "SELECT * FROM migration where applied_at ='pending'";
		}

		$rows = $this->db($sql);

		if (count($rows) > 0) {
			$files = [];
			foreach ($rows as $key => $value) {
				$files[$value['id']] = $value['version'];
			}

			return $files;
		}

		return false;
	}

	/**
	 * @param array $fileArr
	 */
	public function requiredOnce(array $fileArr)
	{
		if (empty($fileArr) or !isset($fileArr)) {
			$this->info("Nothing to migrate");
			die;
		}

		$dirName = $this->getMigrationPath();

		foreach ($fileArr as $key => $value) {
			$fileName = $dirName . $value . '.php';
			if (file_exists($fileName)) {
				include_once($fileName);
			}
		}
	}

	/**
	 * @return string
	 */
	public function getMigrationPath()
	{
		return WEBROOT . DIRECTORY_SEPARATOR . "database" . DIRECTORY_SEPARATOR . "migrations" . DIRECTORY_SEPARATOR;
	}


}