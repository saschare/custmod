<?php
class Skin_Module_BodyTag_Class extends Aitsu_Module_Abstract {

    protected $_allowEdit = false;

	protected function _main() {

		$view = $this->_getView();
		$nav = Aitsu_Persistence_View_Category :: nav(1);

        /*
         * check if we are on level no.1
         * used for different styles of headings
        */
        $isFirstLevel = Aitsu_Db::fetchOne("select
                parentid
            from
              _cat as cat
            where
              cat.idcat = ?
              and cat.parentid in (select idcat from _cat where parentid = 0)
              ", array(Aitsu_Registry::get()->env->idcat));
        if ($isFirstLevel) {
            $view->isFirstLevel = true;
        } else {
            $view->isFirstLevel = false;
        }

        foreach(array_values($nav->children) as $this->_index => $mainNav) {
            if ($mainNav->iscurrent OR $mainNav->isparent){
                $view->index = $this->_index;
                break;
            }
        }

		$template = isset ($this->_params->template) ? $this->_params->template : 'index';
		return $view->render($template.'.phtml');
	}

    protected function _cachingPeriod() {
        return 60*60*24*365;
    }
}
?>