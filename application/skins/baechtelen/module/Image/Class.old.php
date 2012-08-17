<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * 
 * {@id $Id: Class.php 18287 2010-08-23 13:11:54Z akm $}
 */

class Skin_Module_Image_Class extends Aitsu_Ee_Module_Abstract {

	public static function about() {

		return (object) array (
			'name' => 'Image',
			'description' => Zend_Registry :: get('Zend_Translate')->translate('Standard image module. It returns the specified image(s) rendered with the specified template.'),
			'type' => array (
				'Content',
				'Image'
			),
			'author' => (object) array (
				'name' => 'Andreas Kummer',
				'copyright' => 'w3concepts AG'
			),
			'version' => '1.0.0',
			'status' => 'stable',
			'url' => null,
			'id' => 'a072536d-c565-11df-851a-0800200c9a66'
		);
	}

	public static function init($context) {

		$instance = new self();

		$index = $context['index'];

		Aitsu_Content_Edit :: isBlock(true);

		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$output = '';
		if ($instance->_get('Image_' . $context['index'], $output)) {
			$data = unserialize($output);
			return $data->view->render($data->template . '.phtml');
		}

		$template = Aitsu_Content_Config_Radio :: set($index, 'ImageTemplate', '', $instance->_getTemplates(), 'Template');

		if (empty($template)) {
			if (isset($params->template)) {
				$template = $params->template;
			} else {
				$template = 'index';
			}
		}

		$view = $instance->_getView();

		$imgWidth = Aitsu_Content_Config_Text :: set($index, 'width', Aitsu_Translate::_("Breite"), Aitsu_Translate::_("Bildgr&ouml;sse"));
		$imgHeight = Aitsu_Content_Config_Text :: set($index, 'height', Aitsu_Translate::_("H&ouml;he"), Aitsu_Translate::_("Bildgr&ouml;sse"));
		$layout = Aitsu_Content_Config_Radio :: set($index, 'layout', Aitsu_Translate::_("Bild-Position"), array(Aitsu_Translate::_("Bild links")=>'left', Aitsu_Translate::_("Bild zentriert")=>'center', Aitsu_Translate::_("Bild rechts")=>'right'), Aitsu_Translate::_("Layout"));
		$images = Aitsu_Content_Config_Media :: set($index, 'Image', 'Choose image');
        $view->images = Aitsu_Persistence_View_Media :: byFileName(Aitsu_Registry :: get()->env->idart, $images);

		$externalLink = Aitsu_Content_Config_Text :: set($index, 'externalLink', Aitsu_Translate::_("externer Link"), Aitsu_Translate::_("externer Link"));
		$target = Aitsu_Content_Config_Select :: set($index, 'target', Aitsu_Translate::_("Zielfenster"), array(Aitsu_Translate::_("gleiches Fenster")=>'_self',  Aitsu_Translate::_("neues Fenster")=>'_blank'), Aitsu_Translate::_("externer Link"));
		$internalLink = Aitsu_Content_Config_Select :: set($index, 'internalLink', Aitsu_Translate::_("interner Link"), Mereo_Util_Navigation::getTrees(), Aitsu_Translate::_("interner Link"));

        $link = false;
        if (!empty($externalLink)) {
            $protocol = '';
            if (!preg_match("|http://|", $externalLink)){
                $protocol = 'http://';
            }
            $link = $protocol.$externalLink;
        }
        if (!empty($internalLink)) {
            $link = '{ref:'.$internalLink.'}';
        }
        
        if (!empty($imgWidth)) {
            $params->width = $imgWidth;
        }
        if (!empty($imgHeight)) {
            $params->height = $imgHeight;
        }
        if (!empty($layout)) {
            $params->layout = $layout;
        }

		if (!isset($params->height) && isset($params->width)) {
			$params->height = $params->width;
		}
		if (!isset($params->width) && isset($params->height)) {
			$params->width = $params->height;
		}
		if (!isset($params->layout)) {
			$params->layout = false;
		}

		$view->imgWidth = $params->width;
		$view->imgHeight = $params->height;
		$view->layout = $params->layout;

        $view->href = $link;
        $view->target = $target;

		$instance->_save(serialize((object) array (
			'view' => $view,
			'template' => $template
		)), 'eternal');

		if (count($view->images) == 0) {
			if (Aitsu_Application_Status::isEdit()) {
				$output = '// Image '.$index.' '.Aitsu_Translate::_("bearbeiten").' //';
			} else {
				$output = '';
			}
		} else {
			if (Aitsu_Registry::isEdit()) {
                $output = '<div style="padding: 0.5em 0;">';
            }
            
			$output .= $view->render($template . '.phtml');

			if (Aitsu_Registry::isEdit()) {  
                $output .= '</div>';
            }
			$instance->_save(serialize((object) array (
				'view' => $view,
				'template' => $template
			)), 'eternal');
		}

		return $output;
	}
}