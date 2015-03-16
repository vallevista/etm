<?php

$xml = file_get_contents("../my_files/territory.kml");
$index = 0;
$xml = preg_replace_callback('/([<]name[>][0-9]+[<]\/name[>])|([<]name[>]Untitled Polygon[<]\/name[>])/', function(&$pattern) use (&$index) {
	$index++;
	return "<name>$index</name>";
}, $xml);

echo $xml;