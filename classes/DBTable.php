<?php

class DBTable
    {
    protected $db;
    const TABLE_CATEGORY = 'Categories';
    const TABLE_CATEGORY_LNG = 'Category Names';
    const TABLE_APPLICATION = 'Applications';
    const TABLE_LEVEL = 'Levels';
    const TABLE_LEVEL_LOGS = 'User Levels Solved';
    const TABLE_LAYOUT = 'Layouts';
    const TABLE_METADATA_VERSION = 'Metadata Version';
    //const TABLE_LEVEL_QUEUE = 'LevelsQueue';
    //const TABLE_LEVEL_FAILED = 'LevelsFailed';
    const TABLE_VERSION = 'Versions';
    const TABLE_GAME_LOGS_EXT = 'External Game Log';
    const TABLE_ASO_PRODUCTS = 'ASO Products';
    const TABLE_ASO_APPLICATIONS = 'ASO Apps';
    const TABLE_ASO_PLATFORMS = 'ASO Platforms';
    const TABLE_ASO_COUNTRY_PLATFORMS = 'ASO Country-Platforms';
    const TABLE_ASO_APPLICATION_METADATA = 'ASO Metadata';
    const TABLE_ASO_KEYWORDS = 'ASO Keywords';
    const TABLE_ASO_APP_KEYWORDS = 'ASO App Keywords';
    const TABLE_ASO_APP_KEYWORD_RANK = 'ASO App Keyword Rank';
    const TABLE_DC_LAYOUT = 'ASO DC Layouts';
    const TABLE_DC_LEVEL = 'ASO DC Levels';

    const TABLE_CHARTBOOST_REPORT_C = 'Chartboost Report - Campaigns';
    const TABLE_CHARTBOOST_CAMPAIGNS = 'Chartboost Campaigns';
    const TABLE_CHARTBOOST_BLACKLIST = 'Chartboost Blacklist';
    const TABLE_CHARTBOOST_STORE_INFO = 'Chartboost Store Stats';
    const TABLE_CHARTBOOST_COUNTRIES = 'Chartboost Countries';
    const TABLE_CHARTBOOST_REPORT_COUNTRIES = 'Chartboost Report - Countries';
    const TABLE_ADMOB_REPORT_COUNTRIES = 'Chartboost-AdMob Report - Countries';
  
    const DC_LAYOUT_AVAILABLE = 1;
    const DC_LAYOUT_USED = 10;
    const DC_LAYOUT_INVALID = -1;
    const DC_LEVEL_NEW = 0;
    const DC_LEVEL_VALIDATED = 1;
    const DC_LEVEL_INVALID = -1;
    const MIN_DC_COMPLEXITY = 150;
//    const TABLE_POSTFIX_NONE = '';
//    const TABLE_POSTFIX_QUEUE = 'Queue';
//    const TABLE_POSTFIX_FAILED = 'Failed';
   
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
            case self::TABLE_CATEGORY:
                $idColumn = "Category ID";
                return array
                    (
                    $this->createForeignKeyColumn ('Application ID',
                                                   self::TABLE_APPLICATION,
                                                   'Application'),
                    $this->createTextColumn ('Name', 256),
                    $this->createIntColumn ('Priority'),
                    );

            case self::TABLE_CATEGORY_LNG:
                $idColumn = "Translation ID";
                return array
                    (
                    $this->createForeignKeyColumn ('Category ID',
                                                   self::TABLE_APPLICATION,
                                                   'Category'),
                    $this->createTextColumn ('lng', 7),
                    $this->createTextColumn ('Name', 256),
                    );

            case self::TABLE_VERSION:
                $idColumn = "Version ID";
                return array
                    (
                    $this->createForeignKeyColumn ('Application ID',
                                                   self::TABLE_APPLICATION,
                                                   'Application'),
                    $this->createTextColumn ('Name', 256),
                    $this->createBoolColumn ('Daily Challenge'),
                    $this->createBoolColumn ('Published'),
                    $this->createTimestampColumn ('Published On', true),
                    $this->createBoolColumn ('Live'),
                    $this->createTimestampColumn ('Live On', true),
                    $this->createTimestampColumn ('Checked On', true),
                    $this->createIntColumn ('Checked Status', true),
                    $this->createTextColumn ('Schema Version', 10, true),
                    $this->createTextColumn ('Checked Description', 1024, true),
//                    $this->createTextColumn ('DC Button', 128, true),
//                    $this->createTextColumn ('DC Label', 1024, true),
                    $this->createTimestampColumn ('DC Expires', true),
                    );

            case self::TABLE_APPLICATION:
                $idColumn = "Application ID";
                return array
                    (
                    $this->createTextColumn ('Name', 256),
                    $this->createTextColumn ('UniqueID', 128),
                    );

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

            case self::TABLE_DC_LAYOUT:
                $idColumn = "DC Layout ID";
                return array
                    (
                    $this->createForeignKeyColumn ('Application ID',
                                                   self::TABLE_APPLICATION,
                                                   'Application'),
                    $this->createForeignKeyColumn ('Layout ID',
                                                   self::TABLE_LAYOUT,
                                                   'Layout'),
                    $this->createIntColumn ('State'),
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

            case self::TABLE_GAME_LOGS_EXT:
                $idColumn = "Log ID";
                return array
                    (
                    $this->createTextColumn ('Type', 16),
                    $this->createTextColumn ('Log', 4096),
                    $this->createTextColumn ('Source', 512),
                    $this->createTextColumn ('Processed', 512, true),
                    $this->createIntColumn('pid', true)
                    );

            case self::TABLE_LEVEL:
                $idColumn = "Level ID";
                $cols = array
                    (
                    $this->createTextColumn ('Meta', 1000),
                    );
                $cols = array_merge(array($this->createForeignKeyColumn('Version ID', self::TABLE_VERSION, 'Version'),
                    $this->createForeignKeyColumn('Category ID', self::TABLE_CATEGORY, 'Category', true),
                    $this->createForeignKeyColumn('Metadata Version ID', self::TABLE_METADATA_VERSION, 'Layout Version'),
                    $this->createIntColumn('Index', true),
                    $this->createIntColumn('Complexity', true),
                        ), $cols);
                return $cols;
            case self::TABLE_LEVEL_LOGS:
                $idColumn = "Level Log ID";
                $cols = array
                    (
                    $this->createTextColumn ('Device ID', 96),
                    $this->createTextColumn ('IP', 45),
                    $this->createTextColumn ('Platform', 32),
                    $this->createTextColumn ('Category', 32),
                    $this->createTextColumn ('Level', 32),
                    $this->createTimestampColumn ('Solved On', true),
                    );
                return $cols;
            case self::TABLE_DC_LEVEL:
                $idColumn = "Level ID";
                $cols = array(
                    $this->createDateColumn ('Date'),
                    $this->createForeignKeyColumn('Metadata Version ID', self::TABLE_METADATA_VERSION, 'Layout Version'),
                    $this->createIntColumn('Index', true),
                    $this->createIntColumn('StarsX2', true),
                    $this->createIntColumn('State', true),
                        );
                return $cols;
            case self::TABLE_CHARTBOOST_REPORT_C:
                $idColumn = "Row ID";
                $cols = array(
                    $this->createDateColumn ('Date'),
                        );
                foreach (preg_split("#\t#", "To Campaign Name	To Campaign ID	To App Name	To App ID	To App Bundle	To App Platform	From Campaign Name	From Campaign ID	From App Name	From App ID	From App Bundle	From App Platform	Campaign Type	Role	Ad Type") as $col)
                    $cols[] = $this->createTextColumn ($col, 256, true);

                $cols[] = $this->createIntColumn('Impressions', true);
                $cols[] = $this->createIntColumn('Clicks', true);
                $cols[] = $this->createIntColumn('Installs', true);
                $cols[] = $this->createIntColumn('Completed View', true);
                $cols[] = $this->createDecimalColumn('Money Earned', true);
                $cols[] = $this->createDecimalColumn('Money Spent', true);
                $cols[] = $this->createDecimalColumn('eCPM Earned', true);
                $cols[] = $this->createDecimalColumn('eCPM Spent', true);
                return $cols;
                
            case self::TABLE_CHARTBOOST_REPORT_COUNTRIES:
                $idColumn = "Row ID";
                $cols = array(
                    $this->createDateColumn ('Date'),
                        );
                foreach (preg_split("#\t#", "Country	Campaign Name	Campaign ID	Campaign Type	Campaign Subtype	App Name	App ID	App Bundle	Platform	Role	Ad Type") as $col)
                    $cols[] = $this->createTextColumn ($col, 256, true);

                $cols[] = $this->createIntColumn('Impressions', true);
                $cols[] = $this->createIntColumn('Clicks', true);
                $cols[] = $this->createIntColumn('Installs', true);
                $cols[] = $this->createIntColumn('Completed Views', true);
                $cols[] = $this->createDecimalColumn('Money Earned', true);
                $cols[] = $this->createDecimalColumn('Money Spent', true);
                $cols[] = $this->createDecimalColumn('eCPM Earned', true);
                $cols[] = $this->createDecimalColumn('eCPM Spent', true);
                $cols[] = $this->createDecimalColumn('CTR', true);
                $cols[] = $this->createDecimalColumn('IR', true);
                return $cols;
                
            case self::TABLE_ADMOB_REPORT_COUNTRIES:
                $idColumn = "Row ID";
                $cols = array(
                    $this->createDateColumn ('Date'),
                    $this->createTextColumn ('Country', 256, true)
                        );
                foreach (preg_split("#\t#", "AdMob Network requests	Matched requests	Match rate (%)	Impressions	Show rate (%)	Rewarded starts	Rewarded completes	Clicks	Impressions CTR (%)	AdMob Network request RPM (EUR)	Impression RPM (EUR)	Estimated earnings (EUR)	Active View-eligible impressions	Measurable impressions	Viewable impressions	% Measurable impressions (%)	% Viewable impressions (%)") as $col)
                    $cols[] = $this->createDecimalColumn ($col, true);

                return $cols;
                
            case self::TABLE_CHARTBOOST_COUNTRIES:
                $idColumn = "Country ID";
                $cols = array(
                    $this->createTextColumn ('ISO Code', 3),
                    $this->createTextColumn ('Name', 256),
                        );
                return $cols;
                
            case self::TABLE_CHARTBOOST_BLACKLIST:
                $idColumn = "Row ID";
                $cols = array(
                    $this->createTextColumn ('App Id', 40),
                    $this->createTextColumn ('Blacklisted App', 40),
                    );
                return $cols;
                
            case self::TABLE_CHARTBOOST_STORE_INFO:
                $idColumn = "Row ID";
                $cols = array(
                    $this->createTextColumn ('App Id', 40),
                    $this->createTextColumn ('Name', 256, true),
                    $this->createDecimalColumn ('Daily Ratings', true),
                    $this->createTextColumn ('Stats', 10000, true),
                    );
                return $cols;
                
            case self::TABLE_LAYOUT:
                $idColumn = "Layout ID";
                $cols = array
                    (
                    $this->createTextColumn ('Layout', 576),
                    $this->createTextColumn ('Hash', 48),
                    $this->createTextColumn ('Source', 128),
                    $this->createForeignKeyColumn('Metadata Version ID', self::TABLE_METADATA_VERSION, 'Metadata Version'),
                    $this->createTextColumn ('UID'),
                    );
                return $cols;

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
            case self::TABLE_CATEGORY:
                $indexes = array ("UNIQUE(`Application Id`, `Name`)");
                break;
            case self::TABLE_CATEGORY_LNG:
                $indexes = array ("UNIQUE(`Category ID`, `lng`, `Name`)");
                break;
            case self::TABLE_APPLICATION:
                $indexes = array ("UNIQUE(`Name`)");
                $relatedTables[] = self::TABLE_VERSION;
                break;
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
            case self::TABLE_GAME_LOGS_EXT:
                $indexes = array ("UNIQUE(`Type`, `Source`(128))");
                break;
            case self::TABLE_VERSION:
                $indexes = array ("UNIQUE(`Application ID`, `Daily Challenge`, `Name`)", "INDEX(`Application ID`, `Published`, `Daily Challenge`)");
                break;
            case self::TABLE_LEVEL:
                $indexes = array ("INDEX(`Version Id`, `Category Id`, `Index`), UNIQUE(`Version Id`, `Metadata Version ID`)");
                $relatedTables[] = self::TABLE_CATEGORY;
                break;
            case self::TABLE_LEVEL_LOGS:
                $indexes = array ("INDEX(`Category`, `Level`), UNIQUE(`Device Id`, `Category`, `Level`)");
                break;
            case self::TABLE_DC_LEVEL:
                $indexes = array ("UNIQUE(`Date`, `Index`), UNIQUE(`Metadata Version ID`)");
                break;
            case self::TABLE_CHARTBOOST_REPORT_C:
                $indexes = array ("UNIQUE(`Date`, `To Campaign ID`(40), `From App ID`(40))", "INDEX(`To App ID`(40), `Date`)", "INDEX(`To Campaign ID`(40), `Date`)");
                break;
            case self::TABLE_CHARTBOOST_REPORT_COUNTRIES:
                $indexes = array ("UNIQUE(`Date`, `Country`(3), `Campaign ID`(40), `Ad Type`(40))", "INDEX(`Country`(3), `Date`)", "INDEX(`Campaign ID`(40), `Date`)");
                break;
            case self::TABLE_ADMOB_REPORT_COUNTRIES:
                $indexes = array ("UNIQUE(`Date`, `Country`(50))", "INDEX(`Country`(40), `Date`)");
                break;
            case self::TABLE_CHARTBOOST_COUNTRIES:
                $indexes = array ("UNIQUE(`ISO Code`)");
                break;
            case self::TABLE_CHARTBOOST_BLACKLIST:
                $indexes = array ("UNIQUE(`App Id`, `Blacklisted App`)");
                break;
            case self::TABLE_CHARTBOOST_STORE_INFO:
                $indexes = array ("UNIQUE(`App Id`)");
                break;
            //case self::TABLE_LEVEL_QUEUE:
            //    $indexes = array ("INDEX(`pid`, `Level ID`), UNIQUE(`Hash`)");
            //    break;

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


    public function selectChartboostCampaigns (&$error)
        {
        $tableName = self::TABLE_CHARTBOOST_CAMPAIGNS;
        $sql = "SELECT `id`, `name` FROM `$tableName`";
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    private function getCampaignDateCriterion ($date, $column = 'Date')
        {
        if (empty ($date))
            return false;

        $operation = '=';
        if (preg_match('/^([><=]+)(.+)$/', $date, $m))
            {
            $operation = $m[1];
            $date = trim ($m[2]);
            }

        if (false === strtotime ($date))
            return false;

        return "`$column`$operation'".$this->escapeString($date)."'";
        }

    public function selectChartboostCampaignSummary ($id, $date, &$error)
        {
        $tableName = self::TABLE_CHARTBOOST_REPORT_C;
        $id = $this->escapeString ($id);
        $dateFilter = $this->getCampaignDateCriterion ($date);
        $where = empty ($dateFilter) ? "" : " AND $dateFilter";
        $sql = <<<EOT
SELECT COUNT(DISTINCT `From App Name`) `count`, SUM(`Impressions`) as `impressions`,
    SUM(`Clicks`) as `clicks`, SUM(`Installs`) as `installs`,
    SUM(`Impressions`*`eCPM Spent`/1000) as `money`
    FROM `$tableName`
   WHERE `To Campaign ID`='$id' $where
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectChartboostCampaignReport ($id, $minInstalls, $minImpressions, $date, &$error)
        {
        $tableName = self::TABLE_CHARTBOOST_REPORT_C;
        $tableBL = self::TABLE_CHARTBOOST_BLACKLIST;
        $tableInfo = self::TABLE_CHARTBOOST_STORE_INFO;
        $id = $this->escapeString ($id);
        $additionalHaving = $where = "";
        if (is_numeric ($minInstalls))
            {
            $additionalHaving = " AND SUM(CASE WHEN `Installs` IS NULL THEN 0 ELSE `Installs` END) ".(0 == $minInstalls ? "=0" : ">=$minInstalls");
            }

        $dateFilter = $this->getCampaignDateCriterion ($date);

        if (!empty ($dateFilter))
            {
            $where = " AND $dateFilter";
            }
            
        $sql = <<<EOT
SELECT `From App ID`, `From App Name`, `From App Bundle`,
       SUM(`Impressions`) as `Impressions`,
       SUM(`Clicks`) as `Clicks`, SUM(CASE WHEN `Installs` IS NULL THEN 0 ELSE `Installs` END) as `Installs`,
       SUM(`Impressions`*`eCPM Spent`/1000) as `Money`,
       MIN(`Date`) `Min Date`, MAX(`Date`) `Max Date`,
       COUNT(bl.`App Id`) `Is Blacklisted`,
       MIN(inf.`Daily Ratings`) `Daily Ratings`, MAX(inf.`Name`) `Real Name`, MAX(inf.`App Id`) `Stored App Id`
    FROM `$tableName` t
    LEFT OUTER JOIN `$tableBL` bl ON `From App Id`=bl.`BlackListed App` AND `To Campaign ID`=bl.`App Id`
    LEFT OUTER JOIN `$tableInfo` inf ON `From App Id`=inf.`App Id`
   WHERE `To Campaign ID`='$id' $where
   GROUP BY `From App ID`, `From App Name`, `From App Bundle`
   HAVING (SUM(CASE WHEN `Installs` IS NULL THEN 0 ELSE `Installs` END) > 0 OR SUM(`Impressions`) > $minImpressions) $additionalHaving
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
//        print($sql);
        if (empty ($rows))
            return $rows;
        return $rows;
        }
        
    public function selectChartboostCampaignBlacklist ($id, &$error)
        {
        $tableBL = self::TABLE_CHARTBOOST_BLACKLIST;
        $id = $this->escapeString ($id);
        $sql = <<<EOT
SELECT `Blacklisted App`
    FROM `$tableBL` t
   WHERE `App Id`='$id'
   ORDER BY `Created On`
EOT;
        $rows = $this->executeSelect ($tableBL, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }
        
    public function deleteChartboostCampaignBlacklistedApp ($campaignId, $blacklistedAppId, &$error)
        {
        $tableName = DBTable::TABLE_CHARTBOOST_BLACKLIST;
        $campaignIdS = $this->escapeString ($campaignId);
        $idS = $this->escapeString ($blacklistedAppId);
        $sql = <<<EOT
DELETE FROM `$tableName` WHERE `App Id` = '$campaignIdS' AND `Blacklisted App`='$idS'
EOT;

        $ret = $this->db->executeSQLNoCheck ($sql, true);
        if (false === $ret)
            {
            $error = $this->db->getLastError ();
            return false;
            }

        return true;
        }

    public function selectCategory ($id, &$error)
        {
        $tableName = self::TABLE_CATEGORY;
        $sql = "SELECT * FROM `$tableName` WHERE `Category ID`=$id";
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows[0];
        }

    public function selectAllCategories ($applicationId, &$error)
        {
        $tableName = self::TABLE_CATEGORY;
        $tableLevels = $this->getAppLevelsTableName ($applicationId);
        $sql = <<<EOT
SELECT c.`Category ID`, c.`Name`, c.`Priority`, COUNT(DISTINCT l.`Level ID`) as `levels`
  FROM `$tableName` c
  LEFT OUTER JOIN `$tableLevels` l ON l.`Category ID`=c.`Category ID`
  WHERE c.`Application ID`=$applicationId
 GROUP BY c.`Category ID`, c.`Name`
 ORDER BY c.`Priority`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectAllApplications (&$error)
        {
        $tableName = self::TABLE_APPLICATION;
        $tableVersions = self::TABLE_VERSION;
        $sql = <<<EOT
SELECT c.`Application ID`, c.`Name`, `UniqueID`,
       COUNT(DISTINCT vDC.`Version ID`) as `DC count`, COUNT(DISTINCT vGD.`Version ID`) as `GD count`,
       liveGD.`Name` as `Live GD`, liveDC.`Name` as `Live DC`
  FROM `$tableName` c
  LEFT OUTER JOIN `$tableVersions` vDC ON vDC.`Application ID`=c.`Application ID` AND vDC.`Daily Challenge`=1
  LEFT OUTER JOIN `$tableVersions` vGD ON vGD.`Application ID`=c.`Application ID` AND vGD.`Daily Challenge`=0
  LEFT OUTER JOIN `$tableVersions` liveDC ON liveDC.`Application ID`=c.`Application ID` AND liveDC.`Daily Challenge`=1 AND liveDC.`Live`=1
  LEFT OUTER JOIN `$tableVersions` liveGD ON liveGD.`Application ID`=c.`Application ID` AND liveGD.`Daily Challenge`=0 AND liveGD.`Live`=1
 GROUP BY c.`Application ID`, c.`Name`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows;
        }

    public function selectApplicationByUniqueId ($applicationId, &$error)
        {
        $tableName = self::TABLE_APPLICATION;
        $applicationId = $this->db->escapeString ($applicationId);
        $sql = <<<EOT
SELECT c.`Application ID`, c.`Name`, `UniqueID`
  FROM `$tableName` c
 WHERE c.`UniqueID`='$applicationId'
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows[0];
        }

    public function selectVersion ($versionId, &$error)
        {
        $tableName = self::TABLE_VERSION;
        $tableApp = self::TABLE_APPLICATION;
        $sql = <<<EOT
SELECT v.`Application ID`, v.`Name`, a.`Name` `Application`, `Published`, `Version ID`
  FROM `$tableName` v
  LEFT OUTER JOIN `$tableApp` a ON a.`Application ID`=v.`Application ID`
  WHERE `Version ID`=$versionId
EOT;

        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows[0];
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

    public function selectWorkingVersion ($appId, &$error)
        {
        $tableName = self::TABLE_VERSION;
        $tableApp = self::TABLE_APPLICATION;
        $sql = <<<EOT
SELECT v.`Version ID`, v.`Application ID`, v.`Name`, `Published`
  FROM `$tableName` v
  LEFT OUTER JOIN `$tableApp` a ON a.`Application ID`=v.`Application ID`
  WHERE a.`Application ID`=$appId AND `Published`=0
  ORDER BY `Version ID` DESC
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows[0];
        }

    public function getFreeDCLayoutCount ($appId, &$error)
        {
        $tableName = self::TABLE_DC_LAYOUT;
        $layoutsTable = $this->getAppLayoutsTableName ($appId);
        $layoutsMDTable = $this->getAppMetadataTableName ($appId);
        $stateAvailable = self::DC_LAYOUT_AVAILABLE;
        $minComplexity = self::MIN_DC_COMPLEXITY;
        $sql = <<<EOT
SELECT COUNT(*) `cnt`
  FROM `$tableName` dc
 INNER JOIN `$layoutsTable` layout ON layout.`Layout ID`=dc.`Layout ID`
 INNER JOIN `$layoutsMDTable` md ON md.`Metadata Version ID`=layout.`Metadata Version ID`
  WHERE dc.`Application ID`=$appId AND dc.`State`=$stateAvailable AND md.`Complexity` > $minComplexity
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return 0;
        return $rows[0]['cnt'];
        }

    public function selectDCLayoutCounts ($appId, $startDate, $endDate, &$error)
        {
        $tableName = $this->getAppDCLevelsTableName ($appId);
        $startStr = date ('Y-m-d', $startDate);
        $endStr = date ('Y-m-d', $endDate);
        $sql = <<<EOT
SELECT `Date`, COUNT(*) `cnt`, SUM(CASE WHEN `State`>0 THEN 1 ELSE 0 END) `Valid`, SUM(CASE WHEN `State`<0 THEN 1 ELSE 0 END) `Invalid`
  FROM `$tableName` dc
  WHERE `Date` BETWEEN '$startStr' AND '$endStr'
  GROUP BY `Date`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        return $rows;
        }
        
    private function mapDCLevelRow ($row)
        {
        $obj = new StdClass ();
        $obj->id = $row['Level ID'];
        $obj->metadataID = $row['Metadata Version ID'];
        $obj->index = $row['Index'];
        $obj->complexity = $row['Complexity'];
        return $obj;
        }
        
    public function calculateDCComplexityDistance ($existing, $complexity)
        {
        $distance = 10000;
        foreach ($existing as $row)
            $distance = min ($distance, abs($row->complexity - $complexity));
        return $distance;
        }
        
    public function chooseDCLevel ($appId, $date, &$existing, &$rows, &$error)
        {
        /*
SELECT `Level ID`, `Date`, l.`Metadata Version ID`, `Index`, `StarsX2`, md.`Complexity`
 FROM `ASO DC Levels:1` l 
 INNER JOIN `Metadata Version:1` md ON md.`Metadata Version ID`=l.`Metadata Version ID`
         */
        $minDistance = 75;
        for ($i = 1; $i < 20; $i++)
            {
            $available = true;
            foreach ($existing as $row)
                {
                if ($row->index == $i)
                    {
                    $available = false;
                    break;
                    }
                }
                
            if ($available)
                {
                $nextIndex = $i;
                break;
                }
            }

        $min = 500;
        $max = 1000;
        switch ($nextIndex)
            {
            case 1:
            case 2:
            case 3:
                $min = ($nextIndex) * 75;
                $max = $min + 75;
                break;
            case 4:
            case 5:
            case 6:
                $min = ($nextIndex-3) * 100;
                $max = ($nextIndex-2) * 100;
                break;
            }
        foreach ($rows as $idx => $row)
            {
            $max++;$min--;
            if ($row['Complexity'] > $max || $row['Complexity'] < $min)
                continue;
                
            $distance = $this->calculateDCComplexityDistance ($existing, $row['Complexity']);
            if ($distance < $minDistance)
                {
                $minDistance--;
                continue;
                }
            
            $tableName = $this->getAppDCLevelsTableName ($appId);
            
            $stars = min (10, ceil (10 * $row['Complexity'] / 500.0));
            $sql = <<<EOT
(`Date`, `Metadata Version ID`, `Index`, `StarsX2`)
  VALUES
($date, {$row['Metadata Version ID']}, $nextIndex, $stars)
EOT;
            $newId = $this->executeInsert ($tableName, $sql, true, $error);
            
            if (false === $newId)
                {
                if (1062 != $this->db->getLastErrorId ())
                    return false;
                // lets mark this DC candidate as assigned
                }
            else
                {
                $row['Level ID'] = $newId;
                $row['Index'] = $nextIndex;
                $existing[] = $this->mapDCLevelRow ($row);
                }
            
            $tableDCLayout = self::TABLE_DC_LAYOUT;
            $stateAssigned = self::DC_LAYOUT_USED;
            $sql = <<<EOT
SET `State`=$stateAssigned
WHERE `DC Layout ID`={$row['DC Layout ID']}
EOT;
            $affected = $this->executeUpdate ($tableDCLayout, $sql, $error);
            if (false === $affected)
                return ($error = "error updating layout row {$row['DC Layout ID']} - $error") == null;
            
            unset ($rows[$idx]);
            return $newId;
            }
        return false;
        }
        
    public function refreshDCLevels ($appId, &$error)
        {
        $tableName = $this->getAppDCLevelsTableName ($appId);
        $levelsSolvedTable = $this->getUserLevelsTableName ($appId);
        $tableDC = self::TABLE_DC_LAYOUT;
        $layoutsTable = $this->getAppLayoutsTableName ($appId);
        $layoutsMDTable = $this->getAppMetadataTableName ($appId);
        $sql = <<<EOT
SELECT DISTINCT dc.`Level ID`
  FROM `$tableName` dc
 INNER JOIN `$layoutsMDTable` md ON md.`Metadata Version ID`=dc.`Metadata Version ID`
 INNER JOIN `$layoutsTable` layout ON layout.`Layout ID`=md.`Layout ID`
 INNER JOIN `$levelsSolvedTable` solved ON solved.`Level`=layout.`Hash`
 WHERE dc.`State`=0 AND dc.`Date`>=DATE(NOW()) AND solved.`Solved On` IS NOT NULL
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (false === $rows)
            return false;
        if (empty ($rows))
            return 0;
        $ids = implode (',', array_map (function ($row) { return $row['Level ID'];}, $rows));
        $sql = <<<EOT
SET `State`=1
WHERE `Level ID` IN ($ids)
EOT;
        $affected = $this->executeUpdate ($tableName, $sql, $error);
        if (false === $affected)
            return ($error = "error refreshing levels - $error") == null;
        return $affected;
        }
        
    public function assignDCLayouts ($appId, $date, $max, &$error)
        {
        $tableName = $this->getAppDCLevelsTableName ($appId);
        $tableDC = self::TABLE_DC_LAYOUT;
        $layoutsTable = $this->getAppLayoutsTableName ($appId);
        $layoutsMDTable = $this->getAppMetadataTableName ($appId);
        
        $sql = <<<EOT
SELECT `Level ID`, dc.`Metadata Version ID`, dc.`Index`, md.`Complexity`
  FROM `$tableName` dc
  INNER JOIN `$layoutsMDTable` md ON dc.`Metadata Version ID`=md.`Metadata Version ID`
  WHERE `Date`=$date
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (false === $rows)
            return false;
        
        $existing = array_map(array($this, 'mapDCLevelRow'), $rows ?? array());

        $stateAvailable = self::DC_LAYOUT_AVAILABLE;
        $minComplexity = self::MIN_DC_COMPLEXITY;

//        var_dump ($existing);
        $sql = <<<EOT
SELECT dc.`DC Layout ID`, md.`Metadata Version ID`, md.`Complexity`
  FROM `$tableDC` dc
  INNER JOIN `$layoutsTable` lay ON lay.`Layout ID`=dc.`Layout ID`
  INNER JOIN `$layoutsMDTable` md ON lay.`Metadata Version ID`=md.`Metadata Version ID`
  WHERE dc.`Application ID`=$appId AND dc.`State`=$stateAvailable AND md.`Complexity` > $minComplexity
  ORDER BY `uid`
  LIMIT 250
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            {
            $error = empty ($error) ? "No available levels" : $error;
            return false;
            }
        
        $cnt = 0;
        while (count ($existing) < $max && $cnt++ < $max)
            {
            if (false === $this->chooseDCLevel ($appId, $date, $existing, $rows, $error))
                return false;
            }
            
        return count ($existing);
        }
        
    public function populateDCLayouts ($appId, $maxCount, &$error)
        {
        $tableName = self::TABLE_DC_LAYOUT;
        $layoutsMDTable = $this->getAppMetadataTableName ($appId);
        $layoutsTable = $this->getAppLayoutsTableName ($appId);
        $levelsTable = $this->getAppLevelsTableName ($appId);
        $versionsTable = self::TABLE_VERSION;
        $stateAvailable = self::DC_LAYOUT_AVAILABLE;
        $sql = <<<EOT
(`Application ID`, `Layout ID`, `State`)
SELECT DISTINCT $appId, md.`Layout ID`, $stateAvailable
 FROM `$layoutsMDTable` md
 INNER JOIN `$layoutsTable` lay ON md.`Layout ID`=lay.`Layout ID` AND lay.`Metadata Version ID`=md.`Metadata Version ID`
 LEFT OUTER JOIN `$levelsTable` lvl ON md.`Metadata Version ID`=lvl.`Metadata Version ID`
 LEFT OUTER JOIN `$versionsTable` ver ON lvl.`Version ID`=ver.`Version ID` AND ver.`Live`=1 AND ver.`Application ID`=$appId
 LEFT OUTER JOIN `$tableName` dc ON md.`Layout ID`=dc.`Layout ID` AND dc.`Application ID`=$appId
  WHERE ver.`Version ID` IS NULL AND dc.`DC Layout ID` IS NULL AND md.`Complexity` IS NOT NULL
  LIMIT $maxCount
EOT;
        $affected = $this->executeInsert ($tableName, $sql, false, $error);
        //var_dump ($affected, $sql);
        if (false === $affected)
            return false;
        return $affected;
        }

    public function getAppLevelsTableName ($applicationId) // was getLevelsTableName
        {
        if (empty ($applicationId))
            return false;
        return self::TABLE_LEVEL.":$applicationId";
        }

    public function getUserLevelsTableName ($applicationId)
        {
        if (empty ($applicationId))
            return false;
        return self::TABLE_LEVEL_LOGS.":$applicationId";
        }

    public function getAppDCLevelsTableName ($applicationId) // was getLevelsTableName
        {
        if (empty ($applicationId))
            return false;
        return self::TABLE_DC_LEVEL.":$applicationId";
        }

    public function getAppLayoutsTableName ($applicationId)
        {
        if (empty ($applicationId))
            return false;
        return self::TABLE_LAYOUT.":$applicationId";
        }

    public function getAppMetadataTableName ($applicationId)
        {
        if (empty ($applicationId))
            return false;
        return self::TABLE_METADATA_VERSION.":$applicationId";
        }

    public function ensureLayout ($appId, $layout, $hash, $source, &$error)
        {
        if (empty ($appId) || empty ($layout) || empty ($hash))
            {
            return ($error = "invalid params - ensureLayout") == null;
            }
            
        $layoutsTable = $this->getAppLayoutsTableName ($appId);
        $source = $this->escapeString ($source);
        $sql = <<<EOT
(`Layout`, `Hash`, `Source`, `uid`)
VALUES
('$layout', '$hash', '$source', md5(UUID()) )
EOT;
        $id = $this->executeInsert ($layoutsTable, $sql, true, $error);
        if (false === $id)
            return ($error = "error creating layout - $error") == null;
        return $id;
        }

    public function saveLayout ($appId, $layout, $hash, $source, $solution, $solutionErr, &$error)
        {
        if (false === $solution)
            {
            return ($error = "error processing layout".(empty ($solutionErr) ? "" : " ($solutionErr)")) == null;
            }

        $layoutId = is_numeric ($layout) ? $layout : $this->ensureLayout ($appId, $layout, $hash, $source, $error);
        if (false === $layoutId)
            return false;
        
        if (!$solution->reached)
            {
            $metaStr = 'NULL';
            $complexity = 'NULL';
            $err = "'".$this->escapeString ($solutionErr)."'";
            }
        else
            {
            if (empty ($solution->rules))
                $solution->rules = '10i';
            $metaRaw = json_encode ($solution);
            $metaStr = $this->escapeString ($metaRaw);
            $metaStr = "'$metaStr'";
            $complexity = $solution->complexity;
            $err = "NULL";
            }

        $sql = <<<EOT
(`Layout ID`, `Meta`, `Complexity`, `Error`)
VALUES
($layoutId, $metaStr, $complexity, $err)
EOT;

        $metadataTable = $this->getAppMetadataTableName ($appId);
        $layoutsTable = $this->getAppLayoutsTableName ($appId);

        $metaId = $this->executeInsert ($metadataTable, $sql, true, $error);
        if (false === $metaId)
            return ($error = "error inserting metadata for $layoutId - $error") == null;

        $sql = <<<EOT
SET `Metadata Version ID`=$metaId
WHERE `Layout ID`=$layoutId
EOT;
        $affected = $this->executeUpdate ($layoutsTable, $sql, $error);
        if (false === $affected)
            return ($error = "error updating layout row $layoutId - $error") == null;
        if (0 === $affected)
            {
            $error = "Did not update layout row $layoutId";
            return false;
            }
        
        return $metaId;
        }
        
    public function selectApplicationVersions ($applicationId, &$error, $liveOnly = false, $name = false)
        {
        $tableName = self::TABLE_VERSION;
        $tableLevels = $this->getAppLevelsTableName ($applicationId);
        $additionalWhere = $liveOnly ? " AND `Live`=1" : "";
        
        $additionalWhere .= !empty ($name) ? " AND v.`Name`='".$this->escapeString($name)."'" : "";
        $sql = <<<EOT
SELECT v.`Version ID`, v.`Application ID`, v.`Name`, v.`Daily Challenge`,
       `Published`, CASE WHEN `Published`=1 THEN `Published On` ELSE NULL END `Published On`,
       `Live`, CASE WHEN `Live`=1 THEN `Live On` ELSE NULL END `Live On`,
       COUNT(DISTINCT l.`Category Id`) as `Category count`, COUNT(DISTINCT l.`Level ID`) as `Level count`,
       `Checked On`, `Checked Status`, `Checked Description`
  FROM `$tableName` v
  LEFT OUTER JOIN `$tableLevels` l ON l.`Version ID`=v.`Version ID`
  WHERE v.`Application ID`=$applicationId $additionalWhere
  GROUP BY v.`Version ID`, v.`Name`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            {
            if (false === $rows && 1146 == $this->db->getLastErrorId ())
                {
                // create table
                if (!$this->ensureTable ($tableLevels, $error))
                    return false;

                $rows = $this->executeSelect ($tableName, $sql, $error);
                }

            if (empty ($rows))
                return $rows;
            }
        return $rows;
        }

    public function selectApplication ($appId, &$error)
        {
        $tableName = self::TABLE_APPLICATION;
        $sql = <<<EOT
SELECT c.`Application ID`, c.`Name`, `UniqueID`
  FROM `$tableName` c
 WHERE c.`Application ID`=$appId
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty ($rows))
            return $rows;
        return $rows[0];
        }

    public function selectAllLevels ($appId, $versionId, &$error)
        {
        return $this->selectLevelsByCategory ($appId, $versionId, -1, $error);
        }

    public function selectLevelsByCategory ($applicationId, $versionId, $categoryId, &$error)
        {
        $tableCategories = self::TABLE_CATEGORY;
        $tableName = $this->getAppLevelsTableName ($applicationId);
        $tableLayouts = $this->getAppLayoutsTableName ($applicationId);
        $tableMeta = $this->getAppMetadataTableName ($applicationId);
        $where = $categoryId < 0 ? '' : (empty ($categoryId) ? " AND c.`Category ID` IS NULL" : " AND c.`Category ID`=$categoryId");
        $sql = <<<EOT
SELECT c.`Category ID`, c.`Name` `Category Name`, l.`Level ID`, m.`Meta`, m.`Complexity`,
       l.`Index`, lay.`Layout`, l.`Updated On`, l.`Created On`, lay.`Hash`, c.`Priority`
  FROM `$tableName` l
    INNER JOIN `$tableMeta` m ON l.`Metadata Version ID`=m.`Metadata Version ID`
    INNER JOIN `$tableLayouts` lay ON m.`Layout ID`=lay.`Layout ID`
  LEFT OUTER JOIN `$tableCategories` c ON l.`Category ID`=c.`Category ID`
  WHERE l.`Version ID`=$versionId $where
  ORDER BY c.`Priority`, l.`Category ID`, l.`Index`, l.`Complexity`, l.`Level ID`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty($rows))
            {
            return $rows;
            }
        return $rows;
        }

    public function selectDCLevels ($applicationId, $date, &$error)
        {
        $tableName = $this->getAppDCLevelsTableName ($applicationId);
        $tableLayouts = $this->getAppLayoutsTableName ($applicationId);
        $tableMeta = $this->getAppMetadataTableName ($applicationId);
        $now = empty ($date) ? time () : strtotime ($date);
        $startDate = date ('Y-m-d', strtotime ('-1 day', $now));
        $endDate = date ('Y-m-d', strtotime ('+1 day', $now));
        $sql = <<<EOT
SELECT dc.`Date`, dc.`Level ID`, m.`Meta`, m.`Complexity`, dc.`StarsX2`,
       (CASE WHEN dc.Index < 4 THEN 0 ELSE 1 END) `Locked`,
       dc.`Index`, lay.`Layout`, dc.`Updated On`, dc.`Created On`, lay.`Hash`
  FROM `$tableName` dc
    INNER JOIN `$tableMeta` m ON dc.`Metadata Version ID`=m.`Metadata Version ID`
    INNER JOIN `$tableLayouts` lay ON m.`Layout ID`=lay.`Layout ID`
  WHERE dc.`Date` BETWEEN '$startDate' AND '$endDate'
  ORDER BY dc.`Date`, dc.`Index`, dc.`Level ID`
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (empty($rows))
            {
            return $rows;
            }
        return $rows;
        }

    public function deleteLevel ($applicationId, $id, &$error, $postfix = self::TABLE_POSTFIX_NONE)
        {
        $tableName = $this->getLevelsTableName ($applicationId, $postfix);
        $sql = <<<EOT
DELETE FROM `$tableName` WHERE `Level ID` = '$id'
EOT;

        $ret = $this->db->executeSQLNoCheck ($sql, true);
        if (false === $ret)
            {
            $error = $this->db->getLastError ();
            return false;
            }

        return true;
        }

    public function deleteAllLevels ($applicationId, $versionId, &$error)
        {
        $tableName = $this->getLevelsTableName ($applicationId);
        $sql = <<<EOT
DELETE FROM `$tableName` WHERE `Version ID`=$versionId
EOT;

        $ret = $this->db->executeSQLNoCheck ($sql, true);
        if (false === $ret)
            {
            $error = $this->db->getLastError ();
            return false;
            }

        return $ret;
        }

    public function deleteAllFailedLevels ($applicationId, &$error)
        {
        $tableName = $this->getLevelsTableName ($applicationId, self::TABLE_POSTFIX_FAILED);
        $sql = <<<EOT
TRUNCATE TABLE `$tableName`
EOT;

        $ret = $this->db->executeSQLNoCheck ($sql, true);
        if (false === $ret)
            {
            $error = $this->db->getLastError ();
            return false;
            }

        return $ret;
        }

    public function deleteCategory ($applicationId, $id, &$error)
        {
        $tableName = $this->getLevelsTableName ($applicationId);
        $sql = <<<EOT
SELECT COUNT(*) `cnt`
  FROM `$tableName` l
  WHERE l.`Category ID`='$id'
EOT;
        $rows = $this->executeSelect ($tableName, $sql, $error);
        if (!empty ($rows) && $rows[0]['cnt'] > 0)
            {
            $error = "Cannot remove the category with levels assigned to it";
            return false;
            }
        if (false == $rows)
            return false;

        $tableName = DBTable::TABLE_CATEGORY;
        $sql = <<<EOT
DELETE FROM `$tableName` WHERE `Category ID` = '$id'
EOT;

        $ret = $this->db->executeSQLNoCheck ($sql, true);
        if (false === $ret)
            {
            $error = $this->db->getLastError ();
            return false;
            }

        return true;
        }

    }
