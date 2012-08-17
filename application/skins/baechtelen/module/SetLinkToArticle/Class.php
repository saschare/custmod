<?php


/**
 * SetLinkToArticle - shortCode.
 * description: If article has the tag(s) from this module-config set, the user
 * can configure this module and set a link-flag. This flag can be checked in aggregations to decide
 * whether a link to this article should be set or not.
 *
 * @param tag set a tag in the module-parameters (if more than one write tag.1 = tagName etc.)
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 * 
 */

class Skin_Module_SetLinkToArticle_Class extends Aitsu_Module_Abstract {

	protected function _main() {

		$output = '';
		if ($this->_get('SetLinkToArticle', $output)) {
			return $output;
		}

		$view = $this->_getView();

        $view->link = Aitsu_Content_Config_Checkbox :: set($this->_index, 'link', Aitsu_Translate::_("auf diesen Artikel verlinken"), array(Aitsu_Translate::_("ja")=>1), Aitsu_Translate::_("Link setzen"));

		Aitsu_Db::fetchAll("select tag from _tag", array());

		$this->_params->tag = (array)$this->_params->tag;

        $result = Aitsu_Db::fetchAll("select
                tag.tagid
            from
                _tag_art as tagart
            left join _tag as tag on tagart.tagid = tag.tagid
            where
                tagart.idart = ?
                and tag.tag in('".implode("','", $this->_params->tag)."')
            order by tag.tagid", array(
				Aitsu_Registry::get()->env->idart
			));

		if (count($result) > 0) {
			$showConfig = true;
		}

		if (Aitsu_Application_Status :: isEdit() && $showConfig) {
			$output = '<p class="config">'.Aitsu_Translate::_("Wollen Sie diesen Artikel in Artikellisten verlinken?").' ';
            if (isset($view->link)) {
                $output .= '<strong>'.Aitsu_Translate::_("Ja, Link gesetzt.").'</strong>';
            }
            $output .= '</p>';
		}

		return $output;
	}

    protected function _cachingPeriod() {
        return 60*60*24*365;
    }
}