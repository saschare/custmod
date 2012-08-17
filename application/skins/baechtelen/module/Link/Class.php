<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 * @modified Conrad Leu, Mereo GmbH
 * @lastModification added media-selection to link to
 * @version 1.0.2
 */
class Skin_Module_Link_Class extends Aitsu_Module_Abstract {

    protected $_isBlock = false;

    protected function _main() {

        $view = $this->_getView();

        $view->name = Aitsu_Content_Config_Text :: set($this->_index, 'name', Aitsu_Translate::_("Name"), Aitsu_Translate::_("Link"));
        $view->link = Aitsu_Content_Config_Link :: set($this->_index, 'link', Aitsu_Translate::_("Link"), Aitsu_Translate::_("Link"));
        $view->title = Aitsu_Content_Config_Text :: set($this->_index, 'title', Aitsu_Translate::_("Link-Titel"), Aitsu_Translate::_("Link"));

        // set media to link to
        $media2link = Aitsu_Content_Config_Media :: set($this->_index, 'media2link', Aitsu_Translate::_("Medium f&uuml;r Verlinkung w&auml;hlen (optional)"));
        $media2link = Aitsu_Persistence_View_Media :: byFileName(Aitsu_Registry :: get()->env->idart, $media2link);

        $targets = array (
            '_blank' => '_blank',
            '_top' => '_top',
            '_self' => '_self',
            '_parent' => '_parent'
        );
        $view->target = Aitsu_Content_Config_Select :: set($this->_index, 'target', 'Target', $targets, 'Link');

        $template = Aitsu_Content_Config_Radio :: set($this->_index, 'MediaTemplate', '', $this->_getTemplates(), Aitsu_Translate::_("Template (Art der Darstellung/Auflistung)"));
        if (empty($template)) {
            if (isset($this->_params->template)) {
                $template = $this->_params->template;
            } else {
                $template = 'index';
            }
        }

        // set wheter it's a link to a category or article
        if (strpos($view->link, 'idcat') !== false || strpos($view->link, 'idart') !== false) {
            $view->link = str_replace(' ', '-', $view->link);
            $view->link = '{ref:' . $view->link . '}';
        }
        // or set link to media
        if (!empty($media2link)) {
            $view->link = '/file/download/' . $media2link[0]->idart . '/' . $media2link[0]->filename;
        }
        // check if link is an email
        if (Aitsu_Util_Validator::isEmail($view->link)) {
            $view->link = 'mailto:'.$view->link;
        }

        if (preg_match("|www.|", $view->link) && !preg_match("#(http://|https://)#", $view->link)) {
            $view->link = 'http://'.$view->link;
        }

        return $view->render($template . '.phtml');
    }

    protected function _cachingPeriod() {
        return 60 * 60 * 24 * 365;
    }
}