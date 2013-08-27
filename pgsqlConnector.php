<?php
/**
* DokuWiki Plugin authdrupal (Auth Component) - MySQL subclass
*
* @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
* @author  Ben Fox-Moore <ben.foxmoore@gmail.com>
*/

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

$dokuwiki_root = realpath(dirname(__FILE__) . "/../../../");
require_once($dokuwiki_root .'/lib/plugins/authpgsql/auth.php');

class auth_plugin_authdrupal_pgsqlconnector extends auth_plugin_authpgsql {

	/*
	* Requires 'server', 'user', 'password' and 'database' to be defined in the passed array
	*/
	public function __construct($initialConf) {
		$this->loadConfig();
		$this->_setInitialConfig($initialConf);
    	parent::__construct();
  	}

	protected function _setInitialConfig($initialConf) {
		global $conf;
		$defaults = $this->_readDrupalSettings();
		$plugin = $this->getPluginName();
		foreach ($defaults as $key => $value) {
			$conf['plugin'][$plugin][$key] = $value;
		}
		foreach ($initialConf as $key => $value) {
			$conf['plugin'][$plugin][$key] = $value;
		}
	}

	protected function _readDrupalSettings() {
        $path = DOKU_PLUGIN.$this->getDrupalName().'/conf/';
        $conf = array();

        if (@file_exists($path.'default.php')) {
            include($path.'default.php');
        }

        return $conf;
	}

	public function getDrupalName() {
		return "authdrupal";
	}

	public function getPluginName() {
		return "authpgsql";
	}

	public function canConnectToDatabase() {
		$result = $this->_openDB();
		$this->_closeDB();
		return $result;
	}

	public function findPasswordHash($username) {
		if ($this->_openDB()) {
			$sql = $this->getConf('findPasswordHash');
			$sql = str_replace('%{user}', $this->_escape($username), $sql);
			$result = $this->_queryDB($sql);
			if($result) {
				$hash = $result[0]['pass'];
				return $hash;
			} else {
				return false;
			}
			$this->_closeDB();
		} else {
			return false;
		}
	}

}
