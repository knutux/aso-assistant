<?php

class AccessManager
    {
    protected $db;
    private $userId = 0;
    private $userName = "?";
    private $initialized = false;
    private $userAccess = NULL;

    const TABLE_STAFF = "Staff";
    const TABLE_GROUPS = "Groups";
    const TABLE_GROUP_MEMBERS = "Group Members";
    const TABLE_GROUP_ACCESS = "Group Access";
    
    const ALL_TABLES = "*";
    const TABLE_MYSQL_INFO = "MYSQL";

    const READ = "Read";
    const CREATE = "Create";
    const EDIT = "Edit";
    const DELETE = "Delete";
    
    static $singleton = false;
    
    public function __construct ()
        {
        $this->skipCheck = false;
        $this->db = Database::Instance ($this);
        self::$singleton = $this;
        }

    static public function getInstance ()
        {
        return self::$singleton;
        }
    public function checkSession ($returnPage = true, $debug = false)
        {
        if(!isset($_SESSION)) 
            { 
            $cacheExpire = 8 * 60;
            $cache_expire = session_cache_expire ($cacheExpire);
            session_start();
            if ($debug) var_dump("starting session", session_status(), $_SESSION);

            if (!empty ($_SESSION['qp_last_access']) && ((time() - $_SESSION['qp_last_access']) > 60*$cacheExpire ))
                {
                // session started more than 8 hours ago
                if ($debug) var_dump("session_regenerate_id");
                session_regenerate_id (true);    // change session ID for the current session an invalidate old session ID
                $_SESSION['qp_last_access'] = time();  // update creation time
                if ($debug) exit();
                return $returnPage ? "logout.php" : false;
                }
            }

        header('Cache-control: private');

        if (!empty ($_SESSION['qp_access']) && $_SESSION['qp_access'] == 'granted')
            {
            $_SESSION['qp_last_access'] = time();  // update creation time
            $this->userId = $_SESSION['userId'];
            $this->userName = $_SESSION['user'];
            $this->email = $_SESSION['email'];
            return $returnPage ? NULL : true;
            }

        return $returnPage ? "login.php" : false;
        }

    public function setConsoleUser ($email)
        {
        $tableName = self::TABLE_STAFF;
        $this->skipCheck = true;
        $rows = $this->db->executeSelect ($tableName, "SELECT `user ID`, `user`, `email` FROM `$tableName` WHERE email LIKE '$email' AND `Active`=1");
        $this->skipCheck = false;

        if (empty ($rows))
            return false;

        $this->userId = $rows[0]['user ID'];
        $this->userName = $rows[0]['user'];
        $this->email = $rows[0]['email'];
        return true;
        }

    public function login ($user, $password, &$error, $debug = false, $ensureTables = true)
        {
        $this->skipCheck = true;
        $tableName = self::TABLE_STAFF;
        $rows = $this->db->executeSelect ($tableName, "SELECT `user`, `email`, `user ID` FROM `$tableName` WHERE (user='$user' OR `email`='$user') AND `Active`=1 AND `password`=PASSWORD('$password')");
        $this->skipCheck = false;

        if (!empty ($rows))
            {
            $userId = $rows[0]['user ID'];
            $email = $rows[0]['email'];
            $user = $rows[0]['user'];
            $error = NULL;
            }
        else
            {
            $error = "User name or password invalid";
            if (false === $rows && $ensureTables && 1146 == $this->db->getLastErrorId ())
                {
                if (false == FIRST_ADMIN_PWD)
                    $error = "Users not initialized. Please contact site administrator to setup the database.";
                else
                    {
                    $r = $this->createFirstUser('admin', FIRST_ADMIN, FIRST_ADMIN_PWD);
                    if (empty ($r))
                        return $this->login ($user, $password, $error, $debug, false);
                    else
                        $error = $r;
                    }
                }
            return false;
            }

        if ($debug) var_dump ($_SESSION);

        if ($debug) { $started = session_status() == PHP_SESSION_ACTIVE; var_dump ("session started = '$started'"); }
        /* qp_access granted */
        header("Cache-control: private");
        $_SESSION["qp_access"] = "granted";
        $_SESSION["user"] = $user;
        $_SESSION["email"] = $email;
        $_SESSION["userId"] = $userId;
        $_SESSION['created'] = time(); // set the time the session was created
        $_SESSION['qp_last_access'] = time(); // set the time the session was last accessed
        if ($debug) var_dump ($_SESSION);
        return true;
        }

    public function checkAccess ($tableName, $accessToCheck)
        {
        if ($this->skipCheck || self::TABLE_MYSQL_INFO == $tableName)
            return true;

        if (!$this->initialized)
            {
            $this->skipCheck = true;
            $accessTable = self::TABLE_GROUP_ACCESS;
            $groupsTable = self::TABLE_GROUPS;
            $membersTable = self::TABLE_GROUP_MEMBERS;
            $sql = <<<EOT
SELECT `Table Name`, MAX(`Read`) as `Read`, MAX(`Create`) as `Create`, MAX(`Edit`) as `Edit`, MAX(`Delete`) as `Delete`
  FROM `$membersTable` m
  INNER JOIN `$groupsTable` g ON m.`Group ID`=g.`Group ID`
  INNER JOIN `$accessTable` a  ON a.`Group ID`=m.`Group ID`
  WHERE `user ID` = {$this->userId}
  GROUP BY `Table Name`
EOT;
            $this->userAccess = $this->db->executeSelect (self::TABLE_GROUP_ACCESS, $sql);
            if (false === $this->userAccess)
                error_log ("Error executing SQL '$sql'");
            if (empty ($this->userAccess))
                $this->userAccess = array ();

            $this->initialized = true;
            $this->skipCheck = false;
            }

        foreach ($this->userAccess as $row)
            {
            $rowTable = $row['Table Name'];
            if (!isset ($row[$accessToCheck]))
                return false;
            if ((0 == strcasecmp ($rowTable, $tableName) || self::ALL_TABLES == $rowTable) && $row[$accessToCheck] > 0)
                {
                return true;
                }
            }

        return false;
        }

    public function getUserId ()
        {
        return $this->userId;
        }

    public function getUserName ()
        {
        return empty ($this->userName) ? $this->email : $this->userName;
        }

    public function getDB ()
        {
        return $this->db;
        }

    public function createFirstUser ($groupName, $userName, $password)
        {
        $this->skipCheck = true;
        $this->db->autocommit (false);
        $ret = $this->createFirstUserNoSkip ($groupName, $userName, $password);
        if ($ret)
            {
            if (!$this->db->commit ())
                $ret = "Error committing the transaction";
            }
        else
            $this->db->rollback ();

        $this->db->autocommit (true);
        $this->skipCheck = false;
        return $ret;
        }

    private function createFirstUserNoSkip ($groupName, $userName, $password)
        {
        if (!$this->ensureTables ($exists))
            return "Could not create the permissions tables.";
        if ($exists)
            return "Some of the permission tables already exist.";

        $groupId = $this->db->executeInsert (self::TABLE_GROUPS, "(`Group Name`) VALUES ('$groupName')", true);
        if (empty ($groupId))
            return "Could not create the group.";

        if (!$this->db->executeInsert (self::TABLE_GROUP_ACCESS, "(`Group ID`, `Table Name`, `Read`, `Create`, `Edit`, `Delete`) VALUES ($groupId, '".self::ALL_TABLES."', 1, 1, 1, 1)", false))
            return "Could not insert entry into 'Group Access' table.";

        $userId = $this->db->executeInsert (self::TABLE_STAFF, "(`Firstname`, `Lastname`, `user`, `email`, `Password`, `Active`) VALUES ('Site', 'Admin', '$userName', '$userName', PASSWORD('$password'), 1)", true);
        if (empty ($userId))
            return "Could not create the user.";

        if (!$this->db->executeInsert (self::TABLE_GROUP_MEMBERS, "(`Group ID`, `user ID`) VALUES ($groupId, $userId)", false))
            return "Could not insert entry into 'Group Members' table.";

        $userName = API_USER;
        $userId = $this->db->executeInsert (self::TABLE_STAFF, "(`Firstname`, `Lastname`, `user`, `email`, `Password`, `Active`) VALUES ('API', 'user', '$userName', '$userName', 'no password', 1)", true);
        if (empty ($userId))
            return "Could not create the API user.";

        if (!$this->db->executeInsert (self::TABLE_GROUP_MEMBERS, "(`Group ID`, `user ID`) VALUES ($groupId, $userId)", false))
            return "Could not insert entry into 'Group Members' table.";

        $userName = 'script';
        $userId = $this->db->executeInsert (self::TABLE_STAFF, "(`Firstname`, `Lastname`, `user`, `email`, `Password`, `Active`) VALUES ('API', 'user', '$userName', '$userName', 'no password', 1)", true);
        if (empty ($userId))
            return "Could not create the API user.";

        if (!$this->db->executeInsert (self::TABLE_GROUP_MEMBERS, "(`Group ID`, `user ID`) VALUES ($groupId, $userId)", false))
            return "Could not insert entry into 'Group Members' table.";
        }

    private function ensureTables (&$exists)
        {
        $sqlStatements = array ();
        $tableName = self::TABLE_STAFF;
        $sqlStatements["Staff"] = 
<<<EOT
CREATE TABLE IF NOT EXISTS `$tableName` (
  `user ID` int(11) NOT NULL AUTO_INCREMENT,
  `Firstname` varchar(64) NOT NULL,
  `Lastname` varchar(128) NOT NULL,
  `user` varchar(64) NOT NULL,
  `email` varchar(128) NOT NULL,
  `password` varchar(128) NOT NULL,
  `Active` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`user ID`),
  INDEX (`user`, `password`),
  INDEX (`email`, `password`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8
EOT;
        $tableName = self::TABLE_GROUPS;
        $sqlStatements[$tableName] = 
<<<EOT
CREATE TABLE IF NOT EXISTS `$tableName` (
  `Group ID` int(11) NOT NULL AUTO_INCREMENT,
  `Group Name` varchar(255) NOT NULL,
  PRIMARY KEY (`Group ID`),
  UNIQUE KEY `name` (`Group Name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8
EOT;
        $tableName = self::TABLE_GROUP_MEMBERS;
        $sqlStatements[$tableName] = 
        $sqlStatements["Group Members"] = 
<<<EOT
CREATE TABLE IF NOT EXISTS `$tableName` (
  `Group ID` int(11) NOT NULL,
  `user ID` varchar(255) NOT NULL,
  PRIMARY KEY (`user ID`,`Group ID`),
  INDEX (`Group ID`)
) ENGINE=MyISAM
EOT;
        $tableName = self::TABLE_GROUP_ACCESS;
        $sqlStatements[$tableName] = 
<<<EOT
CREATE TABLE IF NOT EXISTS `$tableName` (
  `Group ID` int(11) NOT NULL,
  `Table Name` varchar(255) NOT NULL,
  `Read` tinyint DEFAULT 0,
  `Create` tinyint DEFAULT 0,
  `Edit` tinyint DEFAULT 0,
  `Delete` tinyint DEFAULT 0,
  PRIMARY KEY (`Group ID`, `Table Name`),
  INDEX (`Table Name`)
) ENGINE=MyISAM
EOT;
        $exists = true;
        foreach ($sqlStatements as $tableName => $sql)
            {
            $tableExists = $this->db->executeSelect (self::TABLE_MYSQL_INFO, "SHOW TABLES LIKE '$tableName'");
            if ("Staff" != $tableName && empty ($tableExists))
                $exists = false;
            if (empty ($tableExists))
                {
                $ret = $this->db->executeSQL ($sql);
                if (false === $ret)
                    {
                    error_log ("Failed to create the $tableName table");
                    return false;
                    }
                }
            }

        return true;
        }

    public function ensureCustomTable ($tableName, $idColumn, $columns, $indexes, &$error, &$created = NULL)
        {
        $created = false;
        $idColumnSql = empty ($idColumn) ? "" : "`$idColumn` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`$idColumn`)";
        if (is_array ($columns))
            $columns = implode (",\n", $columns);
        if (!empty ($indexes) && is_array ($indexes))
            $indexes = implode (",\n", $indexes);
        else
            $indexes = "";
        if (!empty ($indexes))
            $indexes = ",\n$indexes";
        $sql = 
<<<EOT
CREATE TABLE IF NOT EXISTS `$tableName` (
  $idColumnSql,
  `Created On` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  $columns,
  `Updated On` TIMESTAMP NULL DEFAULT NULL
  $indexes
) ENGINE=MyISAM  DEFAULT CHARSET=utf8
EOT;

        $tableExists = $this->db->executeSelect (self::TABLE_MYSQL_INFO, "SHOW TABLES LIKE '$tableName'", true);
        if (empty ($tableExists))
            {
            $ret = $this->db->executeSQLNoCheck ($sql);
            if (false === $ret)
                {
var_dump ($sql);
                $error = $this->db->getLastError ();
                return false;
                }
            $created = true;
            }

        $error = NULL;
        return true;
        }

    }
