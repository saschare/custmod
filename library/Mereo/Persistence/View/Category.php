<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2011, w3concepts AG
 * @modified by Conrad Leu, 2011 (added flag showInvisible to nav2() )
 */
class Mereo_Persistence_View_Category {

	public static function tree($idlang) {

		$return = array ();

		$results = Aitsu_Db :: fetchAll('' .
		'select distinct' .
		'	cat.idcat as id, ' .
		'	cat.parentid, ' .
		'	catlang.name ' .
		'from _cat as cat ' .
		'left join _cat_lang as catlang on cat.idcat = catlang.idcat ' .
		'where ' .
		'	catlang.idlang = :idlang ' .
		'order by ' .
		'	cat.lft asc ', array (
			':idlang' => $idlang
		));

		$stack = array (
			'0' => (object) array ()
		);
		foreach ($results as $row) {
			$stack[$row['id']] = (object) $row;
			if (isset ($stack[$row['parentid']])) {
				if (!isset ($stack[$row['parentid']]->children)) {
					$stack[$row['parentid']]->children = array ();
				}
				$stack[$row['parentid']]->children[] = $stack[$row['id']];
			}
		}

		$return = self :: _toArray($stack[0]);

		return $return['children'];
	}

	protected static function _toArray($stack) {

		$return = get_object_vars($stack);
		unset ($return['children']);

		if (isset ($stack->children)) {
			for ($i = 0; $i < count($stack->children); $i++) {
				$return['children'][] = self :: _toArray($stack->children[$i]);
			}
		}

		return $return;
	}

	public static function cat($cat, $idlang, $syncLang) {

		if (!empty ($syncLang)) {
			return Aitsu_Db :: fetchAll('' .
			'select ' .
			'	cat.idcat, ' .
			'	if (catlang.idcat is not null, catlang.name, synclang.name) as name, ' .
			'	catlang.visible as online, ' .
			'	catlang.public as public, ' .
			'	if(count(children.idcat) > 0, 1, 0) as haschildren, ' .
			'	if(catlang.idcat is null, 1, 0) as unsynced ' .
			'from _cat as cat ' .
			'left join _cat_lang as catlang on cat.idcat = catlang.idcat and catlang.idlang = :idlang ' .
			'left join _cat_lang as synclang on cat.idcat = synclang.idcat and synclang.idlang = :syncLang ' .
			'left join _cat as children on children.parentid = cat.idcat ' .
			'where ' .
			'	cat.parentid = :cat ' .
			'group by ' .
			'	cat.idcat, ' .
			'	catlang.name, ' .
			'	synclang.name, ' .
			'	catlang.visible, ' .
			'	catlang.public ' .
			'order by ' .
			'	cat.lft asc', array (
				':cat' => $cat,
				':idlang' => $idlang,
				':syncLang' => $syncLang
			));
		}

		return Aitsu_Db :: fetchAll('' .
		'select ' .
		'	cat.idcat, ' .
		'	catlang.name, ' .
		'	catlang.visible as online, ' .
		'	catlang.public as public, ' .
		'	if(count(children.idcat) > 0, 1, 0) as haschildren ' .
		'from _cat as cat ' .
		'left join _cat_lang as catlang on cat.idcat = catlang.idcat ' .
		'left join _cat as children on children.parentid = cat.idcat ' .
		'where ' .
		'	cat.parentid = :cat ' .
		'	and catlang.idlang = :idlang ' .
		'group by ' .
		'	cat.idcat, ' .
		'	catlang.name, ' .
		'	catlang.visible, ' .
		'	catlang.public ' .
		'order by ' .
		'	cat.lft asc', array (
			':cat' => $cat,
			':idlang' => $idlang
		));
	}

	public static function nav($idcat, $showInvisible = false, $user = null) {

		$currentLang = Aitsu_Registry :: get()->env->idlang;
		$currentCat = Aitsu_Registry :: get()->env->idcat;

		$visiblityClause = $showInvisible ? '' : 'and catlang.visible = 1';

		$cats = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	cat.idcat, ' .
		'	cat.parentid, ' .
		'	catlang.name, ' .
		'	catlang.urlname, ' .
		'	if (child.idcat is null, 0, if(child.idcat = cat.idcat, 0, 1)) as isparent, ' .
		'	if (child.idcat = cat.idcat, 1, 0) as iscurrent, ' .
		'	catlang.public as ispublic, ' .
		'	if (artlang.online = 1 and catlang.visible = 1, 1, 0) as isvisible, ' .
		'	catlang.config ' .
		'from _cat as cat ' .
		'left join _cat as root on cat.lft between root.lft and root.rgt ' .
		'left join _cat_lang as catlang on cat.idcat = catlang.idcat ' .
		'left join _art_lang as artlang on catlang.startidartlang = artlang.idartlang ' .
		'left join _cat as child on child.idcat = :idcat and child.lft between cat.lft and cat.rgt ' .
		'where ' .
		'	root.idcat = :rootIdcat ' .
		'	and catlang.idlang = :idlang ' .
		'	' . $visiblityClause . ' ' .
		'order by ' .
		'	cat.lft asc ', array (
			':idcat' => $currentCat,
			':rootIdcat' => $idcat,
			':idlang' => $currentLang
		), false);

		$return = null;
		$categories = array ();
		foreach ($cats as $category) {

			if (isset ($categories[$category['parentid']])) {
				$level = $categories[$category['parentid']]->level + 1;
			} else {
				$level = 0;
			}

			$cat = (object) $category;
			$cat->level = $level;
			$cat->children = array ();

			$categories[$cat->idcat] = $cat;

			if (isset ($categories[$cat->parentid]) && is_object($categories[$cat->parentid])) {
				$categories[$cat->parentid]->children[] = $cat;
			}
			elseif (!isset ($return)) {
				$return = $cat;
			}
		}

		self :: _setChildren($return);

		return $return;
	}

	public static function nav2($idcat, $showInvisible = false) {

		$currentLang = Aitsu_Registry :: get()->env->idlang;
		$currentCat = Aitsu_Registry :: get()->env->idcat;
		$user = Aitsu_Adm_User :: getInstance();

        $visiblityClause = $showInvisible ? '' : 'and catlang.visible = 1';

		$cats = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	cat.idcat, ' .
		'	cat.parentid, ' .
		'	catlang.name, ' .
		'	catlang.urlname, ' .
		'	if (child.idcat is null, 0, if(child.idcat = cat.idcat, 0, 1)) as isparent, ' .
		'	if (child.idcat = cat.idcat, 1, 0) as iscurrent, ' .
		'	catlang.public as ispublic, ' .
		'	if (artlang.online = 1 and catlang.visible = 1, 1, 0) as isvisible, ' .
		'	catlang.config ' .
		'from _cat as cat ' .
		'left join _cat as root on cat.lft between root.lft and root.rgt ' .
		'left join _cat_lang as catlang on cat.idcat = catlang.idcat ' .
		'left join _art_lang as artlang on catlang.startidartlang = artlang.idartlang ' .
		'left join _cat as child on child.idcat = :idcat and child.lft between cat.lft and cat.rgt ' .
		'where ' .
		'	root.idcat = :rootIdcat ' .
		'	and catlang.idlang = :idlang ' .
        '	' . $visiblityClause . ' ' .
		'order by ' .
		'	cat.lft asc ', array (
			':idcat' => $currentCat,
			':rootIdcat' => $idcat,
			':idlang' => $currentLang
		), false);

		$return = null;
		$categories = array ();
		foreach ($cats as $category) {

			if (isset ($categories[$category['parentid']])) {
				$level = $categories[$category['parentid']]->level + 1;
			} else {
				$level = 0;
			}

			$cat = (object) $category;
			$cat->level = $level;

			$cat->isaccessible = false;
			$cat->haschildren = false;
			if ($cat->ispublic) {
				$cat->isaccessible = true;
			}
			elseif ($user != null) {
				$cat->isaccessible = $user->isAllowed(array (
					'language' => Aitsu_Registry :: get()->env->idlang,
					'resource' => array (
						'type' => 'cat',
						'id' => $cat->idcat
					)
				));
			}

			$cat->children = array ();

			$categories[$cat->idcat] = $cat;

			if (isset ($categories[$cat->parentid]) && is_object($categories[$cat->parentid])) {
				$categories[$cat->parentid]->children[] = $cat;
			}
			elseif (!isset ($return)) {
				$return = $cat;
			}
		}

		self :: _setChildren($return);

		return $return;
	}

	protected static function _setChildren(& $cat) {

		if (empty ($cat)) {
			return;
		}

		foreach ($cat->children as $child) {
			self :: _setChildren($child);
		}

		foreach ($cat->children as $child) {
			if ($child->haschildren || ($child->isaccessible && $child->isvisible)) {
				$cat->haschildren = true;
			}
		}
	}

	public static function breadCrumb($idcat = null) {

		if ($idcat == null) {
			$idcat = Aitsu_Registry :: get()->env->idcat;
		}

		$idlang = Aitsu_Registry :: get()->env->idlang;

		return Aitsu_Db :: fetchAll('' .
		'select ' .
		'	catlang.* ' .
		'from _cat as child ' .
		'left join _cat as parent on child.lft between parent.lft and parent.rgt ' .
		'left join _cat_lang as catlang on catlang.idcat = parent.idcat ' .
		'where ' .
		'	child.idcat = :idcat ' .
		'	and catlang.idlang = :idlang ' .
		'order by ' .
		'	parent.lft asc ', array (
			':idcat' => $idcat,
			':idlang' => $idlang
		));
	}
}