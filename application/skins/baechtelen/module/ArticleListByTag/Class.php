<?php


/**
 * ArticleListByTag - shortCode.
 * 
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2010, Mereo GmbH
 * 
 */

class Skin_Module_ArticleListByTag_Class extends Aitsu_Module_Abstract {
    protected $_isVolatile = true;

    protected $_paramsArray = array();
    
	protected function _main() {

        $output = '';

        if ($this->_get('ArticleListByTag_' . $this->_index, $output)) {
            $data = unserialize($output);
            return $data->view->render($data->template . '.phtml');
        }

        $this->_params->listTemplate = Aitsu_Content_Config_Radio :: set($this->_index, 'template', '', $this->_getTemplates(), Aitsu_Translate::_('Template'));
		$this->_params->limit = Aitsu_Content_Config_Text :: set($this->_index, 'numItems', Aitsu_Translate::_("Anzahl Artikel"), Aitsu_Translate::_('Artikelliste nach Tags'));
        $this->_params->period = Aitsu_Content_Config_Select :: set($this->_index, 'period', Aitsu_Translate::_("Zeitfenster"), array(Aitsu_Translate::_("jetzt gueltige Artikel")=>'now', Aitsu_Translate::_("vergangene Artikel")=>'past'), Aitsu_Translate::_('Artikelliste nach Tags'));

        $tag = Aitsu_Db::fetchAll("select * from _tag order by tag", array());
		foreach($tag as $key=>$value) {
			$tagsDb[$value['tag']] = $value['tag'];
		}
        $tags = Aitsu_Content_Config_Checkbox :: set($this->_index, 'tags', Aitsu_Translate::_("Tag w&auml;hlen"), $tagsDb, Aitsu_Translate::_('Artikelliste nach Tags'));

        if (isset($this->_params->listTemplate) && !empty($this->_params->listTemplate)) {
            $this->_params->template = $this->_params->listTemplate;
        } elseif (!isset($this->_params->template)){
            $this->_params->template = 'index';
        }

		if (!isset($this->_params->limit) OR $this->_params->limit == 0) {
			$this->_params->limit = 999;
		}

		if (!isset($this->_params->offset)) {
			$this->_params->offset = 0;
		}

        if(!isset($this->_params->period)) {
            $this->_params->period = 'now';
        }

        $period = '';

        if ($this->_params->period == 'now') {
            $period = "	and (artlang.pubfrom IS NULL or artlang.pubfrom <= ? )
				and (artlang.pubuntil IS NULL or artlang.pubuntil >= ?)";
        } elseif($this->_params->period == 'past') {
            $period = "	and (artlang.pubfrom IS NULL or artlang.pubfrom < ? )
				and (artlang.pubuntil IS NULL or artlang.pubuntil < ?)";
        }

        $this->_paramsArray = array (
			Aitsu_Registry :: get()->env->idlang,
			Aitsu_Registry :: get()->env->idlang);
        if($this->_params->period == 'now' || $this->_params->period == 'past'){
            array_push($this->_paramsArray, date("Y-m-d H:i:s"),
			date("Y-m-d H:i:s"));
        } 

		if (!is_array($tags)) {
			foreach($this->_params->tag as $tag) {
				$tags[] = $tag;
			}
		}

		$result = Aitsu_Db :: fetchAll("select straight_join distinct
                    artlang.idart as idart,
                    artlang.idartlang as idartlang,
                    artlang.title as articletitle,
                    artlang.pagetitle as pagetitle,
                    artlang.summary as summary,
                    artlang.teasertitle as teasertitle,
                    artlang.created as created,
                    unix_timestamp(artlang.created) as ts_created,
                    artlang.lastmodified as modified,
                    artlang.artsort as artsort,
                    artlang.pubfrom as pubfrom,
                    artlang.pubuntil as pubuntil,
                    artlang.mainimage as image,
                    media.mediaid as mediaid,
                    media.extension as mediaextension
                from _art_lang as artlang
                left join _cat_art as catart ON artlang.idart = catart.idart
                left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = ?
                left join _tag_art as tagart on artlang.idart = tagart.idart
                left join _tag as tag on tagart.tagid = tag.tagid
                left join _media as media ON media.idart = artlang.idart AND media.filename = artlang.mainimage
                where
                    artlang.online = 1
                    and artlang.idlang = ?
                    ".$period."
                    and tag.tag in ('".implode("','", $tags)."')
                order by
                    tagart.val asc,
                    pubfrom desc,
                    created desc limit ".$this->_params->offset.", ".$this->_params->limit, $this->_paramsArray
		);

		foreach ($result as $row) {
			$articles[] = (object)$row;
		}

		$view = $this->_getView();
		$view->items = $articles;

        $this->_save(serialize((object)array (
            'view' => $view,
            'template' => $this->_params->template
        )), 60 * 15);

		$output = $view->render($this->_params->template.'.phtml');

		if (Aitsu_Application_Status :: isEdit() ) {
			$output = '// '.Aitsu_Translate::_("Liste konfigurieren").' //'.$output;
		}
		return $output;
	}
}