<?php

/**
 * aitsu Lucene search implementation.
 * 
 * @version 1.0.0
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * 
 * {@id $Id: output.php 15331 2010-03-15 10:17:24Z akm $}
 */

class Skin_Module_LuceneSearch_Class extends Aitsu_Ee_Module_Abstract {

	public static function init($context) {

		Aitsu_Content_Edit :: noEdit('LuceneSearch', true);

		$instance = new self();

		$view = $instance->_getView();

		try {
			$view->results = Aitsu_Lucene_Index :: find($_REQUEST['searchterm'], array(1,29,130));
		} catch (Exception $e) {
			$view->results = array();
		}
		$output = $view->render('index.phtml');
		
		Aitsu_Ee_Cache_Page :: lifetime(0);

		return $output;
	}
}
?>