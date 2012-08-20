<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright © 2010, w3concepts AG
 */

class Skin_Module_ContactForm_Class extends Aitsu_Module_Abstract {

    protected function _main() {

        $redirectTo = Aitsu_Content_Config_Link :: set($this->_index, 'idcat', Aitsu_Translate::_("Danke-Seite w&auml;hlen"), Aitsu_Translate::_("Weiterleitung"));
		$senderMail = Aitsu_Content_Config_Text :: set($this->_index, 'senderMail', Aitsu_Translate::_("E-Mail"), Aitsu_Translate::_("Absender"));
		$senderName = Aitsu_Content_Config_Text :: set($this->_index, 'senderName', Aitsu_Translate::_("Name"), Aitsu_Translate::_("Absender"));
		$receipientMail = Aitsu_Content_Config_Text :: set($this->_index, 'receipientMail', Aitsu_Translate::_("E-Mail"), Aitsu_Translate::_("Empf&auml;nger"));
		$receipientName = Aitsu_Content_Config_Text :: set($this->_index, 'receipientName', Aitsu_Translate::_("Name"), Aitsu_Translate::_("Empf&auml;nger"));
		$subject = Aitsu_Content_Config_Text :: set($this->_index, 'subject', Aitsu_Translate::_("Betreff"), Aitsu_Translate::_("Betreff"));

        if (!Aitsu_Registry::isEdit() && Aitsu_Registry::isFront()) {

            $cf = Aitsu_Form_Validation :: factory('contactForm');

            $fields = (array) $this->_params->field;
            foreach ($fields as $name => $attr) {
                $validation = 'NoTags';
                if (isset ($attr->validation)) {
                    $validation = $attr->validation;
                }
                $maxlength = isset ($attr->maxlength) ? $attr->maxlength : 255;
                if ($name == 'message') {
                    $maxlength = 4000;
                }
                $cf->setValidator($name, $validation, array (
                    'maxlength' => $maxlength
                ), isset ($attr->required) && $attr->required == 1);
            }

            $processor = Mereo_Frontend_Processor_FormPart :: factory();
            $formIsValid = $cf->process($processor);

            if($formIsValid){

                if (!empty($redirectTo)) {
                    $this->_params->redirectTo = Mereo_Frontend_Util_Navigation::getUrlFromLink($redirectTo);
                } elseif (empty($this->_params->redirectTo)) {
                    $this->_params->redirectTo = Mereo_Frontend_Util_Navigation::getUrlFromLink(Aitsu_Registry::get()->env->idart);
                }

                $cf->process(Aitsu_Form_Processor_Email :: factory($this->_params->redirectTo, array (
                    'sendermail' => $senderMail,
                    'sendername' => $senderName,
                    'recepientmail' => $receipientMail,
                    'recepientname' => $receipientName,
                    'subject' => $subject
                )));
            }
        }

        $view = $this->_getView();

        $view->action = Aitsu_Util :: getCurrentUrl();
        $view->field = $this->_params->field;

        return $view->render($this->_params->template . '.phtml');
	}
}