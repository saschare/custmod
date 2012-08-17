<?php
/**
 * Agenda Artikelliste
 *
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 */

class Skin_Module_ArticleListByCat_Class extends Aitsu_Ee_Module_Abstract {

	protected $type = 'articlelist';

	public static function about() {

		return (object) array (
			'name' => 'ArticleListByCat',
			'description' => Aitsu_Translate :: translate('Lists the articles of the given Categories.'),
			'type' => 'articlelist',
			'author' => (object) array (
				'name' => 'Conrad Leu',
				'copyright' => 'Mereo GmbH'
			),
			'version' => '1.0.1',
			'status' => 'stable',
			'url' => null,
			'id' => '4d5efe79-60a4-4517-899c-487c53f3381f'
		);
	}

	public static function init($context) {
		$instance = new self();
        
		$index = $context['index'];

		$params = Aitsu_Util :: parseSimpleIni($context['params']);

		$output = '';
		if ($instance->_get('ArticleListByCat_' . preg_replace('/[^a-zA-Z_0-9]/', '', $context['index']), $output)) {
			return $output;
		}


		$params->template = Aitsu_Content_Config_Radio :: set($index, 'template', '', $instance->_getTemplates(), 'Template');

		$sourceCategories = Aitsu_Ee_Config_Text :: set($context['index'], 'sourceCategories', Aitsu_Translate::_("Kategorien eingeben (Komma getrennt)"), Aitsu_Translate::_("Quell-Kategorie/n"));
		$categories = Mereo_Util_Navigation::getCategories();
		$categories = array_merge(array(' - '=>''), $categories);
		$idcat = Aitsu_Content_Config_Select :: set($index, 'idcat', Aitsu_Translate::_("..oder Kategorie w&auml;hlen"), $categories, Aitsu_Translate::_("Quell-Kategorie/n"));
		$listChildCats = Aitsu_Content_Config_Checkbox::set($context['index'], 'listChildCats', Aitsu_Translate::_("Kind-Kategorien"), array(Aitsu_Translate::_("einbeziehen")=>1) , Aitsu_Translate::_("Quell-Kategorie/n"));

		$sortOrder = Aitsu_Content_Config_Select :: set($index, 'sortOrder', Aitsu_Translate::_("Sortierreihenfolge"), array(Aitsu_Translate::_("aufsteigend")=>'asc', Aitsu_Translate::_("absteigend")=>'desc'), Aitsu_Translate::_("Listenkonfiguration"));
		$numArticles = Aitsu_Ee_Config_Text :: set($context['index'], 'numArticles', Aitsu_Translate::_("Anzahl Artikel"), Aitsu_Translate::_("Listenkonfiguration"));
		$period = Aitsu_Content_Config_Select::set($context['index'], 'period', Aitsu_Translate::_("Zeitfenster"), array(Aitsu_Translate::_("laufende und kommende Artikel")=>'upcoming', Aitsu_Translate::_("vergangene Artikel")=>'past', Aitsu_Translate::_("Zeitfenster ignorieren")=>'ignore'), Aitsu_Translate::_("Listenkonfiguration"));

		if ($idcat != '') {
			$idcat = explode('-', $idcat);
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


        $whereInChildCategories = $whereInChildCategoriesJoin = '';
		if ($listChildCats == 'CHANGE THIS VALUE TO 1 IF FIXED') {
			$whereInChildCategoriesJoin = " left join _cat as parent ON parent.idcat = ? ";

			//@todo: make nested-set query for child-categories
            $whereInChildCategories = " and cat.";
        }

        $whereInCategories = ' and catart.idcat in ('.$params->idcat.') ';

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
			".$whereInChildCategoriesJoin."
			left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = ?
			where
				artlang.online = 1
				and artlang.idlang = ?
				and catlang.startidartlang != artlang.idartlang
                ".$whereInCategories."
                ".$whereInChildCategories."
                ".$period."
			order by ";
		if($params->period != 'ignore') {
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

		$output = $view->render($params->template.'.phtml');

		if (Aitsu_Application_Status :: isEdit() ) {
			$output = '// '.Aitsu_Translate::_("Liste konfigurieren").' //'.$output;
		}

		$instance->_save($output, 'eternal');

		return $output;
	}
}
?>