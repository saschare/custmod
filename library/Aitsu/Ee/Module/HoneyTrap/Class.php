<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

/**
 * @deprecated 2.1.0 - 29.01.2011
 */
class Aitsu_Ee_Module_HoneyTrap_Class extends Aitsu_Ee_Module_Abstract {

	public static function init($context) {

		$instance = new self();
		Aitsu_Content_Edit :: noEdit('HoneyTrap', true);

		if (!isset (Aitsu_Registry :: get()->config->honeytrap->keyword)) {
			return '';
		}

		$honeyTraps = array_flip(Aitsu_Registry :: get()->config->honeytrap->keyword->toArray());
		if (!empty ($_POST)) {
			if (count(array_intersect_key($honeyTraps, $_GET)) > 0) {
				$ht = Aitsu_Persistence_Honeytrap :: factory();
				$ht->ip = $_SERVER["REMOTE_ADDR"];
				$ht->save();
				Aitsu_Ee_Cache_Page :: lifetime(0);
			}
		}

		$view = $instance->_getView();
		$view->keyword = array_rand($honeyTraps);
		$view->showForm = count(array_intersect_key($honeyTraps, $_GET)) > 0;

		$templates = array (
			'a',
			'b',
			'c',
			'd',
			'e'
		);
		shuffle($templates);

		return $view->render($templates[0] . '.phtml');
	}
}