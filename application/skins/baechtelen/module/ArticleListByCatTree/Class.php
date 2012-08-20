<?php
/**
 * article-list by category, with recursive listing of child-categories (configurable option)
 *
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 */

class Skin_Module_ArticleListByCatTree_Class extends Aitsu_Module_Abstract {

    protected $_isVolatile = true;

    protected function _init() {
        // uncomment this line if you're using a user-configured template
//        $template = Aitsu_Content_Config_Radio :: set($this->_index, 'template', '', $this->_getTemplates(), 'Template');

        $template = 'index';
        if(!empty($this->_params->template)) {
            $template = $this->_params->template;
        }
        Aitsu_Util_Javascript::add($this->_getView()->render($template.'.js'));
    }

    protected function _main() {

		$this->_params->template = Aitsu_Content_Config_Radio :: set($this->_index, 'template', '', $this->_getTemplates(), 'Template');

		$sourceCategories = Aitsu_Content_Config_Text :: set($this->_index, 'sourceCategories', Aitsu_Translate::_("Kategorien eingeben (Komma getrennt)"), Aitsu_Translate::_("Quell-Kategorie/n"));
		$idcat = Aitsu_Content_Config_Link :: set($this->_index, 'idcat', Aitsu_Translate::_("..oder Kategorie w&auml;hlen"), Aitsu_Translate::_("Quell-Kategorie/n"));
		$listChildCats = Aitsu_Content_Config_Checkbox::set($this->_index, 'listChildCats', Aitsu_Translate::_("Kind-Kategorien"), array(Aitsu_Translate::_("einbeziehen (max. 1 Eltern-Kategorie)")=>1) , Aitsu_Translate::_("Quell-Kategorie/n"));
        $listIndexArticles = Aitsu_Content_Config_Checkbox::set($this->_index, 'listIndexArticles', Aitsu_Translate::_("Index-Artikel"), array(Aitsu_Translate::_("Index-Artikel (Startartikel) anzeigen")=>1) , Aitsu_Translate::_("Quell-Kategorie/n"));

		$sortBy = Aitsu_Content_Config_Select :: set($this->_index, 'sortBy', Aitsu_Translate::_("Sortierung nach"), array(Aitsu_Translate::_("Publikationsdatum von (Standard)")=>'pubfrom', Aitsu_Translate::_("Publikationsdatum bis")=>'pubuntil', Aitsu_Translate::_("Kategoriereihenfolge")=>'lft'), Aitsu_Translate::_("Listenkonfiguration"));
		$sortOrder = Aitsu_Content_Config_Select :: set($this->_index, 'sortOrder', Aitsu_Translate::_("Sortierreihenfolge"), array(Aitsu_Translate::_("aufsteigend (Standard)")=>'asc', Aitsu_Translate::_("absteigend")=>'desc'), Aitsu_Translate::_("Listenkonfiguration"));
		$numArticles = Aitsu_Content_Config_Text :: set($this->_index, 'numArticles', Aitsu_Translate::_("Anzahl Artikel"), Aitsu_Translate::_("Listenkonfiguration"));
		$period = Aitsu_Content_Config_Select::set($this->_index, 'period', Aitsu_Translate::_("Zeitfenster"), array(Aitsu_Translate::_("laufende und kommende Artikel (Standard)")=>'upcoming', Aitsu_Translate::_("vergangene Artikel")=>'past', Aitsu_Translate::_("Zeitfenster ignorieren")=>'ignore'), Aitsu_Translate::_("Listenkonfiguration"));

		if (preg_match("|idcat|", $idcat)) {
            $idcat = str_replace('-', ' ', ($idcat));

			$idcat = explode(' ', $idcat);
			$this->_params->idcat = $idcat[1];
		} elseif (preg_match("|[0-9]|", $sourceCategories)) {
            if (isset($this->_params->recursive) || $listChildCats[1] == 1) {
                $idcats = explode(',', $sourceCategories);
			    $sourceCategories = $idcats[0];
            }
            $this->_params->idcat = $sourceCategories;
        } elseif (empty($this->_params->idcat)) {
            $this->_params->idcat = Aitsu_Registry::get()->env->idcat;
        }

		if (empty($this->_params->template)) {
			$this->_params->template = 'index';
		}

        if (isset($numArticles)){
            $this->_params->limit = $numArticles;
        }

		if (!isset($this->_params->limit) OR $this->_params->limit == 0) {
			$this->_params->limit = 999;
		}

		if (!isset($this->_params->offset)) {
			$this->_params->offset = 0;
		}

		if (isset($sortOrder)) {
			$this->_params->order = $sortOrder;
		} elseif (!isset($this->_params->order)){
            $this->_params->order = 'asc';
        }

		if (isset($sortBy)) {
			$this->_params->sortby = $sortBy;
		} elseif (!isset($this->_params->sortby)){
            $this->_params->sortby = 'pubfrom';
        }

        if (isset($this->_params->recursive) || $listChildCats[1] == 1) {

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
                    media.mediaid as mediaid,
                    media.extension as mediaextension,
                    artlang.redirect_url as redirect_url
                from _cat as parent
                inner join _cat as child
                left join _cat_art as catart ON catart.idcat = child.idcat
                left join _art_lang as artlang ON catart.idart = artlang.idart
                left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = artlang.idlang
                left join _media as media ON media.idart = artlang.idart AND media.filename = artlang.mainimage
                where
                    parent.idcat = ".$this->_params->idcat. "
                    and child.lft
                    between parent.lft
                    and parent.rgt
                    and artlang.online = 1
                    and artlang.idlang = ? ";
            if (isset($this->_params->showIndexArticles) || $listIndexArticles[1] == 1) {
                $sql .= " and catlang.startidartlang != artlang.idartlang ";
            }
            $sql .= " and artlang.idartlang != ".Aitsu_Registry::get()->env->idartlang;
        } else {

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
                    artlang.redirect_url as redirect_url
                from _art_lang as artlang
                left join _cat_art as catart ON artlang.idart = catart.idart
                left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = artlang.idlang
                where
                    artlang.online = 1
                    and artlang.idlang = ? ";
            if (isset($this->_params->showIndexArticles) || $listIndexArticles[1] == 1) {
                $sql .= " and catlang.startidartlang != artlang.idartlang ";
            }
            $sql .= " and catart.idcat in (".$this->_params->idcat.') ';
        }

        if(isset($period)) {
	        $this->_params->period = $period;
        } elseif (!isset($this->_params->period)) {
            $this->_params->period = 'upcoming';
		}

		$now = date("Y-m-d H:i:s");
        if ($this->_params->period == 'upcoming') {
            $period = " and pubuntil >= '".$now."'";
        } elseif($this->_params->period == 'past') {
            $period = " and pubuntil < '".$now."'";
        } else {
	        $period = " and '".$now."' = '".$now."' ";
        }

        $sql .= $period;

        $sql .= " order by ";
        if ($this->_params->sortby == 'pubfrom' || $this->_params->sortby == 'pubuntil') {
			$sql .= " ".$this->_params->sortby." ".$this->_params->order.",";
		} elseif ($this->_params->sortby == 'lft') {
			$sql .= " child.lft asc,";
        } elseif($this->_params->period != 'ignore') {
			$sql .= " pubfrom ".$this->_params->order.",";
        }
		$sql .=" pagetitle ".$this->_params->order."
			limit ".$this->_params->offset.", ".$this->_params->limit;

		$result = Aitsu_Db :: fetchAll($sql, array (
			Aitsu_Registry :: get()->env->idlang
		));

		foreach ($result as $row) {
		    $articles[] = (object)$row;
        }

		$view = $this->_getView();
        $view->items = $articles;
        $view->idcat = $this->_params->idcat;

		$output = $view->render($this->_params->template.'.phtml');

		if (Aitsu_Application_Status :: isEdit() ) {
			$output = '// '.Aitsu_Translate::_("Liste konfigurieren").' //'.$output;
		}

		return $output;
	}

    protected function _cachingPeriod() {
        return 60*60*24*365;
    }
}
?>