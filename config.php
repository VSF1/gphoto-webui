<?php

require_once("IniFile.php");

// read setup
$config = IniFile::read("cfg/config.ini");

if (isset($config['debug']) && $config['debug'] ){    
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

?>