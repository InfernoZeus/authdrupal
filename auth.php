<?php
/**
 * DokuWiki Plugin authdrupal (Auth Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Ben Fox-Moore <ben.foxmoore@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class auth_plugin_authdrupal extends DokuWiki_Auth_Plugin {


    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(); // for compatibility

        $this->cando['addUser']     = false; // can Users be created?
        $this->cando['delUser']     = false; // can Users be deleted?
        $this->cando['modLogin']    = false; // can login names be changed?
        $this->cando['modPass']     = false; // can passwords be changed?
        $this->cando['modName']     = false; // can real names be changed?
        $this->cando['modMail']     = false; // can emails be changed?
        $this->cando['modGroups']   = false; // can groups be changed?
        $this->cando['getUsers']    = false; // can a (filtered) list of users be retrieved?
        $this->cando['getUserCount']= false; // can the number of users be retrieved?
        $this->cando['getGroups']   = false; // can a list of available groups be retrieved?
        $this->cando['external']    = false; // does the module do external auth checking?
        $this->cando['logout']      = true; // can the user logout again? (eg. not possible with HTTP auth)

        if(!$this->getConf('drupalRoot')) {
            msg("AuthDrupal err: insufficient configuration - drupalRoot not specified", -1, __LINE__, __FILE__);
            $this->success = false;
            return;
        } else {
            $path = $this->getConf('drupalRoot');
            if (strpos($path, "/") != 0) {
                $dokuwiki_root = realpath(dirname(__FILE__) . "/../../../");
                $this->drupal_root = realpath($dokuwiki_root .'/'. $path);
            } else {
                $this->drupal_root = realpath($this->getConf('drupalRoot'));
            }
        }

        $password_file = $this->drupal_root.'/includes/password.inc';
        if (is_readable($password_file)) {
            require_once($password_file);
            if(!function_exists(_password_crypt)) {
                msg ("AuthDrupal err: Error accessing Drupal's password hashing functions. Drupal 7+ is required.", -1, __LINE__, __FILE__);
                $this->success = false;
                return;
            }
        } else {
            msg ("AuthDrupal err: Drupal 7+ installation not found. Please check your configuration.", -1, __LINE__, __FILE__);
            $this->success = false;
            return;
        }

        if(!function_exists('mysql_connect')) {
            msg("AuthDrupal err: PHP MySQL extension not found.", -1, __LINE__, __FILE__);
            $this->success = false;
            return;
        }

        $settings_file = $this->drupal_root.'/sites/default/settings.php';
        require_once($settings_file);
        $default_db = $databases['default']['default'];

        if ($default_db['driver'] == 'mysql') {
            $this->db_connector = $this->_loadMySQLConnector($default_db);
        } else if ($default_db['driver'] == 'pgsql') {
            $this->db_connector = $this->_loadPgSQLConnector($default_db);
        } else {
            msg("AuthDrupal err: Only MySQL/PgSQL databases are supported currently", -1, __LINE__, __FILE__);
            $this->success = false;
            return;
        }

        if ($this->db_connector->canConnectToDatabase()) {
        } else {
          msg("AuthDrupal err: Database Connection Failed. Please check your configuration.", -1, __LINE__, __FILE__);
          $this->success = false;
          return;
        }

        if($this->getConf('debug') >= 2) {
            $candoDebug = '';
            foreach($this->cando as $cd => $value) {
                if($value) { $value = 'yes'; } else { $value = 'no'; }
                $candoDebug .= $cd . ": " . $value . " | ";
            }
            msg("authdrupal cando: " . $candoDebug, 0, __LINE__, __FILE__);
        }

        $this->success = true;
    }

    protected function _loadMySQLConnector($config) {
        require_once('mysqlConnector.php');
        $conf['server'] = $config['host'];
        $conf['user'] = $config['username'];
        $conf['password'] = $config['password'];
        $conf['database'] = $config['database'];
        $conf['debug'] = $this->getConf('debug');
        $mysqlConnector = new auth_plugin_authdrupal_mysqlconnector($conf);
        return $mysqlConnector;
    }

    protected function _loadPgSQLConnector($config) {
        require_once('pgsqlConnector.php');
        $conf['server'] = $config['host'];
        $conf['user'] = $config['username'];
        $conf['password'] = $config['password'];
        $conf['database'] = $config['database'];
        $conf['debug'] = $this->getConf('debug');
        $pgsqlConnector = new auth_plugin_authdrupal_pgsqlconnector($conf);
        return $pgsqlConnector;
    }

    protected function _findPasswordHash($username) {
        return $this->db_connector->findPasswordHash($username);
    }

    protected function _hashPassword($password, $hashedpw) {
        $hash = _password_crypt('sha512', $password, $hashedpw);
        return $hash;
    }


    /**
     * Check user+password
     *
     * May be ommited if trustExternal is used.
     *
     * @param   string $user the user name
     * @param   string $pass the clear text password
     * @return  bool
     */
    public function checkPass($user, $pass) {
        $stored_hash = $this->_findPasswordHash($user);
        $hash = $this->_hashPassword($pass, $stored_hash);

        $result = $stored_hash == $hash && $hash;
        // $stored_hash is set to false if the username does not exit, which causes $hash to
        // also be false. Without the additional check, $result would be true in that case.
        return $result;
    }




    /**
     * Log off the current user [ OPTIONAL ]
     */
    //public function logOff() {
    //}

    /**
     * Do all authentication [ OPTIONAL ]
     *
     * @param   string  $user    Username
     * @param   string  $pass    Cleartext Password
     * @param   bool    $sticky  Cookie should not expire
     * @return  bool             true on successful auth
     */
    //public function trustExternal($user, $pass, $sticky = false) {
        /* some example:

        global $USERINFO;
        global $conf;
        $sticky ? $sticky = true : $sticky = false; //sanity check

        // do the checking here

        // set the globals if authed
        $USERINFO['name'] = 'FIXME';
        $USERINFO['mail'] = 'FIXME';
        $USERINFO['grps'] = array('FIXME');
        $_SERVER['REMOTE_USER'] = $user;
        $_SESSION[DOKU_COOKIE]['auth']['user'] = $user;
        $_SESSION[DOKU_COOKIE]['auth']['pass'] = $pass;
        $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
        return true;

        */
    //}

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @param   string $user the user name
     * @return  array containing user data or false
     */
    public function getUserData($user) {
        return $this->db_connector->getUserData($user);
    }

    /**
     * Bulk retrieval of user data [implement only where required/possible]
     *
     * Set getUsers capability when implemented
     *
     * @param   int   $start     index of first user to be returned
     * @param   int   $limit     max number of users to be returned
     * @param   array $filter    array of field/pattern pairs, null for no filter
     * @return  array list of userinfo (refer getUserData for internal userinfo details)
     */
    // public function retrieveUsers($start = 0, $limit = -1, $filter = null) {
    //     return $this->db_connector->retrieveUsers($start, $limit, $filter);
    // }

    /**
     * Return a count of the number of user which meet $filter criteria
     * [should be implemented whenever retrieveUsers is implemented]
     *
     * Set getUserCount capability when implemented
     *
     * @param  array $filter array of field/pattern pairs, empty array for no filter
     * @return int
     */
    // public function getUserCount($filter = array()) {
    //     return $this->db_connector->getUserCount($filter);
    // }

    /**
     * Define a group [implement only where required/possible]
     *
     * Set addGroup capability when implemented
     *
     * @param   string $group
     * @return  bool
     */
    //public function addGroup($group) {
        // FIXME implement
    //    return false;
    //}

    /**
     * Retrieve groups [implement only where required/possible]
     *
     * Set getGroups capability when implemented
     *
     * @param   int $start
     * @param   int $limit
     * @return  array
     */
    //public function retrieveGroups($start = 0, $limit = 0) {
        // FIXME implement
    //    return array();
    //}

    /**
     * Return case sensitivity of the backend
     *
     * When your backend is caseinsensitive (eg. you can login with USER and
     * user) then you need to overwrite this method and return false
     *
     * @return bool
     */
    public function isCaseSensitive() {
        return true;
    }

    /**
     * Sanitize a given username
     *
     * This function is applied to any user name that is given to
     * the backend and should also be applied to any user name within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce username restrictions.
     *
     * @param string $user username
     * @return string the cleaned username
     */
    public function cleanUser($user) {
        return $user;
    }

    /**
     * Sanitize a given groupname
     *
     * This function is applied to any groupname that is given to
     * the backend and should also be applied to any groupname within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce groupname restrictions.
     *
     * Groupnames are to be passed without a leading '@' here.
     *
     * @param  string $group groupname
     * @return string the cleaned groupname
     */
    public function cleanGroup($group) {
        return $group;
    }

    /**
     * Check Session Cache validity [implement only where required/possible]
     *
     * DokuWiki caches user info in the user's session for the timespan defined
     * in $conf['auth_security_timeout'].
     *
     * This makes sure slow authentication backends do not slow down DokuWiki.
     * This also means that changes to the user database will not be reflected
     * on currently logged in users.
     *
     * To accommodate for this, the user manager plugin will touch a reference
     * file whenever a change is submitted. This function compares the filetime
     * of this reference file with the time stored in the session.
     *
     * This reference file mechanism does not reflect changes done directly in
     * the backend's database through other means than the user manager plugin.
     *
     * Fast backends might want to return always false, to force rechecks on
     * each page load. Others might want to use their own checking here. If
     * unsure, do not override.
     *
     * @param  string $user - The username
     * @return bool
     */
    // public function useSessionCache($user) {

    // }
}

// vim:ts=4:sw=4:et:
