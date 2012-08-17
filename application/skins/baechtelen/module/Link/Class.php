<?php


/**
 * ArticleAuthor - shortCode.
 * 
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2010, Mereo GmbH
 * 
 */

class Skin_Module_Link_Class extends Aitsu_Ee_Module_Abstract {

	public static function about() {

		return (object) array (
			'name' => 'link to an internal or external page',
			'description' => Zend_Registry :: get('Zend_Translate')->translate('lets the user select an internal or enter an external link'),
			'type' => 'Content',
			'author' => (object) array (
				'name' => 'Conrad Leu',
				'copyright' => 'Mereo GmbH'
			),
			'version' => '1.0.0',
			'status' => 'stable',
			'url' => null,
			'id' => '4d62f432-ebe8-45fe-b41f-693653f3381f'
		);
	}

	public static function init($context) {

		$instance = new self();

		Aitsu_Content_Edit :: isBlock(false);

		$output = '';
		if ($instance->_get('Link_' . $context['index'], $output)) {
			return $output;
		}

		$index = empty ($context['index']) ? 'noindex' : $context['index'];
		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$linkText = Aitsu_Content_Config_Text :: set($index, 'text', Aitsu_Translate::_("Link-Text"), Aitsu_Translate::_("allgemeine Angaben zum Link"));

		$externalLink = Aitsu_Content_Config_Text :: set($index, 'externalLink', Aitsu_Translate::_("externer Link"), Aitsu_Translate::_("externer Link"));
		$target = Aitsu_Content_Config_Select :: set($index, 'target', Aitsu_Translate::_("Zielfenster"), array(Aitsu_Translate::_("gleiches Fenster")=>'_self',  Aitsu_Translate::_("neues Fenster")=>'_blank'), Aitsu_Translate::_("externer Link"));

		$internalLink = Aitsu_Content_Config_Link :: set($index, 'internalLink', Aitsu_Translate::_("interner Link"), Aitsu_Translate::_("interner Link"));

        $link = 'javascript:void(0);';
        if (!empty($externalLink)) {
            $protocol = '';
            if (!preg_match("|http://|", $externalLink)){
                $protocol = 'http://';
            }
            $link = $protocol.$externalLink;
        }
        if (!empty($internalLink)) {
            $link = '{ref:'.str_replace(' ', '-', $internalLink).'}';
        }

		if (!isset($params->template)) {
			$params->template = 'index';
		}
		if (empty($linkText)) {
            $linkText = $context['index'];
			//$linkText = Aitsu_Translate::_("Link bearbeiten");
		}

		$view = $instance->_getView();

        if (Aitsu_Application_Status::isEdit()) {
            $linkText = $linkText.' // '.Aitsu_Translate::_("Link bearbeiten").' //';
        }

        $view->text = $linkText;
        $view->href = $link;
        $view->target = $target;

        $view->navTree = $internalLink;

		$output = $view->render($params->template . '.phtml');

		$instance->_save($output, 'eternal');

		return $output;
	}
}