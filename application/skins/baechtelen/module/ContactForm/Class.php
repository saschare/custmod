<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright © 2010, w3concepts AG
 */

class Skin_Module_ContactForm_Class extends Aitsu_Module_Abstract {

    protected function _main() {

        $view = $this->_getView();

        $redirectTo = Aitsu_Content_Config_Link :: set($this->_index, 'idcat', Aitsu_Translate::_("Danke-Seite w&auml;hlen"), Aitsu_Translate::_("Weiterleitung"));
		$senderMail = Aitsu_Content_Config_Text :: set($this->_index, 'senderMail', 'Email', 'Sender');
		$senderName = Aitsu_Content_Config_Text :: set($this->_index, 'senderName', 'Name', 'Sender');
		$receipientMail = Aitsu_Content_Config_Text :: set($this->_index, 'receipientMail', 'Email', 'Receipient');
		$receipientName = Aitsu_Content_Config_Text :: set($this->_index, 'receipientName', 'Name', 'Receipient');
		$subject = Aitsu_Content_Config_Text :: set($this->_index, 'subject', '', 'Subject');

        if (!Aitsu_Registry::isEdit()) {

            $fields = (array) $this->_params->field;

            $cf = Aitsu_Form_Validation :: factory('contactForm');

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

            /**
             * we do this double-processing to be able to validate without effectively sending the form and getting redirected
             */
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

            $view->action = Aitsu_Util :: getCurrentUrl();
            $view->field = $this->_params->field;

            return $view->render($this->_params->template . '.phtml');
        }
        return;
	}
}