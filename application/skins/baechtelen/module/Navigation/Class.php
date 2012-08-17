<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 * @modified by Conrad Leu, 2012 (replaced Aitsu_Persistence_View_Category by Mereo... because of the showInvisible-Flag)
 */

class Skin_Module_Navigation_Class extends Aitsu_Module_Tree_Abstract {

	protected $type = 'navigation';
	protected $_allowEdit = false;
	protected $_user = null;

	protected function _main() {

		$template = isset ($this->_params->template) ? $this->_params->template : 'index';

		/*
		 * if $this->_params->idcat is not numeric, we have to assume 
		 * that the string represents a configuration value available.
		 */
		if (!is_numeric($this->_params->idcat)) {
			$this->_params->idcat = Aitsu_Config :: get($this->_params->idcat);
		}

		$view = $this->_getView();
		$view->nav = Mereo_Persistence_View_Category :: nav2($this->_params->idcat);

		$output = $view->render($template . '.phtml');

		return $output;
	}

	protected function _cachingPeriod() {
		
		$user = Aitsu_Adm_User :: getInstance();

		if ($user != null) {
			return 0;
		}

		return 60 * 60 * 24 * 365;
	}
}