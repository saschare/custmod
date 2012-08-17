<?php

/**
 * reformats links as clickable hrefs in output
 *
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 * register transformaton in application/configs/listeners.ini in phase:
 * "Dispatch and transformation phase (frontend only)" as follows:
 * frontend.dispatch.listener.Mereo_Frontend_Transformation_LuceneStopContent = true
 *
 */
class Mereo_Frontend_Transformation_CreateMailToLink implements Aitsu_Event_Listener_Interface {

	public static function notify(Aitsu_Event_Abstract $event) {

		if (!isset($event->bootstrap->pageContent)) {
			return;
		}

        //@todo: noch realisieren -> nicht ersetzen, wenn bereits verlinkt!!!
		//$event->bootstrap->pageContent = preg_replace("/(\\S+@\\S+\\.\\w+)/", "<a href=\"mailto:$1\">$1</a>", $event->bootstrap->pageContent);

        //@todo: make one correct regex without the necessity of stripping double transformed mail-links
        // transform all email-addresses to clickable mailto-links
        $content = preg_replace("#(\\S+@\\S+\\.\\w+)#", "<a href=\"mailto:$1\">$1</a>", $event->bootstrap->pageContent);
        // strip double masked mailto-links (in case of already formatted and re-transformed mail-links)
        $event->bootstrap->pageContent = preg_replace("#(<a )(<a href=\"mailto:)(href=\"mailto:)(.*?)(\">href=\"mailto:.*?)(\">.*?<\/a>)#i", "$1$3$4$6", $content);
	}
}
?>