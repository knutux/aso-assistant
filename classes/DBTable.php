<?php

class DBTable
    {
    protected $db;
    const TABLE_ASO_PRODUCTS = 'ASO Products';
    const TABLE_ASO_APPLICATIONS = 'ASO Apps';
    const TABLE_ASO_PLATFORMS = 'ASO Platforms';
    const TABLE_ASO_COUNTRY_PLATFORMS = 'ASO Country-Platforms';
    const TABLE_ASO_APPLICATION_METADATA = 'ASO Metadata';
    const TABLE_ASO_KEYWORDS = 'ASO Keywords';
    const TABLE_ASO_APP_KEYWORDS = 'ASO App Keywords';
    const TABLE_ASO_APP_KEYWORD_RANK = 'ASO App Keyword Rank';

    public function __construct ($accessManager)
        {
        $this->db = $accessManager->getDB ();
        $this->user = $accessManager->getUserName ();
        }

    public function getAccessManager ()
        {
        return $this->db->getAccessManager ();
        }

    public function escapeString ($val)
        {
        return $this->db->escapeString ($val);
        }

    protected function createDBColumn ($name, $type, $sqlType, $nullable, $default, $customUI = false)
        {
        $shortName = strtolower (preg_replace ('#\s+#ms', '_', $name));
        $notNull = $nullable ? "NULL" : "NOT NULL";
        if ($nullable && "timestamp" == $sqlType)
            $notNull = "";
        $obj = new StdClass ();
        $obj->sql = "`$name` $sqlType $notNull $default";
        $obj->name = $name;
        $obj->label = $name;
        $obj->alias = $shortName;
        $obj->type = $type;
        $obj->nullable = $nullable;
        $obj->values = false;
        $obj->customUI = $customUI;
        return $obj;
        }

    protected function createTextColumn ($name, $size = 255, $nullable = false)
        {
        return $this->createDBColumn ($name, 'text', "varchar($size) CHARACTER SET utf8 COLLATE utf8_general_ci", $nullable, false);
        }

    protected function createTextDropdownColumn ($name, $size, $values, $nullable = false)
        {
        $col = $this->createTextColumn ($name, $size, $nullable);
        $col->values = $values;
        return $col;
        }
    protected function createIntDropdownColumn ($name, $values, $nullable = false)
        {
        $col = $this->createIntColumn ($name, $nullable);
        $col->values = $values;
        return $col;
        }
    protected function createIntColumn ($name, $nullable = false, $default = false)
        {
        return $this->createDBColumn ($name, 'int', "integer", $nullable, empty ($default) ? false : "DEFAULT $default");
        }
    protected function createBlobColumn ($name, $nullable = false)
        {
        return $this->createDBColumn ($name, 'blob', "blob", $nullable, false);
        }
    protected function createDecimalColumn ($name, $nullable = false)
        {
        return $this->createDBColumn ($name, 'float', "DECIMAL(10, 2)", $nullable, false);
        }

    protected function createBoolColumn ($name, $nullable = false)
        {
        return $this->createDBColumn ($name, 'int', "int(1)", $nullable, false, 'checkbox');
        }

    protected function createTimestampColumn ($name, $nullable = false)
        {
        return $this->createDBColumn ($name, 'text', "timestamp", $nullable, $nullable ? "NULL" : false);
        }

    protected function createDateColumn ($name, $nullable = false)
        {
        return $this->createDBColumn ($name, 'text', "date", $nullable, false);
        }

    protected function createForeignKeyColumn ($name, $foreignTable, $relatedLabelColumn, $nullable = false)
        {
        $col = $this->createIntColumn ($name, $nullable);
        $col->label = $relatedLabelColumn;
        $col->foreignTable = $foreignTable;
        $col->foreignTableId = $col->name;
        $col->foreignTableLabel = $relatedLabelColumn;
        $col->values = 'selectDropdownValues';
        return $col;
        }

    public function selectDropdownValues ($column)
        {
        $sql = "SELECT `{$column->foreignTableId}`, `{$column->foreignTableLabel}` FROM `{$column->foreignTable}`";
        $rows = $this->db->executeSelect ($column->foreignTable, $sql);

        if (false === $rows)
            return $this->db->getLastError ();

        if (empty ($rows))
            return array ();

        $result = array ();
        foreach ($rows as $row)
            {
            $result[$row[$column->foreignTableId]] = $row[$column->foreignTableLabel];
            }
        return $result;
        }

    public function getTableColumns ($tableName, &$idColumn = NULL)
        {
        switch ($tableName)
            {

            case self::TABLE_ASO_APPLICATIONS:
                $idColumn = "App ID";
                return array
                    (
                    $this->createForeignKeyColumn ('Country-Platform ID',
                                                   self::TABLE_ASO_COUNTRY_PLATFORMS,
                                                   'Platform'),
                    $this->createForeignKeyColumn ('Product ID',
                                                   self::TABLE_ASO_PRODUCTS,
                                                   'Product'),
                    $this->createIntColumn ('Weight Modifier'),
                    $this->createForeignKeyColumn ('Latest Metadata ID',
                                                   self::TABLE_ASO_APPLICATION_METADATA,
                                                   'Last Metadata', true),
                    $this->createDateColumn ('Sheduled Update'),
                    );

            case self::TABLE_ASO_APPLICATION_METADATA:
                $idColumn = "Metadata ID";
                return array
                    (
                    $this->createForeignKeyColumn ('App ID',
                                                   self::TABLE_ASO_COUNTRY_PLATFORMS,
                                                   'Application'),
                    $this->createDateColumn ('Active From'),
                    $this->createTextColumn ('Title', 80),
                    $this->createTextColumn ('Keywords', 100),
                    $this->createTextColumn ('Short Description', 80),
                    $this->createTextColumn ('Subtitle', 30),
                    $this->createTextColumn ('Description', 4000),
                    $this->createTextColumn ('Goal', 512, true),
                    $this->createTextColumn ('Result', 512, true),
                    );

            case self::TABLE_ASO_PLATFORMS:
                $idColumn = "Platform ID";
                return array
                    (
                    $this->createTextColumn ('Name', 32),
                    $this->createTextColumn ('Developer Name', 120),
                    $this->createIntColumn ('Max Title Length'), // 30 or 50
                    $this->createIntColumn ('Max Keywords Length'), // 100 or 0
                    $this->createIntColumn ('Max Subtitle Length'), // 30 or 0
                    $this->createIntColumn ('Max Short Description Length'), // 80 or 0
                    $this->createDecimalColumn ('Developer Name Weight'), // 3 for google play
                    $this->createDecimalColumn ('Title Weight'), // 6 for google play
                    $this->createDecimalColumn ('Short Description Weight'), // 2 for google play
                    $this->createDecimalColumn ('Subtitle Weight'), // 2 for app store, 0 for google play
                    $this->createDecimalColumn ('Description Weight'), // 1 for google play, 0 for app store
                    $this->createDecimalColumn ('Keywords Weight'), // 1 for app store, 0 for google play
                    );

            case self::TABLE_ASO_COUNTRY_PLATFORMS:
                $idColumn = "Country-Platform ID";
                return array
                    (
                    $this->createForeignKeyColumn ('Platform ID',
                                                   self::TABLE_ASO_PLATFORMS,
                                                   'Platform'),
                    $this->createTextColumn ('IETF Tag', 16),
                    $this->createTextColumn ('ISO Code', 3), // ISO-639-1 and ISO-639-2
                    $this->createTextColumn ('Name', 64),
                    $this->createIntColumn ('Weight'),
                    );

            case self::TABLE_ASO_KEYWORDS:
                $idColumn = "Keyword ID";
                return array
                    (
                    $this->createTextColumn ('ISO Code', 3), // ISO-639-1 and ISO-639-2
                    $this->createForeignKeyColumn ('Platform ID',
                                                   self::TABLE_ASO_PLATFORMS,
                                                   'Platform'),
                    $this->createTextColumn ('Keyword', 256),
                    $this->createTextColumn ('Notes', 256),
                    );

            case self::TABLE_ASO_APP_KEYWORDS:
                $idColumn = "App Keyword ID";
                return array
                    (
                    $this->createForeignKeyColumn ('App ID',
                                                   self::TABLE_ASO_KEYWORDS,
                                                   'Application'),
                    $this->createForeignKeyColumn ('Keyword ID',
                                                   self::TABLE_ASO_APPLICATIONS,
                                                   'Keyword'),
                    $this->createIntColumn ('Volume', true), // mobile action?
                    $this->createIntColumn ('Chance', true), // mobile action?
                    $this->createIntColumn ('Total Apps', true), // mobile action?
                    $this->createIntColumn ('Priority', true), // 1-Imediate/3-Urgent/10-FutureHigh/20-FutureMedium/30-FutureLow/100-Reserve/1000-Irrelevant
                    $this->createForeignKeyColumn ('Latest Rank ID',
                                                   self::TABLE_ASO_APP_KEYWORD_RANK,
                                                   'Rank', true),
                    );

            case self::TABLE_ASO_APP_KEYWORD_RANK:
                $idColumn = "Rank ID";
                return array
                    (
                    $this->createForeignKeyColumn ('App Keyword ID',
                                                   self::TABLE_ASO_APP_KEYWORDS,
                                                   'Keyword'),
                    $this->createDateColumn ('Date'),
                    $this->createIntColumn ('Rank', true),
                    );

            case self::TABLE_ASO_PRODUCTS:
                $idColumn = "Product ID";
                return array
                    (
                    $this->createTextColumn ('Name', 64),
                    $this->createTextColumn ('BundleID', 128),
                    $this->createIntColumn ('AppStoreID'),
                    );

            }
        return false;
        }

    protected function getTableColumnSql ($tableName, &$idColumn)
        {
        $columns = $this->getTableColumns ($tableName, $idColumn);
        if (empty ($columns))
            return false;

        $result = array ();
        foreach ($columns as $col)
            {
            $result[] = $col->sql;
            }
        return $result;
        }
       
    public function ensureTable ($tableName, &$error)
        {
        $tableBaseName = $tableName;
        $parts = preg_split ('#:#', $tableName);
        if (count($parts) > 1)
            $tableBaseName = $parts[0];

        $relatedTables = array ();
        $columns = $this->getTableColumnSql ($tableBaseName, $idColumn);
        if (empty ($columns))
            {
            $error = "Table name $tableName not recognized";
            return false;
            }
            
        switch ($tableBaseName)
            {
            case self::TABLE_ASO_APPLICATIONS:
                $indexes = array ("UNIQUE(`Product ID`, `Country-Platform ID`)");
                $relatedTables[] = self::TABLE_ASO_PRODUCTS;
                $relatedTables[] = self::TABLE_ASO_COUNTRY_PLATFORMS;
                $relatedTables[] = self::TABLE_ASO_APPLICATION_METADATA;
                break;
            case self::TABLE_ASO_APPLICATION_METADATA:
                $indexes = array ("UNIQUE(`App ID`,`Active From`)");
                //$relatedTables[] = self::TABLE_ASO_APPLICATIONS;
                break;
            case self::TABLE_ASO_KEYWORDS:
                $indexes = array ("UNIQUE(`ISO Code`,`Keyword`)");
                $relatedTables[] = self::TABLE_ASO_APP_KEYWORDS;
                break;
            case self::TABLE_ASO_APP_KEYWORDS:
                $indexes = array ("UNIQUE(`App ID`,`Keyword ID`)");
                $relatedTables[] = self::TABLE_ASO_APP_KEYWORD_RANK;
                break;
            case self::TABLE_ASO_APP_KEYWORD_RANK:
                $indexes = array ("UNIQUE(`App Keyword ID`,`Date`)");
                //$relatedTables[] = self::TABLE_ASO_APPLICATIONS;
                break;
            case self::TABLE_ASO_PRODUCTS:
                $indexes = array ("UNIQUE(`Name`)", "UNIQUE(`BundleID`)", "UNIQUE(`AppStoreID`)");
                break;
            case self::TABLE_ASO_PLATFORMS:
                $indexes = array ("UNIQUE(`Name`)");
                break;
            case self::TABLE_ASO_COUNTRY_PLATFORMS:
                $indexes = array ("UNIQUE(`Platform ID`, `Name`)", "UNIQUE(`Platform ID`, `IETF Tag`)", "INDEX (`ISO Code`)",);
                $relatedTables[] = self::TABLE_ASO_PLATFORMS;
                break;

            default:
                $indexes = array ();
                break;
            }

        foreach ($relatedTables as $table)
            {
            if (!$this->ensureTable($table, $error))
                {
                return false;
                }
            }

        $accessManager = $this->db->getAccessManager ();
        if (!$accessManager->ensureCustomTable ($tableName, $idColumn, $columns, $indexes, $error, $created))
            {
            return false;
            }

        $initialValues = NULL;
        switch ($tableName)
            {
            case self::TABLE_ASO_PRODUCTS:
                $initialValues[] = <<<EOT
(`Name`, `BundleID`, `AppStoreID`)
VALUES
('2P Damier', 'com.judrilenta.checkersfor2', 1238682209)
EOT;
                break;
            case self::TABLE_ASO_PLATFORMS:
                $initialValues[] = <<<EOT
(`Name`, `Developer Name`, `Max Title Length`, `Max Keywords Length`, `Max Subtitle Length`, `Max Short Description Length`,
         `Developer Name Weight`, `Title Weight`, `Short Description Weight`, `Subtitle Weight`, `Description Weight`, `Keywords Weight`)
VALUES
('Android', 'Judri Lenta - 10x10 checkers game', 50, 0, 0, 80, 3, 6, 2, 0, 1, 0),
('iPhone', 'Andrius Ramanauskas', 50, 100, 30, 0, 3, 3, 0, 2, 0, 1),
('iPad', 'Andrius Ramanauskas', 50, 100, 30, 0, 3, 3, 0, 2, 0, 1)
EOT;
                break;
            default:
                break;
            }

        $error = NULL;
        if (!$created || empty ($initialValues))
            return true;

        foreach ($initialValues as $sql)
            {
            if (false === $this->db->executeInsert ($tableName, $sql))
                {
                $error = $this->db->getLastError ().' --- '.$sql;
                return false;
                }
            }

        return true;
        }

    public function executeSelect ($tableName, $sql, &$error)
        {
        $rows = $this->db->executeSelect ($tableName, $sql);
        if (false === $rows && 1146 == $this->db->getLastErrorId ())
            {
            // create table
            if (!$this->ensureTable ($tableName, $error))
                return false;

            $rows = $this->db->executeSelect ($tableName, $sql);
            }

        if (false === $rows)
            {
            $error = $this->db->getLastError();
            }
        else
            $error = NULL;

        return $rows;
        }

    public function executeInsertWithParam ($tableName, $columnsAndValues, $val, &$error)
        {
        $ret = $this->db->executeInsertWithParam ($tableName, $sql, $blob);
        if (false === $ret)
            $error = $this->db->getLastError ($columnsAndValues);
        else
            $error = NULL;

        return $ret;
        }

    public function executeUpdateWithParam ($tableName, $setAndWhere, $val, &$error)
        {
        $ret = $this->db->executeUpdateWithParam ($tableName, $setAndWhere, $val);
        if (false === $ret)
            $error = $this->db->getLastError ($setAndWhere);
        else
            $error = NULL;

        return $ret;
        }

    public function executeInsert ($tableName, $columnsAndValues, $returnId, &$error)
        {
        $ret = $this->db->executeInsert ($tableName, $columnsAndValues, $returnId);
        if (false === $ret && 1146 == $this->db->getLastErrorId ())
            {
            // create table
            if (!$this->ensureTable ($tableName, $error))
                return false;

            $ret = $this->db->executeInsert ($tableName, $columnsAndValues, $returnId);
            }

        if (false === $ret)
            $error = $this->db->getLastError ($columnsAndValues);
        else
            $error = NULL;

        return $ret;
        }

    public function executeUpdate ($tableName, $columnsAndValues, &$error)
        {
        $ret = $this->db->executeUpdate ($tableName, $columnsAndValues, $error);
        if (false === $ret && 1146 == $this->db->getLastErrorId ())
            {
            // create table
            if (!$this->ensureTable ($tableName, $error))
                return false;

            $ret = $this->db->executeUpdate ($tableName, $columnsAndValues, $error);
            }

        if (false === $ret)
            $error = $this->db->getLastError ($columnsAndValues);
        else
            $error = NULL;

        return $ret;
        }

    public function verifyColumnValue ($col, $value)
        {
        if (!$col->nullable && NULL === $value)
            {
            return "Value of the {$col->label} field not set.";
            }
        if ("int" == $col->type || "dropdown" == $col->type)
            {
            if (!preg_match ('#[0-9]*#', $value))
                return "Invalid value given for the {$col->label} field.";
            }

        return NULL;
        }

    protected function preprocessNewInstance ($tableName, &$request, &$error)
        {
        $error = NULL;

        return true;
        }

    protected function preprocessModifiedInstance ($tableName, $id, &$request, &$error)
        {
        $error = NULL;
        return true;
        }

    protected function postprocessNewInstance ($tableName, $id, $request, &$error)
        {
        $error = NULL;

        return true;
        }

    public function createInstance ($tableName, $request, &$error, &$isDuplicateKey = NULL)
        {
        $columns = $this->getTableColumns ($tableName);
        if (empty ($columns))
            {
            $error = "Table $tableName not found";
            return false;
            }

        if (!$this->preprocessNewInstance ($tableName, $request, $error))
            return false === $error;

        $columnNames = array ();
        $valueList = array ();
        foreach ($columns as $col)
            {
            $val = !isset ($request[$col->alias]) ? NULL : trim ($request[$col->alias]);
            if ("" === $val)
                $val = NULL;

            $error = $this->verifyColumnValue ($col, $val);
            if ($error)
                return false;

            $columnNames[] = "`{$col->name}`";
            if (NULL === $val)
                $valueList[] = "NULL";
            else
                {
                if ("text" == $col->type)
                    $valueList[] = "'".$this->db->escapeString ($val)."'";
                else
                    $valueList[] = $val;
                }
            }

        $sql = "(".implode (',', $columnNames).', `Updated On`) VALUES ('.implode (',', $valueList).', NOW())';
        $id = $this->db->executeInsert ($tableName, $sql, true);
        if (false === $id)
            {
            $error = $this->db->getLastError ();
            $isDuplicateKey = 1062 == $this->db->getLastErrorId ();
            }
        else
            {
            $error = NULL;
            if (!$this->postprocessNewInstance ($tableName, $id, $request, $error))
                return false;
            }

        return $id;
        }

    public function modifyInstance ($tableName, $id, $request, &$error)
        {
        $columns = $this->getTableColumns ($tableName, $idColumn);
        if (empty ($columns))
            {
            $error = "Table $tableName not found";
            return false;
            }

        $columnsToUpdate = array ();
        foreach ($columns as $col)
            {
            if (!isset ($request[$col->alias]))
                continue;

            $val = !isset ($request[$col->alias]) ? NULL : trim ($request[$col->alias]);
            if ("" === $val)
                $val = NULL;

            $error = $this->verifyColumnValue ($col, $val);
            if ($error)
                return false;

            if (NULL === $val)
                $value = "NULL";
            else
                {
                if ("text" == $col->type)
                    $value = "'".$this->db->escapeString ($val)."'";
                else
                    $value = $val;
                }
            $columnsToUpdate[] = "`{$col->name}`=$value";
            }

        if (!$this->preprocessModifiedInstance ($tableName, $id, $request, $error))
            return false === $error;

        $columnsToUpdate[] = "`Updated On`=NOW()";
        $sql = "SET ".implode (',', $columnsToUpdate)." WHERE `$idColumn`=$id";
        if (false === $this->db->executeUpdate ($tableName, $sql))
            {
            $error = $this->db->getLastError ();
            return false;
            }
        else
            $error = NULL;

        return true;
        }


    public function selectASOApplications (&$error)
        {
        $tableName = self::TABLE_ASO_APPLICATIONS;
        $tableCountries = self::TABLE_ASO_COUNTRY_PLATFORMS;
        $tablePlatforms = self::TABLE_ASO_PLATFORMS;
        $tableProducts = self::TABLE_ASO_PRODUCTS;
        $tableKeywords = self::TABLE_ASO_KEYWORDS;
        $tableAppKeywords = self::TABLE_ASO_APP_KEYWORDS;
        $tableRanks = self::TABLE_ASO_APP_KEYWORD_RANK;
        $tableMetadata = self::TABLE_ASO_APPLICATION_METADATA;
        $sql = <<<EOT
SELECT app.`App ID`, app.`Country-Platform ID`, app.`Product ID`, app.`Weight Modifier`, app.`Latest Metadata ID`, app.`Sheduled Update`,
       ctr.`ISO Code`, ctr.`Name` `Country-Language`, ctr.`Weight`,
       prod.`Name` `Product`, plat.`Name` `Platform`, plat.`Platform ID`,
       SUM(kwd.`Keyword ID` IS NOT NULL) `Keyword Count`, SUM(akwd.`Volume` IS NOT NULL) `Keywords With Info`,
       MAX(rank.`Date`) `Keywords Updated`, app.`Active From` `Metadata Updated`
  FROM (SELECT app1.`App ID`, app1.`Country-Platform ID`, app1.`Product ID`, app1.`Weight Modifier`, app1.`Latest Metadata ID`, app1.`Sheduled Update`,
               MAX(mt.`Active From`) `Active From`
         FROM `$tableName` app1
         LEFT OUTER JOIN `$tableMetadata` mt ON app1.`App ID`=mt.`App ID` AND mt.`Active From` < NOW()
        GROUP BY app1.`App ID`, app1.`Country-Platform ID`, app1.`Product ID`, app1.`Weight Modifier`, app1.`Latest Metadata ID`, app1.`Sheduled Update`
         ) as `app`
  LEFT OUTER JOIN `$tableCountries` ctr ON app.`Country-Platform ID`=ctr.`Country-Platform ID`
  LEFT OUTER JOIN `$tableProducts` prod ON app.`Product ID`=prod.`Product ID`
  LEFT OUTER JOIN `$tablePlatforms` plat ON ctr.`Platform ID`=plat.`Platform ID`
  LEFT OUTER JOIN `$tableKeywords` kwd ON ctr.`ISO Code`=kwd.`ISO Code` AND prod.`Product ID`=kwd.`Product ID`
  LEFT OUTER JOIN `$tableAppKeywords` akwd ON kwd.`Keyword ID`=akwd.`Keyword ID` AND app.`App ID`=akwd.`App ID`
  LEFT OUTER JOIN `$tableRanks` rank ON akwd.`Latest Rank ID`=rank.`Rank ID`
 GROUP BY app.`App ID`, app.`Country-Platform ID`, app.`Product ID`, app.`Weight Modifier`, app.`Latest Metadata ID`, app.`Sheduled Update`,
          ctr.`ISO Code`, ctr.`Name`, ctr.`Weight`, prod.`Name`, plat.`Name`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectASOKeywords ($unitId, $languageISO, &$error)
        {
        $tableName = self::TABLE_ASO_KEYWORDS;
        $tableAppKeywords = self::TABLE_ASO_APP_KEYWORDS;
        $tableRank = self::TABLE_ASO_APP_KEYWORD_RANK;
        $tableApps = self::TABLE_ASO_APPLICATIONS;
        $sql = <<<EOT
SELECT kwd.`Keyword ID`, kwd.`Keyword`, kwd.`Notes`, IFNULL(kwd.`Updated On`,kwd.`Created On`) `Keyword Updated On`,
       IFNULL(akwd.`Updated On`,akwd.`Created On`) `App Keyword Updated On`,
       akwd.`App Keyword ID`, akwd.`Volume`, akwd.`Chance`, akwd.`Total Apps`,
       rank.`Rank`, rank.`Date` `Rank Date`, rank2.`Rank` `Old Rank`, rank2.`Date` `Old Rank Date`, akwd.`Priority`,
       MAX(rankMinMax.`Rank`) `Max Rank`, MIN(rankMinMax.`Rank`) `Min Rank`
  FROM `$tableName` kwd
  INNER JOIN `$tableApps` aps ON kwd.`Product ID`=aps.`Product ID` AND aps.`App ID`=$unitId
  LEFT OUTER JOIN `$tableAppKeywords` akwd ON kwd.`Keyword ID`=akwd.`Keyword ID` AND akwd.`App ID`=$unitId
  LEFT OUTER JOIN `$tableRank` rank ON akwd.`Latest Rank ID`=rank.`Rank ID`
  LEFT OUTER JOIN `$tableRank` rankMinMax ON akwd.`App Keyword ID`=rankMinMax.`App Keyword ID`
  LEFT OUTER JOIN `$tableRank` rank2 ON akwd.`App Keyword ID`=rank2.`App Keyword ID`
                                    AND rank2.`Date` = (SELECT MAX(`Date`) FROM `$tableRank` r WHERE akwd.`App Keyword ID`=r.`App Keyword ID` AND r.`Date` < rank.`Date` )
    WHERE `ISO Code` LIKE '$languageISO'
   GROUP BY kwd.`Keyword ID`, kwd.`Keyword`, kwd.`Notes`, kwd.`Updated On`, kwd.`Created On`,
            akwd.`Updated On`,akwd.`Created On`, akwd.`App Keyword ID`, akwd.`Volume`, akwd.`Chance`, akwd.`Total Apps`,
            rank.`Rank`, rank.`Date`, rank2.`Rank`, rank2.`Date`, akwd.`Priority`
EOT;

        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectRelatedASOKeywords ($unitId, $languageISO, &$error)
        {
        $tableName = self::TABLE_ASO_KEYWORDS;
        $tableAppKeywords = self::TABLE_ASO_APP_KEYWORDS;
        $tableRank = self::TABLE_ASO_APP_KEYWORD_RANK;
        $tableApps = self::TABLE_ASO_APPLICATIONS;
        $sql = <<<EOT
SELECT kwd.`Keyword`, MAX(kwd.`Notes`),
       COUNT(akwd.`App Keyword ID`) `cnt`, AVG(akwd.`Volume`) `Volume`, AVG(akwd.`Chance`) `Chance`, AVG(akwd.`Total Apps`) `Total Apps`
  FROM `$tableName` kwd
  INNER JOIN `$tableAppKeywords` akwd ON kwd.`Keyword ID`=akwd.`Keyword ID` AND NOT akwd.`App ID`=$unitId AND `Volume` IS NOT NULL AND `Chance` IS NOT NULL
    WHERE `ISO Code` LIKE '$languageISO'
  GROUP BY kwd.`Keyword`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);

        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectASOMetadataHistory ($appId, &$error)
        {
        $tableMetadata = self::TABLE_ASO_APPLICATION_METADATA;
        $sql = <<<EOT
SELECT mt.`Title`, mt.`Keywords`, mt.`Subtitle`, mt.`Short Description`, mt.`Description`, mt.`Active From`,
       mt.`Metadata ID`, mt.`Goal`, mt.`Result`
  FROM `$tableMetadata` mt
 WHERE mt.`App ID` = $appId
 ORDER BY mt.`Active From` DESC
 LIMIT 7
EOT;
        $rows = $this->executeSelect ($tableMetadata, $sql, $error);
        return $rows;
        }

    public function selectASOApplication ($appId, &$error)
        {
        $tableName = self::TABLE_ASO_APPLICATIONS;
        $tableCountries = self::TABLE_ASO_COUNTRY_PLATFORMS;
        $tablePlatforms = self::TABLE_ASO_PLATFORMS;
        $tableProducts = self::TABLE_ASO_PRODUCTS;
        $tableMetadata = self::TABLE_ASO_APPLICATION_METADATA;
        $sql = <<<EOT
SELECT app.`App ID`, app.`Country-Platform ID`, app.`Product ID`, app.`Weight Modifier`, app.`Latest Metadata ID`, app.`Sheduled Update`,
       ctr.`ISO Code`, ctr.`IETF Tag`, ctr.`Name` `Country-Language`, ctr.`Weight`, mt.`Goal`, mt2.`Result`,
       prod.`Name` `Product`, plat.`Name` `Platform`, prod.`AppStoreID`, prod.`BundleID`,
       mt.`Title`, mt.`Keywords`, mt.`Subtitle`, mt.`Short Description`, mt.`Description`, mt.`Active From`,
       plat.`Developer Name Weight`, plat.`Title Weight`, plat.`Short Description Weight`, plat.`Subtitle Weight`, plat.`Description Weight`, plat.`Keywords Weight`,
       plat.`Developer Name`, plat.`Max Title Length`, plat.`Max Keywords Length`, plat.`Max Subtitle Length`,
       plat.`Max Short Description Length`, 4000 as `Max Description Length`,
       mt2.`Title` `Current Title`, mt2.`Keywords` `Current Keywords`, mt2.`Subtitle` `Current Subtitle`,
       mt2.`Short Description` `Current Short Description`, mt2.`Description` `Current Description`, mt2.`Active From`  `Current Active From`,
       mt2.`Metadata ID` `Old ID`
  FROM `$tableName` app
  LEFT OUTER JOIN `$tableCountries` ctr ON app.`Country-Platform ID`=ctr.`Country-Platform ID`
  LEFT OUTER JOIN `$tableProducts` prod ON app.`Product ID`=prod.`Product ID`
  LEFT OUTER JOIN `$tablePlatforms` plat ON ctr.`Platform ID`=plat.`Platform ID`
  LEFT OUTER JOIN `$tableMetadata` mt ON app.`Latest Metadata ID`=mt.`Metadata ID`
  LEFT OUTER JOIN `$tableMetadata` mt2 ON app.`App ID`=mt2.`App ID` AND mt2.`Active From` < CURDATE()
 WHERE app.`App ID` = $appId
 ORDER BY mt2.`Active From` DESC
 LIMIT 1
EOT;
//ini_set('xdebug.var_display_max_data', 2056);var_dump($sql);
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            {
            if (empty ($error))
                $error = "Not found";
            return $rows;
            }
        return $rows[0];
        }

    public function selectASOProducts (&$error)
        {
        $tableName = self::TABLE_ASO_PRODUCTS;
        $sql = <<<EOT
SELECT `Product ID`, `Name`
  FROM `$tableName`
 ORDER BY `Name`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectASOPlatforms (&$error)
        {
        $tableName = self::TABLE_ASO_PLATFORMS;
        $sql = <<<EOT
SELECT `Platform ID`, `Name`
  FROM `$tableName`
 ORDER BY `Name`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }


    }
