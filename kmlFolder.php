<?php
//security
session_start();

if (!isset($_SESSION['viewFolder'])) {
    exit;
}

//set this to xml
header ("Content-Type:text/xml");

//set defaults in $_REQUEST
$_REQUEST = array_merge(array(
    "territory" => "1",
    "congregation" => "",
    "locality" => ""
), $_REQUEST);

//include and instantiate EasyTerritoryMaker
include_once('lib/EasyTerritoryMaker.php');
$etm = new EasyTerritoryMaker();

//setup default colors
$borderColor = '4000ff00';
$fillColor = '4000ff00';

//override color if mini is used
if (isset($_REQUEST['mini'])) {
	$borderColor = '660000ff';
}

//get congregation and territory as strings
$congregation = (isset($_REQUEST['congregation']) ? $_REQUEST['congregation'] : "");
$kml = $etm->getSingleKml(urldecode($_REQUEST['territory']), urldecode($_REQUEST['locality']));

//write the xml
echo <<<XML
<kml
    xmlns="http://www.opengis.net/kml/2.2"
    xmlns:gx="http://www.google.com/kml/ext/2.2"
    xmlns:kml="http://www.opengis.net/kml/2.2"
    xmlns:atom="http://www.w3.org/2005/Atom">
	<Document>
		<name>$congregation</name>
		<open>1</open>
		<Style id="standardStyle">
            <LineStyle>
                <color>$borderColor</color>
                <width>5</width>
            </LineStyle>
            <PolyStyle>
                <color>$fillColor</color>
            </PolyStyle>
		</Style>
		$kml
	</Document>
</kml>
XML
;