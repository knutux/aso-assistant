<?php

class PageRouter
    {
    public static function route ($name)
        {
        switch ($name)
            {
            case 'lvl':
                return "LevelList";
            case 'api':
                return "ApiHandler";
            case 'app':
                return "ApplicationList";
            case 'ver':
                return "VersionList";
            case 'cat':
                return "CategoryList";
            case 'create':
                return "CheckersPuzzleCreatorPage";
            case 'mgmt':
                return "DataManagementPage";
            case 'aso':
                return "ASOToolsPage";
            case 'asoapp':
                return "ASOUnitPage";
            case 'stats':
                return "Statistics";
            case 'ch-a':
                return "ChartboostManager";
            case 'ch-a-old':
                return "ChartboostAnalytics";
            default:
                return false;
                
            }
        }
    }