<?php
/**
 * Portfolio (images by tags or other articles)
 *
 * @author Conrad Leu, Mereo GmbH
 * @copyright Copyright &copy; 2011, Mereo GmbH
 *
 */

class Skin_Module_MediaByTag_Class extends Aitsu_Module_Abstract {

	protected function _main() {

		$output = '';
		if ($this->_get('MediaByTag_' . $this->_index, $output)) {
			$data = unserialize($output);
			return $data->view->render($data->template . '.phtml');
		}

		$view = $this->_getView();

		// configure tags
        $tag = Aitsu_Db::fetchAll("select * from _media_tag order by tag", array());
		foreach($tag as $key=>$value) {
			$tagsDb[$value['tag']] = $value['tag'];
		}
        $tags = Aitsu_Content_Config_Checkbox :: set($this->_index, 'tags', Aitsu_Translate::_("Tag w&auml;hlen"), $tagsDb, Aitsu_Translate::_("Dateiauswahl nach Tags"));
		if (!is_array($tags)) {
			foreach($this->_params->tag as $tag) {
				$tags[] = $tag;
			}
		}

        $selectedFileTypes = Aitsu_Content_Config_Checkbox::set($this->_index, 'fileType', Aitsu_Translate::_("Datei-Typ ausw&auml;hlen"), array(Aitsu_Translate::_("alle Dateien")=>'all', Aitsu_Translate::_("JPG-Bilder")=>'jpg,jpeg', Aitsu_Translate::_("GIF-Bilder")=>'gif', Aitsu_Translate::_("PNG-Bilder")=>'png', Aitsu_Translate::_("PDF-Dokumente")=>'pdf', Aitsu_Translate::_("Word/Writer-Dokumente")=>'doc,docx,odt', Aitsu_Translate::_("Excel/Calc-Dokumente")=>'xls,xlsx,ods'), Aitsu_Translate::_("Dateiauswahl nach Tags"));
        if (!is_array($selectedFileTypes)) {
            foreach($this->_params->filetype as $type) {
                $fileTypes[] = $type;
            }
        } else {
            foreach ($selectedFileTypes as $type) {
                if ($type == 'all') {
                    $fileTypes = false;
                    break;
                }
                $extensions = explode(',', $type);
                foreach ($extensions as $extension) {
                    $fileTypes[] = $extension;
                }
            }
        }

		// get or configure template
		$template = Aitsu_Content_Config_Radio :: set($this->_index, 'template', Aitsu_Translate::_("Template w&auml;hlen"), $this->_getTemplates(), Aitsu_Translate::_("Darstellung"));
		if (empty($template)) {
			if (isset($this->_params->template)) {
				$template = $this->_params->template;
			} else {
				$template = 'index';
			}
		}

		// get files by chosen tags
        $sql = "select
				media.mediaid,
				media.idart,
				media.filename,
				media.size,
				media.extension,
				mediadesc.name,
				mediadesc.subline,
				mediadesc.description,
				mediatags.val as tagvalue
			from
				_media_tag as mediatag
			left join _media_tags as mediatags on mediatag.mediatagid = mediatags.mediatagid
			left join _media as media on mediatags.mediaid = media.mediaid
			left join _media_description as mediadesc on media.mediaid = mediadesc.mediaid
			where
				mediatag.tag in ('".implode('\',\'', $tags)."')
				and mediadesc.idlang = ?
				and media.deleted is null";
        if ($fileTypes) {
            $sql .= " and media.extension in('".implode('\',\'', $fileTypes)."') ";
        }
				"and mediadesc.subline is not null
				and mediadesc.description is not null
			group by
			    media.mediaid
			order by
			"./* @todo: make sortby and order configurable */"
			    mediatags.val asc,
				mediadesc.name asc,
				media.filename asc";
		$result = Aitsu_Db::fetchAll($sql, array(Aitsu_Registry::get()->env->idlang));

		foreach($result as $item) {
			$items[] = (object)$item;
		}
		$view->items = $items;

		$output = '';

		if (Aitsu_Application_Status::isEdit()) {
			$output = '<div>// MediaByTag ('.$this->_index.') '.Aitsu_Translate::_("bearbeiten").' //</div>';
		}

		$output .= $view->render($template . '.phtml');

		$this->_save(serialize((object) array (
			'view' => $view,
			'template' => $template
		)), 'eternal');

		return $output;
	}
}
?>