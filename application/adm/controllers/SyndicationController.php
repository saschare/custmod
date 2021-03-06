<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class SyndicationController extends Zend_Controller_Action {

	public function init() {

	}

	public function indexAction() {

		header("Content-type: text/javascript");
		$this->_helper->layout->disableLayout();
	}

	public function deleteAction() {

		$this->_helper->layout->disableLayout();

		Aitsu_Persistence_SyndicationSource :: factory($this->getRequest()->getParam('sourceid'))->remove();

		$this->_helper->json((object) array (
			'success' => true
		));
	}

	public function editAction() {

		$this->_helper->layout->disableLayout();

		$id = $this->getRequest()->getParam('sourceid');

		$form = Aitsu_Forms :: factory('edit', APPLICATION_PATH . '/adm/forms/syndication/syndication.ini');
		$form->title = Aitsu_Translate :: translate('Edit source');
		$form->url = $this->view->url();
		$form->setValue('idclient', Aitsu_Registry :: get()->session->currentClient);

		if (!empty ($id)) {
			$data = Aitsu_Persistence_SyndicationSource :: factory($id)->load()->toArray();
			$form->setValues($data);
		}

		if ($this->getRequest()->getParam('loader')) {
			$this->view->form = $form;
			header("Content-type: text/javascript");
			return;
		}

		try {
			if ($form->isValid()) {
				$values = $form->getValues();

				/*
				 * Persist the data.
				 */
				if (empty ($id)) {
					/*
					 * New source.
					 */
					unset ($values['sourceid']);
					Aitsu_Persistence_SyndicationSource :: factory()->setValues($values)->save();
				} else {
					/*
					 * Update source.
					 */
					Aitsu_Persistence_SyndicationSource :: factory($id)->load()->setValues($values)->save();
				}

				$this->_helper->json((object) array (
					'success' => true
				));
			} else {
				$this->_helper->json((object) array (
					'success' => false,
					'errors' => $form->getErrors()
				));
			}
		} catch (Exception $e) {
			$this->_helper->json((object) array (
				'success' => false,
				'exception' => true,
				'message' => $e->getMessage()
			));
		}
	}

	public function storeAction() {

		$results = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	* ' .
		'from _syndication_source ' .
		'where ' .
		'	idclient = :idclient ' .
		'order by' .
		'	url asc', array (
			':idclient' => Aitsu_Registry :: get()->session->currentClient
		));

		$data = array ();
		foreach ($results as $result) {
			$data[] = (object) array (
				'sourceid' => $result['sourceid'],
				'url' => $result['url'],
				'userid' => $result['userid']
			);
		}

		$this->_helper->json((object) array (
			'data' => $data
		));
	}

	public function treeAction() {

		$return = array ();
		$id = $this->getRequest()->getParam('node');

		if ($id == '0') {
			/*
			 * Root node. Show available sources.
			 */
			$sources = Aitsu_Db :: fetchAll('' .
			'select ' .
			'	sourceid, ' .
			'	url ' .
			'from _syndication_source ' .
			'where idclient = :idclient ' .
			'order by url asc ', array (
				':idclient' => Aitsu_Registry :: get()->session->currentClient
			));

			if ($sources) {
				foreach ($sources as $source) {
					$return[] = array (
						'id' => $source['sourceid'],
						'text' => $source['url'],
						'name' => '',
						'leaf' => false,
						'iconCls' => 'treecat-online',
						'type' => 'source'
					);
				}
			}

			$this->_helper->json($return);
		}

		preg_match('/(\\d*)(?:\\-([a-z]*)\\-(\\d*))?/', $id, $match);

		$id = empty ($match[3]) ? 0 : $match[3];
		$type = empty ($match[2]) ? 'unk' : $match[2];

		$source = Aitsu_Db :: fetchRow('' .
		'select * from _syndication_source ' .
		'where sourceid = :sourceid', array (
			':sourceid' => $match[1]
		));

		$locale = Aitsu_Db :: fetchOne('' .
		'select locale from _lang where idlang = :idlang', array (
			':idlang' => Aitsu_Registry :: get()->session->currentLanguage
		));

		$response = Aitsu_Http_Hmac_Sha1 :: factory($source['url'] . 'rest/syndication/tree/', $source['userid'], $source['secret'])->setParams(array (
			'id' => $id,
			'type' => $type,
			'locale' => $locale
		))->getResponse();

		$response = json_decode($response);

		foreach ($response as $entry) {
			$return[] = array (
				'id' => $source['sourceid'] . '-' . $entry->type . '-' . $entry->id,
				'sourceid' => $source['sourceid'],
				'idartlang' => $entry->id,
				'text' => $entry->name,
				'name' => $entry->name,
				'leaf' => $entry->type == 'page',
				'iconCls' => $entry->type == 'page' ? 'treepage-online' : 'treecat-online',
				'type' => $entry->type
			);
		}

		$this->_helper->json($return);
	}
	
	public function contentAction() {
		
		$idart = $this->getRequest()->getParam('idart');
		$idlang = $this->getRequest()->getParam('idlang');
	}
}