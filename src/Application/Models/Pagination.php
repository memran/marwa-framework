<?php
	
	namespace Marwa\Application\Models;
	
	use Marwa\Application\Exceptions\InvalidArgumentException;
	use Marwa\Application\Utils\SmartObject;
	use Nette\Utils\Paginator;
	
	
	class Pagination {
		
		use SmartObject;
		
		/**
		 * @var int
		 */
		protected $LIMIT_PER_PAGE = 25;
		/**
		 * @var mixed|string
		 */
		protected $path;
		/**
		 * @var int
		 */
		protected $total_items;
		/**
		 * @var mixed|Paginator
		 */
		protected $paginator;
		
		protected $PAGINATION_DATA = [
			'total' => '',
			'per_page' => '',
			'last_page' => '',
			"first_page_url" => '',
			"last_page_url" => '',
			"next_page_url" => '',
			"prev_page_url" => null,
			"path" => '',
			"from" => '',
			"to" => '',
			"data" => ''
		];
		/**
		 * @var mixed
		 */
		protected $data;
		/**
		 * @var \MarwaDB\QueryBuilder
		 */
		protected $parent;
		/**
		 * @var bool
		 */
		private $build_pagination_links = false;
		
		/**
		 * Pagination constructor.
		 * @param int $limit_per_page
		 * @param $total_items
		 * @param $parent
		 */
		public function __construct( int $limit_per_page, $total_items, $parent )
		{
			$this->LIMIT_PER_PAGE = $limit_per_page;
			$this->parent = $parent;
			$this->total_items = $total_items;
			$this->createPaginator();
		}
		
		/**
		 *
		 */
		protected function createPaginator()
		{
			$this->paginator = new Paginator;
			$this->paginator->setItemCount($this->getTotalItems());
			$this->paginator->setItemsPerPage($this->getPerPage());
			$this->paginator->setPage($this->getCurrentPage());
			$this->setDatabaseResult();
		}
		
		/**
		 * @return int
		 */
		public function getTotalItems()
		{
			return $this->total_items;
		}
		
		/**
		 * @return int
		 */
		public function getPerPage()
		{
			return $this->LIMIT_PER_PAGE;
		}
		
		/**
		 * @return int
		 */
		public function getCurrentPage()
		{
			if ( isset($_GET['page']) )
			{
				return (int) $_GET['page'];
			}
			
			return 1;
			
		}
		
		/**
		 * @param $data
		 */
		public function setDatabaseResult()
		{
			$this->data = $this->parent->limit($this->getPerPage())->offset($this->paginator->getOffset())->get();
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		public function links()
		{
			return $this->createSimpleLinks(false);
		}
		
		/**
		 * @param bool $simple
		 * @return string
		 * @throws InvalidArgumentException
		 */
		public function createSimpleLinks( $simple = true )
		{
			$linkPrev = '
				<nav aria-label="Page navigation">
			  <ul class="pagination justify-content-center">
			    <li class="page-item ' . $this->getFirstPageClass() . '">
			        <a class="page-link" href="' . $this->getPrevPageUrl() . '">Previous</a>
			        </li>';
			
			$linkNext = '
			    <li class="page-item ' . $this->getLastPageClass() . '">
			     <a class="page-link" href="' . $this->getNextPageUrl() . '">Next</a>
			    </li>
			  </ul>
			</nav>';
			if ( $simple )
			{
				return $linkPrev . $linkNext;
			}
			else
			{
				return $linkPrev . $this->createLinks() . $linkNext;
				
			}
		}
		
		/**
		 * @return string
		 */
		protected function getFirstPageClass()
		{
			if ( $this->paginator->isFirst() )
			{
				return 'disabled';
			}
			
			return '';
		}
		
		/**
		 * @return string|null
		 * @throws InvalidArgumentException
		 */
		protected function getPrevPageUrl()
		{
			
			if ( $this->paginator->isFirst() )
			{
				return null;
			}
			
			return $this->getPath() . "?page=" . $this->getPrevPage();
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getPath()
		{
			if ( isset($this->path) )
			{
				return base_url() . $this->path;
			}
			
			return base_url() . current_url();
		}
		
		/**
		 * @return int
		 */
		protected function getPrevPage()
		{
			
			return $this->getCurrentPage() - 1;
		}
		
		/**
		 * @return string
		 */
		protected function getLastPageClass()
		{
			if ( $this->paginator->isLast() )
			{
				return 'disabled';
			}
			
			return '';
		}
		
		/**
		 * @return string|null
		 * @throws InvalidArgumentException
		 */
		protected function getNextPageUrl()
		{
			if ( $this->paginator->isLast() )
			{
				return null;
			}
			
			return $this->getPath() . "?page=" . $this->getNextPage();
		}
		
		/**
		 * @return int
		 */
		protected function getNextPage()
		{
			return $this->getCurrentPage() + 1;
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		public function createLinks()
		{
			// initialise the list of links
			$links = [];
			$PAGE_LIMIT = 5;
			
			if ( $this->getCurrentPage() < $PAGE_LIMIT )
			{
				$START_PAGE = 1;
				$MAX_PAGE = $PAGE_LIMIT;
			}
			else
			{
				if ( $this->getCurrentPage() >= $PAGE_LIMIT )
				{
					$START_PAGE = $this->getCurrentPage() - round($PAGE_LIMIT / 2);
					$MAX_PAGE = $this->getCurrentPage() + round($PAGE_LIMIT / 2);
				}
				
			}
			
			if ( $this->paginator->getPageCount() < $MAX_PAGE )
			{
				$MAX_PAGE = $this->paginator->getPageCount();
			}
			
			for ( $i = $START_PAGE; $i <= $MAX_PAGE; $i++ )
			{
				$links[] = '<li class="page-item ' . $this->getActiveClass($i) . '"><a class="page-link" href="' . $this->getPageLink($i) . '">' . $i . '</a></li>';
			}
			
			// return the array of links
			return implode('', $links);
		}
		
		/**
		 * @param $page
		 * @return string
		 */
		protected function getActiveClass( $page )
		{
			if ( $this->getCurrentPage() == $page )
			{
				return 'active';
			}
			else
			{
				return '';
			}
		}
		
		/**
		 * @param $page
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getPageLink( $page )
		{
			return $this->getPath() . '?page=' . $page;
		}
		
		/**
		 * @param string $path
		 */
		public function withPath( string $path )
		{
			$this->path = $path;
		}
		
		/**
		 * @return int
		 */
		public function itemsInPage()
		{
			return $this->paginator->getItemsPerPage();
		}
		
		/**
		 * @return mixed
		 */
		public function data()
		{
			return $this->getDatabaseResult();
		}
		
		/**
		 * @return mixed
		 */
		protected function getDatabaseResult()
		{
			return $this->data;
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		public function getSimpleLinks()
		{
			return $this->createSimpleLinks();
		}
		
		/**
		 * @return false|string
		 */
		public function __toString()
		{
			return $this->toJson();
		}
		
		/**
		 * @return false|string
		 */
		public function toJson()
		{
			return json_encode($this->getLinks());
		}
		
		/**
		 * @return array
		 */
		protected function getLinks()
		{
			if ( !$this->build_pagination_links )
			{
				$this->setLinks();
			}
			
			return $this->PAGINATION_DATA;
		}
		
		/**
		 * @throws InvalidArgumentException
		 */
		protected function setLinks()
		{
			$this->PAGINATION_DATA['total'] = $this->getTotalItems();
			$this->PAGINATION_DATA['per_page'] = $this->getPerPage();
			$this->PAGINATION_DATA['current_page'] = $this->getCurrentPage();
			$this->PAGINATION_DATA['last_page'] = $this->paginator->getLastPage();
			$this->PAGINATION_DATA['first_page_url'] = $this->getFirstPageUrl();
			$this->PAGINATION_DATA['last_page_url'] = $this->getLastPageUrl();
			$this->PAGINATION_DATA['next_page_url'] = $this->getNextPageUrl();
			$this->PAGINATION_DATA['prev_page_url'] = $this->getPrevPageUrl();
			$this->PAGINATION_DATA['path'] = $this->getPath();
			$this->PAGINATION_DATA['from'] = $this->paginator->getOffset();
			$this->PAGINATION_DATA['to'] = $this->getTo();
			$this->PAGINATION_DATA['total_page'] = $this->paginator->getPageCount();
			$this->PAGINATION_DATA['data'] = $this->getDatabaseResult();
			
			$this->build_pagination_links = true;
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getFirstPageUrl()
		{
			return $this->getPath() . "?page=" . $this->paginator->getFirstPage();
		}
		
		/**
		 * @return string
		 * @throws InvalidArgumentException
		 */
		protected function getLastPageUrl()
		{
			return $this->getPath() . "?page=" . $this->paginator->getLastPage();
		}
		
		/**
		 * @return int
		 */
		protected function getTo()
		{
			return ( $this->paginator->getOffset() + $this->getPerPage() );
		}
		
		/**
		 * @return string
		 */
		public function displayText()
		{
			$from = $this->getPerPage()*$this->getCurrentPage();
			$to = $from+$this->getPerPage();
			return "Displaying $from to $to of ".$this->getTotalItems();
		}
	}
