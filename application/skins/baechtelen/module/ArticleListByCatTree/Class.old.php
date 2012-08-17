<?php
/**
 * Agenda Artikelliste
 *
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 */

class Skin_Module_ArticleListByCatTree_Class extends Aitsu_Ee_Module_Abstract {

	protected $type = 'articlelist';

	public static function about() {

		return (object) array (
			'name' => 'ArticleListByCatTree',
			'description' => Aitsu_Translate :: translate('Lists the articles of the given child Categories.'),
			'type' => 'articlelist',
			'author' => (object) array (
				'name' => 'Conrad Leu',
				'copyright' => 'Mereo GmbH'
			),
			'version' => '1.0.1',
			'status' => 'stable',
			'url' => null,
			'id' => '4e09857b-a700-4eb1-ad91-19b75e7e12c2'
		);
	}

	public static function init($context) {
		$instance = new self();
        
		$index = $context['index'];

		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$output = '';
		if ($instance->_get('ArticleListByCatTree_' . preg_replace('/[^a-zA-Z_0-9]/', '', $index), $output)) {
			$data = unserialize($output);
			return $data->view->render($data->template . '.phtml');
		}


		$params->template = Aitsu_Content_Config_Radio :: set($index, 'template', '', $instance->_getTemplates(), 'Template');

		$sourceCategories = Aitsu_Content_Config_Text :: set($context['index'], 'sourceCategories', Aitsu_Translate::_("Kategorien eingeben (Komma getrennt)"), Aitsu_Translate::_("Quell-Kategorie/n"));
		$idcat = Aitsu_Content_Config_Link :: set($index, 'idcat', Aitsu_Translate::_("..oder Kategorie w&auml;hlen"), Aitsu_Translate::_("Quell-Kategorie/n"));
		$listChildCats = Aitsu_Content_Config_Checkbox::set($context['index'], 'listChildCats', Aitsu_Translate::_("Kind-Kategorien"), array(Aitsu_Translate::_("einbeziehen")=>1) , Aitsu_Translate::_("Quell-Kategorie/n"));

		$sortBy = Aitsu_Content_Config_Select :: set($index, 'sortBy', Aitsu_Translate::_("Sortierung nach"), array(Aitsu_Translate::_("Publikationsdatum")=>'pubfrom', Aitsu_Translate::_("Kategoriereihenfolge")=>'lft'), Aitsu_Translate::_("Listenkonfiguration"));
		$sortOrder = Aitsu_Content_Config_Select :: set($index, 'sortOrder', Aitsu_Translate::_("Sortierreihenfolge"), array(Aitsu_Translate::_("aufsteigend")=>'asc', Aitsu_Translate::_("absteigend")=>'desc'), Aitsu_Translate::_("Listenkonfiguration"));
		$numArticles = Aitsu_Content_Config_Text :: set($context['index'], 'numArticles', Aitsu_Translate::_("Anzahl Artikel"), Aitsu_Translate::_("Listenkonfiguration"));
		$period = Aitsu_Content_Config_Select::set($context['index'], 'period', Aitsu_Translate::_("Zeitfenster"), array(Aitsu_Translate::_("laufende und kommende Artikel")=>'upcoming', Aitsu_Translate::_("vergangene Artikel")=>'past', Aitsu_Translate::_("Zeitfenster ignorieren")=>'ignore'), Aitsu_Translate::_("Listenkonfiguration"));

		if (preg_match("|idcat|", $idcat)) {
			$idcat = explode(' ', $idcat);
			$params->idcat = $idcat[1];
		} elseif ($sourceCategories != '') {
            $params->idcat = $sourceCategories;
        } elseif (!isset($params->idcat)) {
            $params->idcat = 1;
        }

		if (empty($params->template)) {
			$params->template = 'index';
		}

        if (isset($numArticles)){
            $params->limit = $numArticles;
        }
		if (!isset($params->limit) OR $params->limit == 0) {
			$params->limit = 999;
		}

		if (!isset($params->offset)) {
			$params->offset = 0;
		}

		if (isset($sortOrder)) {
			$params->order = $sortOrder;
		} elseif (!isset($params->order)){
            $params->order = 'asc';
        }

		if (isset($sortBy)) {
			$params->sortby = $sortBy;
		} elseif (!isset($params->sortby)){
            $params->sortby = 'pubfrom';
        }

        $whereInCategories = $whereInChildCategories = $whereInChildCategoriesJoin = '';
		if ($listChildCats[1] == 1) {

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
                from _cat as parent
                inner join _cat as child
                left join _cat_art as catart ON catart.idcat = child.idcat
                left join _art_lang as artlang on catart.idart = artlang.idart
                left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = ?
                where
                    parent.idcat = ".Aitsu_Registry::get()->env->idcat. "
                    and child.lft
                    between parent.lft
                    and parent.rgt
                    and artlang.online = 1
                    and artlang.idlang = ? 
                    and artlang.idartlang != ".Aitsu_Registry::get()->env->idartlang;
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
                left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = ?
                where
                    artlang.online = 1
                    and artlang.idlang = ?
                    and catlang.startidartlang != artlang.idartlang
                    and catart.idcat in (".$params->idcat.') ';
        }

        if(isset($period)) {
	        $params->period = $period;
        } elseif (!isset($params->period)) {
            $params->period = 'upcoming';
		}

		$now = date("Y-m-d H:i:s");
        if ($params->period == 'upcoming') {
            $period = " and pubuntil >= '".$now."'";
        } elseif($params->period == 'past') {
            $period = " and pubuntil < '".$now."'";
        } else {
	        $period = " and '".$now."' = '".$now."' ";
        }

        $sql .= $period;
        $sql .= " order by ";
        if ($params->sortby == 'pubfrom') {
			$sql .= " pubfrom ".$params->order.",";
		} elseif ($params->sortby == 'lft') {
			$sql .= " child.lft asc,";
        } elseif($params->period != 'ignore') {
			$sql .= " pubfrom ".$params->order.",";
        }
		$sql .=" pagetitle ".$params->order."
			limit ".$params->offset.", ".$params->limit;

		$result = Aitsu_Db :: fetchAll($sql, array (
			Aitsu_Registry :: get()->env->idlang,
			Aitsu_Registry :: get()->env->idlang
		));

		foreach ($result as $row) {
		    $articles[] = (object)$row;
        }

		$view = $instance->_getView();

        $view->items = $articles;
        
        $view->idcat = $params->idcat;

        $view->sql = $sql;

		$output = $view->render($params->template.'.phtml');

		if (Aitsu_Application_Status :: isEdit() ) {
			$output = '// '.Aitsu_Translate::_("Liste konfigurieren").' //'.$output;
		}

		$instance->_save(serialize((object) array (
			'view' => $view,
			'template' => $params->template
		)), 'eternal');

		return $output;
	}
}
?>