<?php


/**
 * ArticleListByTag - shortCode.
 * 
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2010, Mereo GmbH
 * 
 */

class Skin_Module_ArticleListByTag_Class extends Aitsu_Ee_Module_Abstract {

	public static function about() {

		return (object) array (
			'name' => 'articlelist by tag',
			'description' => Zend_Registry :: get('Zend_Translate')->translate('Creates a list of all articles with the given tags, using the defined template.'),
			'type' => 'Content',
			'author' => (object) array (
				'name' => 'Conrad Leu',
				'copyright' => 'Mereo GmbH'
			),
			'version' => '1.0.2',
			'status' => 'stable',
			'url' => null,
			'id' => '4ce2bcdc-40ac-43b1-bc9d-581f5e7e1264'
		);
	}

	public static function init($context) {
		$instance = new self();

		$output = '';
		if ($instance->_get('ArticleListByTag_' . preg_replace('/[^a-zA-Z_0-9]/', '', $context['index']), $output)) {
			$data = unserialize($output);
			return $data->view->render($data->template . '.phtml');
		}

		$index = empty ($context['index']) ? 'noindex' : $context['index'];
		$params = Aitsu_Util :: parseSimpleIni($context['params']);

        $params->listTemplate = Aitsu_Content_Config_Radio :: set($index, 'template', '', $instance->_getTemplates(), 'Template');
		$params->numItems = Aitsu_Content_Config_Text :: set($index, 'numItems', Aitsu_Translate::_("Anzahl Artikel"), Aitsu_Translate::_('Artikelliste nach Tags'));
        $params->showPeriod = Aitsu_Content_Config_Select :: set($index, 'period', Aitsu_Translate::_("Zeitfenster"), array(Aitsu_Translate::_("Zeitfenster ignorieren")=>'ignore', Aitsu_Translate::_("jetzt gueltige Artikel")=>'now', Aitsu_Translate::_("vergangene Artikel")=>'past', Aitsu_Translate::_("aktuelle und kommende Artikel")=>'upcoming'), Aitsu_Translate::_('Artikelliste nach Tags'));

		$params->sortBy = Aitsu_Content_Config_Select :: set($index, 'sortBy', Aitsu_Translate::_("Sortierung nach"), array(Aitsu_Translate::_("Publikationsdatum")=>'pubfrom', Aitsu_Translate::_("Seitentitel")=>'pagetitle'), Aitsu_Translate::_("Artikelliste nach Tags"));
        $params->sortOrder = Aitsu_Content_Config_Select :: set($index, 'sortOrder', Aitsu_Translate::_("Sortierreihenfolge"), array(Aitsu_Translate::_("aufsteigend")=>'asc', Aitsu_Translate::_("absteigend")=>'desc'), Aitsu_Translate::_("Artikelliste nach Tags"));

        $tag = Aitsu_Db::fetchAll("select * from _tag order by tag", array());
		foreach($tag as $key=>$value) {
			$tagsDb[$value['tag']] = $value['tag'];
		}
        $tags = Aitsu_Content_Config_Checkbox :: set($index, 'tags', Aitsu_Translate::_("Tag w&auml;hlen"), $tagsDb, Aitsu_Translate::_('Artikelliste nach Tags'));


        
		if (isset($params->listTemplate) && !empty($params->listTemplate)) {
			$params->template = $params->listTemplate;
		} elseif (!isset($params->template)){
            $params->template = 'index';
        }

		if (isset($params->numItems) && !empty($params->numItems)) {
			$params->limit = $params->numItems;
		} elseif (!isset($params->limit)){
            $params->limit = 999;
        }

		if (!isset($params->offset)) {
			$params->offset = 0;
		}

		if (isset($params->sortBy) && !empty($params->sortBy)) {
			$params->sortby = $params->sortBy;
		} elseif (!isset($params->sortby)){
            $params->sortby = 'pubfrom';
        }

		if (isset($params->sortOrder) && !empty($params->sortOrder)) {
			$params->order = $params->sortOrder;
		} elseif (!isset($params->order)){
            $params->order = 'asc';
        }

		if (isset($params->showPeriod) && !empty($params->showPeriod)) {
			$params->period = $params->showPeriod;
		} elseif (!isset($params->period)){
            $params->period = 'now';
        }

		$now = date("Y-m-d H:i:s");
        if ($params->period == 'now') {
            $period = "	and (artlang.pubfrom IS NULL or artlang.pubfrom <= ? )
				and (artlang.pubuntil IS NULL or artlang.pubuntil >= ?)";
        } elseif($params->period == 'past') {
            $period = "	and (artlang.pubfrom IS NULL or artlang.pubfrom < ? )
				and (artlang.pubuntil IS NULL or artlang.pubuntil < ?)";
        } elseif ($params->period == 'upcoming') {
            $period = " and artlang.pubfrom >= '".$now."'
                and ? = ? ";
        } elseif($params->period == 'ignore') {
	        $period = ' and ? = ? ';
        }

		if (!is_array($tags)) {
			foreach($params->tag as $tag) {
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
				media.extension as extension,
				tblsetlink.textvalue as setlink
			from _art_lang as artlang
			left join _cat_art as catart ON artlang.idart = catart.idart
			left join _cat_lang as catlang ON catart.idcat = catlang.idcat AND catlang.idlang = ?
			left join _tag_art as tagart on artlang.idart = tagart.idart
			left join _tag as tag on tagart.tagid = tag.tagid
			left join _media as media on media.idart = artlang.idart and media.filename = artlang.mainimage

			left join _aitsu_property as propsetlink on propsetlink.identifier = 'ModuleConfig_SetLink:link'
			left join _aitsu_article_property as tblsetlink on tblsetlink.idartlang = artlang.idartlang and propsetlink.propertyid = tblsetlink.propertyid

			where
				artlang.online = 1
				and artlang.idlang = ?
				".$period."
				and tag.tag in ('".implode("','", $tags)."')
            group by
                artlang.idartlang
			order by";
		if($params->period != 'ignore' || $params->sortby == 'pubfrom') {
			$sql .= " pubfrom ".$params->order.",";
		}
		$sql .=" pagetitle ".$params->order."
		    limit ".$params->offset.", ".$params->limit;

		$result = Aitsu_Db :: fetchAll($sql, array (
			Aitsu_Registry :: get()->env->idlang,
			Aitsu_Registry :: get()->env->idlang,
			$now,
			$now
		));
		
		foreach ($result as $row) {
			$articles[] = (object)$row;
		}

		$view = $instance->_getView();

		$view->articles = $articles;

        $view->month = self::getMonthNames();
        $view->weDay = self::getWeekDays();

		$output = $view->render($params->template . '.phtml');

		if (Aitsu_Application_Status :: isEdit() ) {
			$output = '// '.Aitsu_Translate::_("Liste konfigurieren").' //'.$output;
		}

		$instance->_save(serialize((object) array (
			'view' => $view,
			'template' => $params->template
		)), 'eternal');

		return $output;
	}
    public static function getMonthNames() {
        $month = array(
            "January"=>"Januar",
            "February"=>"Februar",
            "March"=>"M&auml;rz",
            "April"=>"April",
            "May"=>"Mai",
            "June"=>"Juni",
            "July"=>"Juli",
            "August"=>"August",
            "September"=>"September",
            "October"=>"Oktober",
            "November"=>"November",
            "December"=>"Dezember"
        );

        return $month;
    }

    public static function getWeekDays() {
        $weDay = array(
            "Mon"=>"Montag",
            "Tue"=>"Dienstag",
            "Wed"=>"Mittwoch",
            "Thu"=>"Donnerstag",
            "Fri"=>"Freitag",
            "Sat"=>"Samstag",
            "Sun"=>"Sonntag"
        );

        return $weDay;
    }

}