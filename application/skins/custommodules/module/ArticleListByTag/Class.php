<?php


/**
 * ArticleListByTag - shortCode.
 * Creates a list of all articles with the given tags, using the defined template.
 * 
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2010, Mereo GmbH
 * @version 1.1.0
 * 
 */

class Skin_Module_ArticleListByTag_Class extends Aitsu_Module_Abstract {

    protected $_isVolatile = true;

    protected function _main() {

		$output = '';
		if ($this->_get('ArticleListByTag_' . $this->_index, $output)) {
			$data = unserialize($output);
			return $data->view->render($data->template . '.phtml');
		}

        $this->_params->listTemplate = Aitsu_Content_Config_Radio :: set($this->_index, 'template', '', $this->_getTemplates(), 'Template');
		$this->_params->numItems = Aitsu_Content_Config_Text :: set($this->_index, 'numItems', Aitsu_Translate::_("Anzahl Artikel"), Aitsu_Translate::_('Artikelliste nach Tags'));
        $this->_params->showPeriod = Aitsu_Content_Config_Select :: set($this->_index, 'period', Aitsu_Translate::_("Zeitfenster"), array(Aitsu_Translate::_("Zeitfenster ignorieren")=>'ignore', Aitsu_Translate::_("jetzt gueltige Artikel")=>'now', Aitsu_Translate::_("vergangene Artikel")=>'past', Aitsu_Translate::_("aktuelle und kommende Artikel")=>'upcoming'), Aitsu_Translate::_('Artikelliste nach Tags'));

		$this->_params->sortBy = Aitsu_Content_Config_Select :: set($this->_index, 'sortBy', Aitsu_Translate::_("Sortierung nach"), array(Aitsu_Translate::_("Publikationsdatum")=>'pubfrom', Aitsu_Translate::_("Seitentitel")=>'pagetitle'), Aitsu_Translate::_("Artikelliste nach Tags"));
        $this->_params->sortOrder = Aitsu_Content_Config_Select :: set($this->_index, 'sortOrder', Aitsu_Translate::_("Sortierreihenfolge"), array(Aitsu_Translate::_("aufsteigend")=>'asc', Aitsu_Translate::_("absteigend")=>'desc'), Aitsu_Translate::_("Artikelliste nach Tags"));

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

		if (isset($this->_params->numItems) && !empty($this->_params->numItems)) {
			$this->_params->limit = $this->_params->numItems;
		} elseif (!isset($this->_params->limit)){
            $this->_params->limit = 999;
        }

		if (!isset($this->_params->offset)) {
			$this->_params->offset = 0;
		}

		if (isset($this->_params->sortBy) && !empty($this->_params->sortBy)) {
			$this->_params->sortby = $this->_params->sortBy;
		} elseif (!isset($this->_params->sortby)){
            $this->_params->sortby = 'pubfrom';
        }

		if (isset($this->_params->sortOrder) && !empty($this->_params->sortOrder)) {
			$this->_params->order = $this->_params->sortOrder;
		} elseif (!isset($this->_params->order)){
            $this->_params->order = 'asc';
        }

		if (isset($this->_params->showPeriod) && !empty($this->_params->showPeriod)) {
			$this->_params->period = $this->_params->showPeriod;
		} elseif (!isset($this->_params->period)){
            $this->_params->period = 'now';
        }

        $period = '';
		$now = date("Y-m-d H:i:s");
        if ($this->_params->period == 'now') {
            $period = "	and (artlang.pubfrom IS NULL or artlang.pubfrom <= :now )
				and (artlang.pubuntil IS NULL or artlang.pubuntil >= :now)";
        } elseif($this->_params->period == 'past') {
            $period = "	and (artlang.pubfrom IS NULL or artlang.pubfrom < :now )
				and (artlang.pubuntil IS NULL or artlang.pubuntil < :now)";
        } elseif ($this->_params->period == 'upcoming') {
            $period = " and artlang.pubfrom >= :now ";
        } elseif($this->_params->period == 'ignore') {
	        $period = ' and :now = :now ';
        }

		if (!is_array($tags)) {
			foreach($this->_params->tag as $tag) {
				$tags[] = $tag;
			}
		}

		$sql = "select straight_join distinct
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
				if(artlang.redirect = 1, artlang.redirect_url, null) AS redirect_url,
				media.mediaid as mediaid,
				media.extension as mediaextension,
				mediadesc.name as medianame,
				mediadesc.subline,
				mediadesc.description,
				tblsetlink.textvalue as setlink
			from _art_lang as artlang
			left join _cat_art as catart ON artlang.idart = catart.idart
			left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = artlang.idlang
			left join _tag_art as tagart on artlang.idart = tagart.idart
			left join _tag as tag on tagart.tagid = tag.tagid
			left join _media as media on media.idart = artlang.idart and media.filename = artlang.mainimage
			left join _media_description as mediadesc on mediadesc.mediaid = media.mediaid and mediadesc.idlang = artlang.idlang

			left join _aitsu_property as propsetlink on propsetlink.identifier = 'ModuleConfig_SetLink:link'
			left join _aitsu_article_property as tblsetlink on tblsetlink.idartlang = artlang.idartlang and propsetlink.propertyid = tblsetlink.propertyid

			where
				artlang.online = 1
				and artlang.idlang = :idlang
				".$period."
				and tag.tag in ('".implode("','", $tags)."')
		    group by
		        artlang.idartlang
			order by";

		if($this->_params->period != 'ignore' || $this->_params->sortby == 'pubfrom') {
			$sql .= " pubfrom ".$this->_params->order.",";
		}
		$sql .=" pagetitle ".$this->_params->order."
		    limit ".$this->_params->offset.", ".$this->_params->limit;

		$result = Aitsu_Db :: fetchAll($sql, array (
			':idlang' => Aitsu_Registry :: get()->env->idlang,
			':now' => $now
		));

        $view = $this->_getView();
		foreach ($result as $row) {
            $items[] = (object)$row;
		}

        $view->items = $items;

		$output = $view->render($this->_params->template . '.phtml');

		if (Aitsu_Application_Status :: isEdit() ) {
			$output = '// '.Aitsu_Translate::_("Liste konfigurieren").' //'.$output;
		}

		$this->_save(serialize((object) array (
			'view' => $view,
			'template' => $this->_params->template
		)), 'eternal');

		return $output;
	}
}