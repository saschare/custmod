<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * 
 * {@id $Id: Class.php 18287 2010-08-23 13:11:54Z akm $}
 *
 * deprecated - use media instead
 */

class Skin_Module_Image_Class extends Aitsu_Module_Abstract {

    protected $_isBlock = true;

    protected function _main() {

		$output = '';
        
		if ($this->_get('Image_' . $this->_index, $output)) {
			$data = unserialize($output);
			return $data->view->render($data->template . '.phtml');
		}

        $view = $this->_getView();

		$template = Aitsu_Content_Config_Radio :: set($this->_index, 'ImageTemplate', '', $this->_getTemplates(), Aitsu_Translate::_("Template"));

		$imgWidth = Aitsu_Content_Config_Text :: set($this->_index, 'width', Aitsu_Translate::_("Breite"), Aitsu_Translate::_("Bildgr&ouml;sse"));
		$imgHeight = Aitsu_Content_Config_Text :: set($this->_index, 'height', Aitsu_Translate::_("H&ouml;he"), Aitsu_Translate::_("Bildgr&ouml;sse"));
		$layout = Aitsu_Content_Config_Radio :: set($this->_index, 'layout', Aitsu_Translate::_("Bild-Position"), array(Aitsu_Translate::_("Bild links")=>'left', Aitsu_Translate::_("Bild zentriert")=>'center', Aitsu_Translate::_("Bild rechts")=>'right'), Aitsu_Translate::_("Layout"));
		$images = Aitsu_Content_Config_Media :: set($this->_index, 'Image', Aitsu_Translate::_("Datei(en) w&auml;hlen"));
        $view->images = Aitsu_Persistence_View_Media :: byFileName(Aitsu_Registry :: get()->env->idart, $images);

		$externalLink = Aitsu_Content_Config_Text :: set($this->_index, 'externalLink', Aitsu_Translate::_("externer Link"), Aitsu_Translate::_("externer Link"));
		$target = Aitsu_Content_Config_Select :: set($this->_index, 'target', Aitsu_Translate::_("Zielfenster"), array(Aitsu_Translate::_("gleiches Fenster")=>'_self',  Aitsu_Translate::_("neues Fenster")=>'_blank'), Aitsu_Translate::_("externer Link"));
        $internalLink = Aitsu_Content_Config_Link :: set($this->_index, 'internalLink', Aitsu_Translate::_("interner Link"), Aitsu_Translate::_("Seite ausw&auml;hlen"));

        if (empty($template)) {
            if (isset($this->_params->template)) {
                $template = $this->_params->template;
            } else {
                $template = 'index';
            }
        }

        if (!empty($target)) {
            $this->params->target = $target;
        }

        $link = false;
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
        
        if (!empty($imgWidth)) {
            $this->_params->width = $imgWidth;
        }
        if (!empty($imgHeight)) {
            $this->_params->height = $imgHeight;
        }

		if (!isset($this->_params->height) && isset($this->_params->width)) {
			$this->_params->height = $this->_params->width;
		} elseif (!isset($this->_params->width) && isset($this->_params->height)) {
			$this->_params->width = $this->_params->height;
		}

        if (!empty($layout)) {
            $this->_params->layout = $layout;
        } elseif (!isset($this->_params->layout)) {
            $this->_params->layout = 'block';
        }

		$view->imgWidth = $this->_params->width;
		$view->imgHeight = $this->_params->height;
		$view->layout = $this->_params->layout;

        $view->href = $link;
        $view->target = $this->_params->target;

		$this->_save(serialize((object) array (
			'view' => $view,
			'template' => $template
		)), 60 * 60 * 24 * 365);

		if (count($view->images) == 0) {
			if (Aitsu_Application_Status::isEdit()) {
				$output = '// Image '.$this->_index.' '.Aitsu_Translate::_("bearbeiten").' //';
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
			$this->_save(serialize((object) array (
				'view' => $view,
				'template' => $template
			)), 60 * 60 * 24 * 365);
		}

		return $output;
	}
}