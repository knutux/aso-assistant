<?php

class PageRouter
    {
    public static function route ($name)
        {
        switch ($name)
            {
            case 'api':
                return "ApiHandler";
            case 'aso':
                return "ASOToolsPage";
            case 'asoapp':
                return "ASOUnitPage";
            default:
                return false;
                
            }
        }
    }
