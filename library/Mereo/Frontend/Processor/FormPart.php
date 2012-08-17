<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2010, w3concepts AG
 * @copyright Copyright &copy; 2010, Mereo GmbH
 * 
 */

class Mereo_Frontend_Processor_FormPart implements Aitsu_Form_Processor_Interface {

	protected function __construct() {
	}

	public static function factory() {
		return new self();
	}

	public function process() {
		return true;
	}
}