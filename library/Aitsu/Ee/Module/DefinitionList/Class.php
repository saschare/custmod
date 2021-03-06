<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright © 2010, w3concepts AG
 */

class Aitsu_Ee_Module_DefinitionList_Class extends Aitsu_Ee_Module_Abstract {

	public static function init($context) {

		$index = $context['index'];

		$list = Aitsu_Content_Text :: get('DefinitionList_' . $index, 0);

		$elements = array ();

		if (Aitsu_Registry :: isEdit() && empty ($list)) {
			$elements['DefinitionList'] = 'No entries have been made yet.';
		} else {
			if (preg_match_all('/^([^\\:]*)\\:\\s(.*)/m', $list, $matches) == 0) {
				return '';
			}
			for ($i = 0; $i < count($matches[0]); $i++) {
				$elements[$matches[1][$i]] = $matches[2][$i];
			}
		}

		$instance = new self();
		$view = $instance->_getView();
		$view->elements = $elements;

		return $view->render('index.phtml');
	}
}