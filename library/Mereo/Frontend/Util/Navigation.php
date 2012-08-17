<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Conrad Leu
 * Date: 16.08.12
 * Time: 18:23
 * To change this template use File | Settings | File Templates.
 */
class Mereo_Frontend_Util_Navigation {

    /**
     * returns an absolute url from a given link (ie. "idcat-123" or "idart 465" from Aitsu_Content_Config_Link)
     * @static
     * @param string $link
     * @return bool|string
     */
    public static function getUrlFromLink($link=null) {

        if (empty($link)) {
            return false;
        }

        $link = str_replace('-', ' ', ($link));
        $linkExploded = explode(' ', $link);
        if (preg_match("|idart|", $linkExploded[0])) {
            $url = Aitsu_Db::fetchOne("select
                      concat_ws('/', catlang.url, artlang.urlname) as url
                    from
                      _art_lang as artlang
                      left join _cat_art as catart on catart.idart = artlang.idart
                      left join _cat_lang as catlang on catart.idcat = catlang.idcat
                    where
                      artlang.idart = ?
                      and artlang.idlang = ?
                      and catlang.idlang = artlang.idlang
                    ", array(
                $linkExploded[1],
                Aitsu_Registry::get()->env->idlang
            ));
            return '/'.$url.'.html';
        } else {
            $url = Aitsu_Db::fetchOne("select
                      catlang.url
                    from
                      _cat_lang as catlang
                    where
                      catlang.idcat = ?
                      and catlang.idlang = ?
                    ", array(
                $linkExploded[1],
                Aitsu_Registry::get()->env->idlang
            ));
            return '/'.$url;
        }
    }
}
