<?php


/**
 * Meta Data batch editing grid controller
 *
 * TODO: make category pagetitle synchronous with title
 * TODO: add more meta data
 * TODO: extend to cope with addtionals meta data added from 3rd party (e.g. OpenGraph)
 * TODO: add localization
 * TODO: update tree nodes (west) if on stage (or reload tree)
 *
 * @author Andreas Kummer, w3concepts AG
 * @author Elmar Bransch, Minkenberg Medien GmbH
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class MetaBatchCategoryController extends Aitsu_Adm_Plugin_Controller {

	const ID = '4cd2cac0-f2a0-4ecc-98b1-0ace7e000201';
	
	public function init() {

		$this->_helper->layout->disableLayout();
		header("Content-type: text/javascript");
	}

	public static function register($idcat) {
		
		$pos = self :: getPosition($idcat, 'metabatch', 'category');

		return (object) array (
			'name' => 'metabatch',
			'tabname' => Aitsu_Translate :: translate('Meta Data Editor (alpha)'),
			'enabled' => $pos,
			'position' => $pos,
			'id' => self :: ID
		);
	}

	public function indexAction() {

		$idcat = $this->getRequest()->getParam('idcat');
		$cat = Aitsu_Persistence_Category :: factory($idcat)->load();

		$this->view->title = Aitsu_Translate :: translate('Meta Data Editor (alpha)');
		$this->view->usePublishing = isset (Aitsu_Registry :: get()->config->sys->usePublishing) && Aitsu_Registry :: get()->config->sys->usePublishing == true;
		$this->view->idcat = $idcat;
		$this->view->categoryname = $cat->name;
		$this->view->articles = Aitsu_Persistence_View_Articles :: full($idcat, null);
		$this->view->isInFavories = Aitsu_Persistence_CatFavorite :: factory($idcat)->load()->isInFavorites();
		$this->view->isClipboardEmpty = !isset (Aitsu_Registry :: get()->session->clipboard->articles) || count(Aitsu_Registry :: get()->session->clipboard->articles) == 0;
	}

	
	private function articleForCat( $idcat, $indent ) {

		$data = array ();
		$arts = Aitsu_Persistence_View_Articles :: full($idcat, null);
		if ($arts) {
			foreach ($arts as $art) {
				$data[] = (object) array (
					'id' => 'idart:'.$art['idart'],
					'level' => $indent,
					'type' => 'art',
					'title' => $art['title'],
					'pagetitle' => $art['pagetitle'],
					'urlname' => $art['urlname'],
					'online' => $art['online'],
					'author' => $art['author'],
					'published' => $art['published'],
					'isstart' => $art['isstart']
					// find a way to add more properties dynamically
					// pot. even from 3rd party (e.g. OpenGraph)
				);
			}
		}
		return $data;
	}

	private function categoriesInCat( $nav, $indent ) {
	
		$data = array ();
		
		$artdata =	$this->articleForCat( $nav->idcat, $indent );		
		$data = array_merge( $data, $artdata );

		if( !empty( $nav->children ) ) {
			foreach( $nav->children as $navc ) {
				$data[] = (object) array (
					'id' => 'idcat:'.$navc->idcat,
					'level' => $indent,
					'type' => 'cat',
					'title' => $navc->name,
					'pagetitle' => $navc->name,
					'urlname' => $navc->urlname,
					'online' => 1,
					'author' => 'elmar',
					'published' => -1,
					'isstart' => -1
					// find a way to add more properties dynamically
				);
				$artdata =	$this->articleForCat( $navc->idcat, $indent+1 );		
				$data = array_merge( $data, $artdata );

				if( !empty( $navc->children ) ) {
					$subdata = $this->categoriesInCat( $navc, $indent+1 );
					$data = array_merge( $data, $subdata );
				}
				
			}
		}
		return $data;
	}

	
	public function catarttreeAction() {

		$nav = Aitsu_Persistence_View_Category :: nav( $this->getRequest()->getParam('idcat') );

		$data = $this->categoriesInCat( $nav , 0 );
		
		$this->_helper->json((object) array (
			'data' => $data
		));
	}
	
	// pass the changed data to the category persistance layer
	private function saveCategoryData( $id, $changeset ) {
		try {
			$data = Aitsu_Persistence_Category :: factory($id)->load()->setValues(
				$changeset
			)->save()->getData();
			return $data;
		} catch (Exception $e) {
			$this->_helper->json((object) array (
				'sucess' => false,
				'status' => 'exception',
				'message' => $e->getMessage()
			));
			return false;
		}
	}

	// pass the changed data to the article persistance layer
	private function saveArticleData( $id, $changeset ) {

		$changeset = (array)$changeset;

		try {
			$data = Aitsu_Persistence_Article :: factory($id)->load()->setValues(
				$changeset
			)->save()->getData();
			return $data;
		} catch (Exception $e) {
			$this->_helper->json((object) array (
				'sucess' => false,
				'status' => 'exception',
				'message' => $e->getMessage()
			));
			return false;
		}
	}
	
	// re-map the grid columns to category columns
	private function prepareCategoryChangeset( $changeset ) {

		// in the grid we have mixed article and category data, so we need
		// to map the grid columns (that are based on article data) to the
		// corresponding category column names
		static $mapCatMembers = Array(
			'title'			=> 'name',
			'urlname'		=> 'urlname',
		);
		
		$changeset = (array)$changeset;
		// prevent re-setting the primary id on update
		unset( $changeset['id'] );

		$catChange = Array();
		// re-map category property names
		foreach( $changeset as $ckey => $cval ) {
			if( isset( $mapCatMembers[$ckey] ) ) {
				$catChange[$mapCatMembers[$ckey]] = $cval;
			} else {
				$catChange[$ckey] = $cval;
			}
		}
		
		return $catChange;
	}

	
	public function savechangesAction() {
		$data = array();
		
		$changes = json_decode( $this->getRequest()->getParam('changes') );
		// print_r( $changes );
		
		
		foreach( $changes as $change ) {
			// change->id
			list( $type, $id ) = explode( ':', $change->id );

			print "Updating $type = $id\n";
			// print "Current data = \n";
			// print_r( Aitsu_Persistence_Category :: factory($id)->getData() );

			if( $type == 'idcat' ) {

				$data = $this->saveCategoryData( $id, $this->prepareCategoryChangeset( $change ) );
				if( $data === false ) {
					// operation failed
					return;
				}
			}

			if( $type == 'idart' ) {

				$data = $this->saveArticleData( $id, $change );
				if( $data === false ) {
					// operation failed
					return;
				}
			}
		}

		$this->_helper->json((object) array (
			'sucess' => true,
			'data' => $data
		));
	}
}