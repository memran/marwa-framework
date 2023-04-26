<?php
	
	namespace Marwa\Application\Migrations;
	
	use Exception;
	
	class Column {
		
		/**
		 * [$name description]
		 *
		 * @var null
		 */
		var $name = null;
		/**
		 * [$type description]
		 *
		 * @var null
		 */
		var $type = null;
		
		/**
		 * [$options description]
		 *
		 * @var array
		 */
		var $options = [];
		
		/**
		 * [$colSql description]
		 *
		 * @var null
		 */
		var $colSql = null;
		/**
		 * [$_valid_col_type description]
		 *
		 * @var [type]
		 */
		var $_valid_col_type = ['TINYINT', 'SMALLINT', 'MEDIUMINT', 'INT', 'BIGINT', 'FLOAT', 'DOUBLE',
		                        'DECIMAL', 'CHAR', 'VARCHAR', 'TINYTEXT', 'TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'DATE',
		                        'DATETIME', 'TIMESTAMP', 'TIME', 'YEAR', 'ENUM', 'BINARY', 'VARBINARY', 'TINYBLOB',
		                        'BLOB', 'MEDIUMBLOB', 'LONGBLOB'
		];
		/**
		 * [$_valid_col_options description]
		 *
		 * @var [type]
		 */
		var $_col_option_rules = [
			'TINYINT' => ['limit', 'default', 'null', 'comment', 'signed', 'autoincre', 'primary', 'zerofill'],
			'SMALLINT' => ['limit', 'default', 'null', 'comment', 'signed', 'autoincre', 'primary', 'zerofill'],
			'MEDIUMINT' => ['limit', 'default', 'null', 'comment', 'signed', 'autoincre', 'primary', 'zerofill'],
			'INT' => ['limit', 'default', 'null', 'comment', 'signed', 'autoincre', 'primary', 'zerofill'],
			'BIGINT' => ['limit', 'default', 'null', 'comment', 'signed', 'autoincre', 'primary', 'zerofill'],
			'FLOAT' => ['limit', 'scale', 'default', 'null', 'comment'],
			'DOUBLE' => ['limit', 'scale', 'default', 'null', 'comment'],
			'DECIMAL' => ['limit', 'scale', 'default', 'null', 'comment'],
			'DATE' => ['default', 'null', 'comment', 'update'],
			'DATETIME' => ['default', 'null', 'comment', 'update'],
			'TIMESTAMP' => ['default', 'null', 'comment', 'update'],
			'YEAR' => ['limit', 'default', 'null', 'comment'],
			'CHAR' => ['limit', 'default', 'null', 'comment'],
			'VARCHAR' => ['limit', 'default', 'null', 'comment'],
			'TINYTEXT' => ['default', 'null', 'comment'],
			'TEXT' => ['default', 'null', 'comment'],
			'MEDIUMTEXT' => ['default', 'null', 'comment'],
			'LONGTEXT' => ['default', 'null', 'comment'],
			'ENUM' => ['value', 'default', 'null', 'comment'],
			'BINARY' => ['limit', 'default', 'null', 'comment'],
			'VARBINARY' => ['limit', 'default', 'null', 'comment'],
			'TINYBLOB' => ['default', 'null', 'comment'],
			'BLOB' => ['default', 'null', 'comment'],
			'MEDIUMBLOB' => ['default', 'null', 'comment'],
			'LONGBLOB' => ['default', 'null', 'comment'],
		];
		
		/**
		 * [$_foreign_key description]
		 *
		 * @var [type]
		 */
		var $_foreign_key = ['update', 'delete'];
		
		/**
		 * Column constructor.
		 * @param $colName
		 * @param $colType
		 * @param array $options
		 * @throws Exception
		 */
		public function __construct( $colName, $colType, $options = [] )
		{
			
			if ( !in_array(strtoupper($colType), $this->_valid_col_type) )
			{
				throw new Exception("Column Type does not exists");
			}
			//set column name
			$this->name = trim($colName);
			//set column type
			$this->type = strtoupper($colType);
			//validate options
			if ( !empty($options) )
			{
				$this->options = $options;
			}
			//build the column
			$this->buildColumn();
		}
		
		/**
		 * @throws Exception
		 */
		public function buildColumn()
		{
			$this->colSql = "{$this->name} {$this->type}";
			if ( !empty($this->options) )
			{
				$this->buildOptions();
			}
		}
		
		/**
		 * @throws Exception
		 */
		protected function buildOptions()
		{
			switch ( $this->type )
			{
				case 'BIGINT':
				case 'MEDIUMINT':
				case 'SMALLINT':
				case 'TINYINT':
				case 'INT':
					$this->getInteger();
					break;
				case 'DECIMAL':
				case 'DOUBLE':
				case 'FLOAT':
					$this->getDecimal();
					break;
				case 'YEAR':
				case 'TIME':
				case 'TIMESTAMP':
				case 'DATETIME':
				case 'DATE':
					$this->getDateTime();
					break;
				case 'VARCHAR':
				case 'CHAR':
					$this->getString();
					break;
				case 'LONGTEXT':
				case 'MEDIUMTEXT':
				case 'TEXT':
				case 'TINYTEXT':
					$this->getText();
					break;
				case 'ENUM':
					$this->getEnum();
					break;
				case 'VARBINARY':
				case 'BINARY':
					$this->getBinary();
					break;
				case 'LONGBLOB':
				case 'MEDIUMBLOB':
				case 'BLOB':
				case 'TINYBLOB':
					$this->getBlob();
					break;
				default :
					throw new Exception("Column type not found");
			}
		}
		
		/**
		 * @throws Exception
		 */
		public function getInteger()
		{
			//var_dump($this->options);
			//check array contains limit
			if ( array_key_exists('limit', $this->options) )
			{
				$this->setLimit();
			}
			//check array contains signed value
			$this->setSign();
			//check array contains null value
			$this->setNull();
			//check array contains default value
			$this->setDefault();
			//check array contains zerofill value
			if ( array_key_exists('zerofill', $this->options) )
			{
				$zerofill = $this->options['zerofill'];
				if ( $zerofill )
				{
					$this->colSql .= " ZEROFILL";
				}
			}
			
			//check array contains autoincre value
			if ( array_key_exists('autoincre', $this->options) )
			{
				$autoincre = $this->options['autoincre'];
				if ( $autoincre )
				{
					$this->colSql .= " AUTO_INCREMENT";
				}
			}
			//check array contains primary value
			$this->setPrimaryKey();
			$this->setComment();
		}
		
		/**
		 * @throws Exception
		 */
		private function setLimit()
		{
			$limit = $this->options['limit'];
			//check limit size for various data type
			switch ( $this->type )
			{
				case 'INT':
					if ( $limit > 11 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 11");
					}
					break;
				case 'TINYINT':
					if ( $limit > 4 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 4");
					}
					break;
				case 'SMALLINT':
					if ( $limit > 5 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 5");
					}
					break;
				case 'MEDIUMINT':
					if ( $limit > 9 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 9");
					}
					break;
				case 'BIGINT':
					if ( $limit > 20 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 20");
					}
					break;
				case 'BINARY':
				case 'CHAR':
					if ( $limit > 255 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 255");
					}
					break;
				case 'VARBINARY':
				case 'VARCHAR':
					if ( $limit > 65535 )
					{
						throw new Exception("Maximum Limit for {$this->type} is 65,535");
					}
					break;
				default:
					throw new Exception("Uknown limit option", 1);
			}
			
			$this->colSql .= "({$limit})";
			
		}
		
		/**
		 *
		 */
		private function setSign()
		{
			if ( array_key_exists('signed', $this->options) )
			{
				$signed = $this->options['signed'];
				if ( !$signed )
				{
					$this->colSql .= " UNSIGNED";
				}
				else
				{
					$this->colSql .= " SIGNED";
				}
			}
		}
		
		/**
		 *
		 */
		private function setNull()
		{
			if ( array_key_exists('null', $this->options) )
			{
				$nullable = $this->options['null'];
				if ( !$nullable )
				{
					$this->colSql .= " NULL";
				}
				else
				{
					$this->colSql .= " NOT NULL";
				}
			}
		}
		
		/**
		 *
		 */
		private function setDefault()
		{
			
			if ( array_key_exists('default', $this->options) )
			{
				$default = $this->options['default'];
				$this->colSql .= " DEFAULT {$default}";
			}
		}
		
		/**
		 *
		 */
		private function setPrimaryKey()
		{
			if ( array_key_exists('primary', $this->options) )
			{
				$primary = $this->options['primary'];
				if ( $primary )
				{
					$this->colSql .= " PRIMARY KEY";
				}
			}
		}
		
		/**
		 * [setComment description]
		 */
		private function setComment()
		{
			if ( array_key_exists('comment', $this->options) )
			{
				$comment = $this->options['comment'];
				if ( $comment )
				{
					$this->colSql .= " COMMENT '{$comment}'";
				}
			}
		}
		
		/**
		 * @throws Exception
		 */
		private function getDecimal()
		{
			$this->colSql = "{$this->name} {$this->type}";
			$this->setDecimal();
			$this->setSign();
			$this->setNull();
			$this->setDefault();
			$this->setPrimaryKey();
			$this->setComment();
		}
		
		/**
		 * @throws Exception
		 */
		private function setDecimal()
		{
			//check limit option exists
			if ( array_key_exists('limit', $this->options) )
			{
				if ( $this->options['limit'] > 24 and $this->type == 'FLOAT' )
				{
					throw new Exception('Maximum limit can not more than 24 for {$this->type}');
				}
				if ( ( $this->options['limit'] > 54 || $this->options['limit'] < 25 ) and $this->type == 'DOUBLE' )
				{
					throw new Exception('Maximum range for {$this->type} 24-53');
				}
				
				if ( $this->options['limit'] > 65 and $this->type == 'DECIMAL' )
				{
					throw new Exception('Maximum limit can not more than 65 for {$this->type}');
				}
				//check scale
				if ( $this->options['scale'] > 24 and $this->type == 'FLOAT' )
				{
					throw new Exception('Maximum scale for {$this->type} is 24');
				}
				if ( $this->options['scale'] > 14 and $this->type == 'DOUBLE' )
				{
					throw new Exception('Maximum scale for {$this->type} is 14');
				}
				if ( $this->options['scale'] > 30 and $this->type == 'DECIMAL' )
				{
					throw new Exception('Maximum scale for {$this->type} is 30');
				}
				$this->colSql .= " ({$this->options['limit']},{$this->options['scale']})";
			}
			else
			{
				throw new Exception('Precision scale is missing');
			}
		}
		
		/**
		 * @throws Exception
		 */
		private function getDateTime()
		{
			if ( $this->type === 'YEAR' && array_key_exists('limit', $this->options) )
			{
				$limit = $this->options['limit'];
				if ( $limit != 2 || $limit != 4 )
				{
					throw new Exception("Year Limit can be 2 or 4", 1);
				}
				$this->colSql .= " ({$limit})";
			}
			$this->setNull();
			$this->setDefault();
			//enable auto update for timestamp
			if ( array_key_exists('update', $this->options) )
			{
				$this->colSql .= " ON UPDATE CURRENT_TIMESTAMP";
			}
			//set comment
			$this->setComment();
		}
		
		/**
		 * @throws Exception
		 */
		private function getString()
		{
			$this->setLimit();
			$this->setNull();
			$this->setDefault();
			$this->setComment();
		}
		
		/**
		 *
		 */
		private function getText()
		{
			$this->setNull();
			$this->setDefault();
			$this->setComment();
		}
		
		/**
		 *
		 */
		private function getEnum()
		{
			if ( $this->type == 'ENUM' && array_key_exists('value', $this->options) )
			{
				$value = $this->options['value'];
				$this->colSql .= " ({$value})";
			}
			$this->setDefault();
			$this->setComment();
		}
		
		/**
		 * @throws Exception
		 */
		private function getBinary()
		{
			$this->setLimit();
			$this->setNull();
			$this->setDefault();
			$this->setComment();
		}
		
		/**
		 *
		 */
		private function getBlob()
		{
			$this->setNull();
			$this->setDefault();
			$this->setComment();
		}
		
		/**
		 * @return bool|null
		 */
		public function getColumn()
		{
			if ( !is_null($this->colSql) )
			{
				return $this->colSql;
			}
			
			return false;
		}
		
		/**
		 *
		 */
		private function setAfter()
		{
			if ( array_key_exists('after', $this->options) )
			{
				$after = $this->options['after'];
				if ( $after )
				{
					$this->colSql .= " AFTER `{$after}`";
				}
			}
		}
		
		/**
		 *
		 */
		private function setBefore()
		{
			if ( array_key_exists('before', $this->options) )
			{
				$before = $this->options['before'];
				if ( $before )
				{
					$this->colSql .= " FIRST `{$before}`";
				}
			}
		}
		
	}


