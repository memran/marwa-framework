<?php
	
	namespace Marwa\Application\Utils;
	
	use Doctrine\Common\Collections\ArrayCollection;
	use Doctrine\Common\Collections\Criteria;
	use Doctrine\Common\Collections\Expr\Comparison;
	use Exception;
	
	class Collection extends ArrayCollection {
		
		protected $criteria;
		
		/**
		 * @param string $key
		 * @param mixed $value
		 * @param string $op
		 * @return Collection
		 */
		public function where( $key, $value, $op = '=' )
		{
			$expr = new Comparison($key, $op, $value);
			$this->getCriteria();
			$this->criteria->where($expr);
			
			return $this;
		}
		
		/**
		 * @return Criteria
		 */
		protected function getCriteria()
		{
			if ( !isset($this->criteria) )
			{
				$this->criteria = new Criteria();
			}
			
			return $this->criteria;
		}
		
		/**
		 * @param $key
		 * @param $value
		 * @param string $op
		 * @return $this
		 */
		public function andWhere( $key, $value, $op = '=' )
		{
			$this->getCriteria();
			$expr = new Comparison($key, $op, $value);
			$this->criteria->andWhere($expr);
			
			return $this;
		}
		
		/**
		 * @param $key
		 * @param $value
		 * @param string $op
		 * @return $this
		 */
		public function orWhere( $key, $value, $op = '=' )
		{
			$this->getCriteria();
			$expr = new Comparison($key, $op, $value);
			$this->criteria->orWhere($expr);
			
			return $this;
		}
		
		/**
		 * @return Collection
		 */
		public function fetch()
		{
			return $this->matching($this->criteria);
		}
		
		/**
		 * @param $key
		 * @param string $sort
		 * @return $this
		 */
		public function orderBy( string $key, $sort = 'ASC' )
		{
			$this->getCriteria();
			$this->criteria->orderBy([$key => strtoupper($sort)]);
			
			return $this;
		}
		
		/**
		 * @return mixed
		 */
		public function first()
		{
			$this->getCriteria();
			$this->criteria->setFirstResult(1);
			
			return $this->criteria->getFirstResult();
		}
		
		/**
		 * @param int $limit
		 * @return mixed
		 */
		public function limit( int $limit )
		{
			$this->getCriteria();
			$this->criteria->setMaxResults($limit);
			
			return $this->criteria->getMaxResults();
		}
		
		/**
		 * @param string $key
		 * @return array
		 * @throws Exception
		 */
		public function pluck( string $key )
		{
			$data = $this->toArray();
			if ( empty($data) )
			{
				throw new Exception("Collection is empty");
			}
			if ( is_multi($data) )
			{
				return array_column($data, $key);
			}
			else
			{
				return [$data[ $key ]];
			}
			
		}
	}
