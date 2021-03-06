<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

/**
 * @deprecated 2.1.0 - 29.01.2011
 */
class Aitsu_Ee_Module_Navigation_Class extends Aitsu_Ee_Module_Abstract {

	protected $type = 'navigation';

	public static function init($context) {

		Aitsu_Content_Edit :: noEdit('Navigation', true);
		$instance = new self();

		$index = empty ($context['index']) ? 'noindex' : $context['index'];
		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$output = '';
		if ($instance->_get('Navigation_' . preg_replace('/[^a-zA-Z_0-9]/', '', $index), $output)) {
			return $output;
		}

		$view = $instance->_getView();
		$view->nav = Aitsu_Persistence_View_Category :: nav($params->idcat);

		$template = isset ($params->template) ? $params->template : 'index';
		$output = $view->render($template . '.phtml');

		$instance->_save($output, 'eternal');

		return $output;
	}

}