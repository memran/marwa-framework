<?php

	namespace Marwa\Application\Utils;
	
	use Exception;
	use Nette\Utils\Paginator;
	
	class Pagination extends Paginator {
		
		use SmartObject;
		
		/**
		 * @var string
		 */
		var $pageStr = '';
		
		/**
		 * @var string
		 */
		var $path = '';
		
		/**
		 * @var integer
		 */
		var $pageLimit = 3;
		
		/**
		 * @param string $url
		 * @throws Exception
		 */
		public function setPath( string $url )
		{
			if ( Validate::isUri($url) )
			{
				$this->path = $url;
			}
			else
			{
				throw new Exception('Invalid Path');
			}
		}
		
		/**
		 * @param int $pagelimit
		 */
		public function setMaxDisplayPage( $pagelimit = 5 )
		{
			$this->pageLimit = $pagelimit - 1;
		}
		
		/**
		 * @return string
		 */
		public function createLinks()
		{
			//get starting base
			$start_page = $this->getBase();
			//get total number of pages
			$total_page = $this->getPageCount();
			
			//get current page
			$page = $this->getPage();
			//calculate to page at initial
			$to_page = $start_page + $this->pageLimit - 1;
			
			//if current page is greater then default page limit then change start_page value
			if ( $page >= $this->pageLimit )
			{
				$start_page = $page - floor($this->pageLimit / 2);
				$to_page = $page + floor($this->pageLimit / 2);
				//if to page is equal or greater than total page
				if ( $to_page >= $total_page )
				{
					$to_page = $total_page;
				}
			}
			
			//if current page is not first page then show previouse
			if ( !$this->isFirst() )
			{
				$firstPage = $this->getFirstPage();
				$path = $this->path . '?page=' . $firstPage;
				$this->pageStr .= '<li class="page-item"><a class="page-link" href="' . $path . '">First</a></li>';
				
				$path = $this->path . '?page=' . ( $page - 1 );
				$this->pageStr .= '<li class="page-item"><a class="page-link" href="' . $path . '">Previous</a></li>';
			}
			//building pagination page
			for ( $i = $start_page; $i <= $to_page; $i++ )
			{
				$path = $this->path . '?page=' . $i;
				if ( $i == $page )
				{
					$activeclass = 'active';
				}
				else
				{
					$activeclass = '';
				}
				
				$this->pageStr .= '<li class="page-item ' . $activeclass . '">
			<a class="page-link" href="' . $path . '">' . $i . '</a></li>';
			}
			//if current page is not last then show next navigation
			if ( !$this->isLast() )
			{
				$path = $this->path . '?page=' . ( $page + 1 );
				$this->pageStr .= '<li class="page-item"><a class="page-link" href="' . $path . '">Next</a></li>';
				//decideing last page
				$lastPage = $this->getLastPage();
				$path = $this->path . '?page=' . $lastPage;
				$this->pageStr .= '<li class="page-item"><a class="page-link" href="' . $path . '">Last</a></li>';
			}
			
			return $this->pageStr;
		}
		
		/**
		 * @return string
		 */
		public function simpleLinks()
		{
			//get total number of pages
			$total_page = $this->getPageCount();
			//get current page
			$page = $this->getPage();
			
			if ( !$this->isFirst() )
			{
				$path = $this->path . '?page=' . ( $page - 1 );
				$this->pageStr = '<li class="page-item"><a class="page-link" href="' . $path . '">Previous</a></li>';
			}
			//if current page is not last then show next navigation
			if ( !$this->isLast() )
			{
				$path = $this->path . '?page=' . ( $page + 1 );
				$this->pageStr .= '<li class="page-item"><a class="page-link" href="' . $path . '">Next</a></li>';
			}
			
			return $this->pageStr;
		}
		
		/**
		 * @return string
		 */
		public function links()
		{
			return $this->pageStr;
		}
		
		/**
		 * @return array
		 */
		public function pageInArray()
		{
			return [
				'page' => $this->getPage(),
				'totalPages' => $this->getPageCount(),
				'per_page' => $this->getItemsPerPage(),
				'totalResults' => $this->getItemCount()
			];
		}
		
		/**
		 * @return string
		 */
		public function __toString()
		{
			return $this->pageStr;
		}
		
	}
