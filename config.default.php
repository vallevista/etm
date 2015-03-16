<?php

global $etm_config;

require_once('lib/EasyTerritoryMakerConfig.php');

$etm_config = new EasyTerritoryMakerConfig();

$etm_config->password = "12345";
$etm_config->googleSpreadsheetKey = "";
$etm_config->dateFormat = "m/d/Y";