<?php


/**
 * @author Andreas Kummer, w3concepts AG
 * @copyright Copyright &copy; 2010, w3concepts AG
 */

class Aitsu_Persistence_User extends Aitsu_Persistence_Abstract {

	protected $_id = null;
	protected $_data = null;
	protected $_properties = null;

	protected function __construct($id) {

		$this->_id = $id;
	}

	public static function factory($id = null) {

		static $instance = array ();

		if ($id == null || !isset ($instance[$id])) {
			$instance[$id] = new self($id);
		}

		return $instance[$id];
	}

	public function load() {

		if ($this->_id == null || $this->_data !== null) {
			return $this;
		}

		$this->_data = Aitsu_Db :: fetchRow('' .
		'select * from _acl_user where userid = :id', array (
			':id' => $this->_id
		));

		$this->_data['roles'] = Aitsu_Db :: fetchCol('' .
		'select roleid from _acl_roles where userid = :id', array (
			':id' => $this->_id
		));

		return $this;
	}

	public function __get($key) {

		if ($this->_data === null) {
			$this->load();
		}

		if (!isset ($this->_data[$key])) {
			return null;
		}

		return stripslashes($this->_data[$key]);
	}

	public function __set($key, $value) {

		if ($this->_data === null) {
			$this->_data = array ();
		}

		$this->_data[$key] = $value;
	}

	public function save() {

		if (empty ($this->_data)) {
			return;
		}

		if (Aitsu_Db :: fetchOne('' .
			'select count(*) from information_schema.columns ' .
			'where ' .
			'	table_schema = :schema ' .
			'	and table_name = :tableName ' .
			'	and column_name = :columnName ', array (
				':schema' => Aitsu_Registry :: get()->config->database->params->dbname,
				':tableName' => Aitsu_Registry :: get()->config->database->params->tblprefix . 'acl_user',
				':columnName' => 'properties'
			)) == 0) {
			Aitsu_Db :: query('' .
			'alter table _acl_user add properties longtext not null');
		}

		if ($this->_id == null) {
			$this->_data['created'] = date('Y-m-d H:i:s');
		}

		$this->_id = Aitsu_Db :: put('_acl_user', 'userid', $this->_data);

		Aitsu_Db :: query('' .
		'delete from _acl_roles where userid = :id ', array (
			':id' => $this->_id
		));
		if (isset ($this->_data['roles']) && is_array($this->_data['roles'])) {
			foreach ($this->_data['roles'] as $role) {
				Aitsu_Db :: put('_acl_roles', null, array (
					'userid' => $this->_id,
					'roleid' => $role
				));
			}
		}

		return $this->_id;
	}

	public function remove() {

		Aitsu_Db :: query('delete from _acl_user where userid = :id', array (
			':id' => $this->_id
		));
	}

	public static function getByName($name = '%', $limit = 100, $offset = 0) {

		$return = array ();

		$results = Aitsu_Db :: fetchAll('' .
		'select * from _acl_user ' .
		'where ' .
		'	lastname like :name ' .
		'	or firstname like :name ' .
		'	or login like :name ' .
		'order by ' .
		'	lastname asc, ' .
		'	firstname asc ', array (
			':name' => $name
		));

		if (!$results) {
			return $return;
		}

		foreach ($results as $result) {
			$row = new self($result['userid']);
			$row->_data = $result;
			$return[] = $row;
		}

		return $return;
	}

	public static function getUsersWithRoles() {

		$return = array ();

		$results = Aitsu_Db :: fetchAll('' .
		'select ' .
		'	roles.userid, ' .
		'	rl.roleid, ' .
		'	rl.identifier ' .
		'from _acl_roles as roles ' .
		'left join _acl_role as rl on roles.roleid = rl.roleid');

		if (!$results) {
			return $return;
		}

		foreach ($results as $result) {
			$roles[$result['userid']][] = array (
				'roleid' => $result['roleid'],
				'identifier' => $result['identifier']
			);
		}

		$users = self :: getByName('%', 999999);

		foreach ($users as $user) {
			if (isset ($roles[$user->userid])) {
				$user->roles = $roles[$user->userid];
			}
			$return[] = $user;
		}

		return $return;
	}

	public function getData() {

		return $this->_data;
	}

	/**
	 * @since 2.1.0.0 - 23.12.2010
	 */
	public static function getStore($limit = null, $offset = null, $filters = null, $orders = null) {

		return Aitsu_Db :: filter('select *, concat(lastname, ", ", firstname) as user from _acl_user', $limit, $offset, $filters, $orders);
	}

	public static function isLoginUnique($userid, $login) {

		$userid = empty ($userid) ? 0 : $userid;

		$result = Aitsu_Db :: fetchOne('' .
		'select login from _acl_user ' .
		'where ' .
		'	login = :login ' .
		'	and userid != :userid', array (
			':userid' => $userid,
			':login' => $login
		));

		if (!$result) {
			return true;
		}

		return false;
	}
	
	public function getProperty($key) {
		
		if (!isset($this->_data['properties'])) {
			return null;
		}
		
		if ($this->_properties == null) {
			$this->_properties = array();
			preg_match_all('/^(.*?)(?:\\:|\\=)(.*)/m', $this->_data['properties'], $matches);
			for ($i = 0; $i < count($matches[0]); $i++) {
				$tKey = trim($matches[1][$i]);
				if (!empty($tKey)) {
					$this->_properties[$tKey] = trim($matches[2][$i]);
				}
			}
		}
		
		if (!isset($this->_properties[$key])) {
			return null;
		}
		
		return $this->_properties[$key];
	}
	
	public function getProperties() {
		// trigger population of prop array
		$this->getProperty( 'dummy' );
		return $this->_properties;
	}
}