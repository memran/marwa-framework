<?php
	namespace Marwa\Application\Authentication\Interfaces;

	interface PolicyInterface {
		public function viewAny($user,$policy):bool;
		public function view($user, $policy):bool;
		public function create($user,$policy):bool;
		public function update($user,$policy):bool;
		public function delete($user,$policy):bool;
	}

