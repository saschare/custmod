<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * 
 */

class Skin_Module_Navigation_Class extends Aitsu_Ee_Module_Abstract {

	protected $type = 'navigation';

	public static function about() {

		return (object) array (
			'name' => 'Navigation',
			'description' => Aitsu_Translate :: translate('Returns a navigation menu based on the specified template.'),
			'type' => 'Navigation',
			'author' => (object) array (
				'name' => 'Andreas Kummer',
				'copyright' => 'w3concepts AG'
			),
			'version' => '1.0.0',
			'status' => 'stable',
			'url' => null,
			'id' => '4cdc126d-aef8-484f-b085-1c237f000101'
		);
	}

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

        Aitsu_Html_Helper_Navigation_Ul::get();

		$template = isset ($params->template) ? $params->template : 'index';
		$output = $view->render($template . '.phtml');

		$instance->_save($output, 'eternal');

		return $output;
	}

}