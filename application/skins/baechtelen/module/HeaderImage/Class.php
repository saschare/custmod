<?php

/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 * @modified by Conrad Leu, Mereo GmbH, 2011
 */

class Skin_Module_HeaderImage_Class extends Aitsu_Module_Abstract {

	protected function _main(){

		$view = $this->_getView();

		$choices = array ();
		if (isset($this->_params->image)) {
			foreach ($this->_params->image as $key => $value) {
				$choices[$value->name] = $value->path;
			}
		}
		$choices[Aitsu_Translate :: translate('Inherit from above')] = 'inheritFromAbove';
		$choices[Aitsu_Translate :: translate('Use article media')] = 'useArticleMedia';

		$medium = Aitsu_Content_Config_Radio :: set($this->_index, 'HeaderIllustrationMedium', '', $choices, Aitsu_Translate :: translate('Header'));

		$images = Aitsu_Content_Config_Media :: set($this->_index, 'HeaderIllustrationMedia', Aitsu_Translate :: translate('Choose image'));
        $media = Aitsu_Persistence_View_Media :: byFileName(Aitsu_Registry :: get()->env->idart, $images);

		if (empty($medium) && empty($media)) {
			$medium = 'inheritFromAbove';
		} elseif (empty($medium) && !empty($media)) {
			$medium = 'useArticleMedia';
		}

		if ($medium == 'useArticleMedia') {
			$source = $media;
		}

		if ($medium == 'inheritFromAbove') {
			$source = $this->_inheritFromAbove();
		}

		$view->source = $source[0];

		$output = $view->render('index.phtml');

		if (Aitsu_Registry :: isEdit()) {
			$output = '<code class="aitsu_params" style="display:none;">' . $this->_params . '</code>' . $output;
		}

		return $output;
	}

    protected function _cachingPeriod() {
        return 60*60*24*365;
    }

	protected function _inheritFromAbove() {
		$image = Aitsu_Db :: fetchRow("select distinct
			artlang.idart,
			propa.textvalue as `media`,
			propb.textvalue as `medium`
		from _cat as child
		left join _cat as parent on child.lft between parent.lft and parent.rgt
		left join _cat_lang as catlang on parent.idcat = catlang.idcat
		left join _art_lang as artlang on catlang.startidartlang = artlang.idartlang
		left join _aitsu_property as namea on namea.identifier = ?
		left join _aitsu_article_property as propa on artlang.idartlang = propa.idartlang and namea.propertyid = propa.propertyid
		left join _aitsu_property as nameb on nameb.identifier = ?
		left join _aitsu_article_property as propb on artlang.idartlang = propb.idartlang and nameb.propertyid = propb.propertyid
		where
			parent.idclient = ?
			and child.idcat = ?
			and catlang.idlang = ?
			and propb.textvalue != 'inheritFromAbove'
		order by
			parent.lft desc", array (
			'ModuleConfig_' . $this->_index . ':HeaderIllustrationMedia',
			'ModuleConfig_' . $this->_index . ':HeaderIllustrationMedium',
			Aitsu_Registry :: get()->env->idclient,
			Aitsu_Registry :: get()->env->idcat,
			Aitsu_Registry :: get()->env->idlang
		));

		if (!$image) {
			return null;
		}

		if ($image['medium'] == 'useArticleMedia') {
			$filename = unserialize($image['media']);

			$media = Aitsu_Persistence_View_Media :: byFileName($image['idart'], $filename);

			return $media;
			/*
			$media = unserialize($image['media']);
			var_dump($media[0]);
			var_dump(Aitsu_Core_File :: getByFilename($media[0], $image['idartlang']));
			return Aitsu_Core_File :: getByFilename($media[0], $image['idartlang']);
			*/
		}

		return $image['medium'];
	}
}