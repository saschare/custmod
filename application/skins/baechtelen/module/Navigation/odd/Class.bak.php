<?php


/**
 * Navigation Top.
 *
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 *
 * {@id $Id: Class.php 17663 2010-07-21 13:30:22Z akm $}
 */

class Skin_Module_Navigation_Class extends Aitsu_Ee_Module_Abstract {

	public static function init($context) {

		Aitsu_Content_Edit :: noEdit('Navigation', true);

		$index = empty ($context['index']) ? 'noindex' : $context['index'];
		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$instance = new self();

		if (!($output = $instance->_cache('Navigation'))) {

			$view = $instance->_getView();

			if (isset($params->idcat)) {
				$rootIdcat = $params->idcat;				
			} else {
				$rootIdcat = 1;				
			}
			$view->nav = Aitsu_Core_Categories :: getNavigationTree($rootIdcat, 4, array('background-color', 'text-color', 'directSub'));

			$output = $view->render('index.phtml');
			$instance->_cache('Navigation', $output, 60 * 60 * 24 * 30);
		}

		return $output;
	}
}
?>