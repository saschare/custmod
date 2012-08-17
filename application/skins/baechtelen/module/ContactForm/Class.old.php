<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright © 2010, w3concepts AG
 */

class Skin_Module_ContactForm_Class extends Aitsu_Ee_Module_Abstract {

	public static function init($context) {

		$index = $context['index'];
		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$redirectTo = Aitsu_Content_Config_Link :: set($index, 'redirectTo', Aitsu_Translate::_("Weiterleitung nach Empfang"), 'Redirect');
		$senderMail = Aitsu_Content_Config_Text :: set($index, 'senderMail', Aitsu_Translate::_("E-Mail"), 'Sender');
		$senderName = Aitsu_Content_Config_Text :: set($index, 'senderName', Aitsu_Translate::_("Name"), 'Sender');
		$receipientMail = Aitsu_Content_Config_Text :: set($index, 'receipientMail', Aitsu_Translate::_("E-Mail"), 'Receipient');
		$receipientName = Aitsu_Content_Config_Text :: set($index, 'receipientName', Aitsu_Translate::_("Name"), 'Receipient');
		$subject = Aitsu_Content_Config_Text :: set($index, 'subject', Aitsu_Translate::_("Betreff"), 'Subject');

		$fields = (array) $params->field;

		if (!Aitsu_Application_Status::isEdit()) {
            $cf = Aitsu_Form_Validation :: factory('contactForm');

            foreach ($fields as $name => $attr) {
                $validation = 'NoTags';
                if (isset ($attr->validation)) {
                    $validation = $attr->validation;
                }
                $maxlength = isset ($attr->maxlength) ? $attr->maxlength : 255;
                if ($name == 'Message') {
                    $maxlength = 4000;
                }
                $cf->setValidator($name, $validation, array (
                    'maxlength' => $maxlength
                ), isset ($attr->required) && $attr->required == 1);
            }

            // check if redirect is an article or category
            $redirect = explode(' ', $redirectTo);
            if (preg_match("|idart|", $redirect[0])) {
                $url = Aitsu_Db::fetchOne("select
                      concat_ws('/', catlang.url, artlang.urlname) as url
                    from
                      _art_lang as artlang
                      left join _cat_art as catart on catart.idart = artlang.idart
                      left join _cat_lang as catlang on catart.idcat = catlang.idcat
                    where
                      artlang.idart = ?
                      and artlang.idlang = ?
                      and catlang.idlang = artlang.idlang
                    ", array(
                        $redirect[1],
                        Aitsu_Registry::get()->env->idlang
                    ));
                $redirectUrl = '/'.$url.'.html';
            } else {
                $url = Aitsu_Db::fetchOne("select
                      catlang.url
                    from
                      _cat_lang as catlang
                    where
                      catlang.idcat = ?
                      and catlang.idlang = ?
                    ", array(
                        $redirect[1],
                        Aitsu_Registry::get()->env->idlang
                    ));
                $redirectUrl = '/'.$url;
            }
            $cf->process(Aitsu_Form_Processor_Email :: factory($redirectUrl, array (
                  'sendermail' => $senderMail,
                  'sendername' => $senderName,
                  'recepientmail' => $receipientMail,
                  'recepientname' => $receipientName,
                  'subject' => $subject
            )));
        }

		$code = '';
		if (Aitsu_Registry :: isEdit()) {
			$parameters = str_replace("\r\n", "\n", $context['params']);
			$code = '<code class="aitsu_params" style="display:none;">' . $parameters . '</code>';
		}

		$instance = new self();
		$view = $instance->_getView();

		$view->action = Aitsu_Util :: getCurrentUrl();
		$view->field = $params->field;

		return $code . $view->render($params->template . '.phtml');
	}
}