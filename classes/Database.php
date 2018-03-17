<?php

require_once (__DIR__.'/../config.php');

class Database
    {
    private static $db = array ();
    private $handle = 0;
    private $accessManager = NULL;
    private $lastError = NULL;

    public function __destruct ()
        {
        $this->disconnect ();
        }
    
    static public function Instance ($accessManager, $host = false, $user = false, $password = false, $dbName = false, $autocommit = true)
        {
        $host = empty ($host) ? DB_HOST : $host;
        $user = empty ($user) ? DB_USER : $user;
        $password = false === $password ? DB_PASS : $password;
        $dbName = empty ($dbName) ? DB_NAME : $dbName;
        $userId = $accessManager->getUserId ();
        $key = "$userId|$host|$user|$dbName";
        if (empty (self::$db[$key]))
            {
            $db = new Database ();
            if ($db->connect($host, $user, $password, $dbName, $autocommit))
                {
                $db->accessManager = $accessManager;
                self::$db[$key] = $db;
                }
            else
                {
                self::$db[$key] = NULL;
                }
            }

        return self::$db[$key];
        }

    public function getAccessManager ()
        {
        return $this->accessManager;
        }

    public function connect ($host, $user, $password, $database, $autocommit = true)
        {
        $this->handle = new mysqli ($host, $user, $password, $database);
        if ($this->handle->connect_errno)
            {
            error_log ("Failed to connect to MySQL: (" . $this->handle->connect_errno . ") " . $this->handle->connect_error);
            }
        else
            {
            $this->handle->query("SET NAMES 'utf8mb4'");
            $this->handle->set_charset("utf8mb4");
            $this->handle->autocommit ($autocommit);
            return true;
            }

        return false;
        }
        
    public function autocommit ($autocommit)
        {
        if (!empty($this->handle))
            {
            $this->handle->autocommit($autocommit);
            }
        }

    public function disconnect ()
        {
        if ($this->handle)
            {
            $this->handle->close ();
            }
        }

    public function checkAccess ($tableName, $access)
        {
        if (empty ($this->accessManager) || !$this->accessManager->checkAccess ($tableName, $access))
            {
            // everyone should be able to create audit log records
            $exception = AccessManager::CREATE == $access && "audit_log" == $tableName;
            if (!$exception)
                {
                $this->lastError = "No $access access on $tableName";
                return false;
                }
            }

        $this->lastError = NULL;
        return true;
        }

    public function executeSelect ($tableName, $sql, $skipAccessCheck = false)
        {
        if (!$skipAccessCheck && !$this->checkAccess($tableName, AccessManager::READ))
            {
            return false;
            }

        if (VERBOSE_SQL)
            $startTime = microtime (true);

        $hResult = $this->handle->query ($sql);

        // if error occurred, we still need to free resources, but set result to "false"
        $resultSet = $this->logError () ? null : false;

        if ($hResult && $hResult->num_rows > 0)
            {
            while ($row = $hResult->fetch_array (MYSQLI_ASSOC))
                {
                $resultSet[] = $row;
                }
            }

        if ($hResult)
            {
            $hResult->free();
            }

        if (VERBOSE_SQL)
            {
            $endTime = microtime (true);
            echo "<div>Select <pre>$sql</pre> took ".($endTime-$startTime)." s.</div>";
            }

        return $resultSet;
        }

    /// retrieves single column from single row
    public function prepareSelect ($tableName, $sql)
        {
        if (!$this->checkAccess($tableName, AccessManager::READ))
            {
            return false;
            }

        $stmt = $this->handle->prepare ($sql);
        if (!$stmt)
            {
            $this->logError ($sql);
            return false;
            }

        return $stmt;
        }
    
    public function getInsertId ()
        {
        return $this->handle->insert_id;
        }

    public function executeInsert ($tableName, $columnsAndValues, $returnId = false, $skipAccessCheck = false)
        {
        if (!$skipAccessCheck && !$this->checkAccess ($tableName, AccessManager::CREATE))
            return false;

        $affected = $this->executeSQLNoCheck ("INSERT INTO `$tableName` $columnsAndValues", true);
        if (!$affected || !$returnId)
            return $affected;

        return $this->getInsertId ();
        }
        
    public function executeInsertWithParam ($tableName, $columnsAndValues, $val)
        {
        $returnId = true;
        if (!$this->checkAccess ($tableName, AccessManager::CREATE))
            return false;

        $sqlStatement = "INSERT INTO `$tableName` $columnsAndValues";
        $stmt = $this->handle->prepare ($sqlStatement);
        if (!$stmt)
            {
            $this->logError ($sqlStatement);
            return false;
            }

        $null = NULL;
        $stmt->bind_param ('b', $null);
        $stmt->send_long_data (0, $val);

        $ret = $stmt->execute ();
        if (!$ret)
            $this->lastError = $this->getLastError (NULL, $stmt);
        $stmt->close ();
        if (!$ret)
            return $ret;

        return $this->getInsertId ();
        }
        
    public function executeUpdateWithParam ($tableName, $setAndWhere, $val)
        {
        $returnId = true;
        if (!$this->checkAccess ($tableName, AccessManager::EDIT))
            return false;

        $sqlStatement = "UPDATE `$tableName` $setAndWhere";
        $stmt = $this->handle->prepare ($sqlStatement);
        if (!$stmt)
            {
            $this->logError ($sqlStatement);
            return false;
            }

        $null = NULL;
        $stmt->bind_param ('b', $null);
        $stmt->send_long_data (0, $val);

        $ret = $stmt->execute ();
        if (!$ret)
            $this->lastError = $this->getLastError (NULL, $stmt);
        $stmt->close ();
        if (!$ret)
            return $ret;

        return true;
        }
        
    public function executeUpdate ($tableName, $setAndWhere, $accessAlreadyChecked = false)
        {
        if (!$accessAlreadyChecked && !$this->checkAccess ($tableName, AccessManager::EDIT))
            return false;
//var_dump ("UPDATE `$tableName` $setAndWhere");
        return $this->executeSQLNoCheck ("UPDATE `$tableName` $setAndWhere", true);
        }

    public function executeDelete ($tableName, $condition)
        {
        if (!$this->checkAccess ($tableName, AccessManager::DELETE))
            return false;

        return $this->executeSQLNoCheck ("DELETE FROM `$tableName` $condition", true);
        }

    public function executeSQL ($sqlStatement, $returnAffected = false)
        {
        if (!$this->checkAccess ("All", "Any"))
            return false;

        return $this->executeSQLNoCheck ($sqlStatement, $returnAffected);
        }

    public function executeSQLNoCheck ($sqlStatement, $returnAffected = false)
        {
        if ($this->handle->query ($sqlStatement))
            {
            if ($returnAffected)
                return $this->handle->affected_rows;
            return true;
            }

        $this->logError ($sqlStatement);
        return false;
        }

    public function executeSQLNoCheckMulti ($sqlStatement, $returnAffected = false)
        {
        if ($this->handle->multi_query ($sqlStatement))
            {
            do
                {
                /* store first result set */
                if ($result = $this->handle->store_result())
                    {
                    $result->free();
                    }
                if (!$this->handle->more_results())
                    break;
                }
                while ($this->handle->next_result());

            if ($returnAffected)
                return $this->handle->affected_rows;
            return true;
            }

        $this->logError ($sqlStatement);
        return false;
        }

    public function escapeString ($string)
        {
        return $this->handle->real_escape_string ($string);
        }
       
    public function begin ()
        {
        return true;
        }
        
    public function rollback ()
        {
        return $this->handle->rollback ();
        }
        
    public function commit ()
        {
        return $this->handle->commit ();
        }
        
    public function getLastError ($sqlStatement = NULL, $handle = false)
        {
        if (false === $handle)
            {
            if (!empty ($this->lastError))
                return $this->lastError;
            $handle = $this->handle;
            }

        if (0 == $handle->errno)
            return NULL;

        switch ($handle->errno)
            {
            case 1062:
                return "Duplicate entry";

            default:
                return "MySql error. Please contact site administrators if error persists. ({$handle->errno}: {$handle->error}) : ".$sqlStatement;
            }
        }

    public function getLastErrorId ($handle = false)
        {
        if (false === $handle)
            $handle = $this->handle;

        return $handle->errno;
        }

    protected function logError ($sqlStatement = NULL)
        {
        $error = $this->getLastError ($sqlStatement);
        if (NULL == $error)
            return true;

        error_log ($error);
        return false;
        }
    }

