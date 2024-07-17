<?php
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */

namespace Marwa\Application;

use Exception;
use Marwa\Application\Validation\V;

abstract class AppController
{

	/**
	 * @var V
	 */
	protected $validator;

	/**
	 * [$_validate_message description]
	 *
	 * @var array
	 */
	protected $_validate_message = [];

	protected $_validation;

	/**
	 * @param $id
	 * @param $alias_name
	 */
	public function alias($id, $alias_name)
	{
		$this->getValidator();
		$this->validator->setAlias($id, $alias_name);


	}

	function getValidator(): V
	{
		if ($this->validator == null) {
			$this->validator = new V();
		}
		return $this->validator;
	}

	/**
	 * @return bool
	 * @throws Exception
	 */
	public function validate()
	{

		$this->getValidator();

		/**
		 * If there are no validation array then validate the request
		 */
		if (empty($this->getFieldValidation())) {
			return true;
		}

		if (!empty($this->_validate_message) && is_array($this->_validate_message)) {
			$this->setValidationMessage($this->_validate_message);
		}

		$this->_validation = $this->validator->make($_POST + $_FILES, $this->getFieldValidation());
		$this->_validation->validate();
		if ($this->_validation->fails()) {
			setMessage('error', 'errors', $this->_validation->errors()->firstOfAll());
			return false;
		}

		return true;

	}

	/**
	 * @return array
	 */
	public function getFieldValidation(): array
	{

		return [];
	}

	/**
	 * [setValidationMessage description]
	 *
	 * @param array $msg [description]
	 */
	public function setValidationMessage(array $msg)
	{
		$this->validator->setMessages($msg);
	}

	/**
	 * return validation errors
	 * */
	public function getValidationErrors()
	{
		return $this->_validation->errors()->firstOfAll();
	}

	/**
	 * @param string $tplFileName
	 * @param array $data
	 * @return mixed
	 * @throws Exceptions\FileNotFoundException
	 */
	public function render(string $tplFileName, array $data = [])
	{
		return view($tplFileName, $data);
	}
	/**
	 * alias of valdiation error returns
	 * */
	public function errors(){
		return $this->getValidationErrors();
	}

}
