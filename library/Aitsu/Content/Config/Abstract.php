<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 */

abstract class Aitsu_Content_Config_Abstract {
	
	protected $facts;
	protected $params;

	protected function __construct($index, $name, $idartlang = null, $suppressRegistration = false) {
		
		if (strlen($name) > 127) {
			throw new Exception('The name may consist of not more than 127 characters.');
		}
		
		$this->facts['index'] = $index;
		$this->facts['name'] = str_replace('.', '_', $name);
		
		$this->facts['type'] = 'text';
		
		$this->facts['idartlang'] = $idartlang == null ? Aitsu_Registry :: get()->env->idartlang : $idartlang;
		
		if (!$suppressRegistration) {
			Aitsu_Content_Edit :: registerConfig($this);
		}
	}
	
	public function __set($key, $value) {
		
		$this->facts[$key] = $value;
	}
	
	public function __get($key) {
		
		if (!isset($this->facts[$key]) && !isset($this->params[$key])) {
			return null;
		}
		
		if (isset($this->params[$key])) {
			return $this->params[$key];
		}
		
		return $this->facts[$key];
	}
	
	public function __isset($key) {
		
		return isset($this->facts[$key]) || isset($this->params[$key]);
	}
	
	abstract public function getTemplate();
	
	public function currentValue() {

		if (Aitsu_Core_Article_Property :: factory($this->facts['idartlang'])->getValue('ModuleConfig_' . $this->facts['index'], $this->facts['name']) == null) {
			return null;
		}
		
		$value = Aitsu_Core_Article_Property :: factory($this->facts['idartlang'])->getValue('ModuleConfig_' . $this->facts['index'], $this->facts['name'])->value;
	
		if ($this->facts['type'] == 'date' && $value == '0000-00-00 00:00:00') {
			$value = '';
		} 
	
		return $value;
	}
}