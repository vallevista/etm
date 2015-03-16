<?php


class GoogleSpreadsheet
{
	public static function fromUrl($url) {
		$xmlString = file_get_contents($url);
		$xml = simplexml_load_string($xmlString);
		$xml->registerXPathNamespace('gsx', 'http://schemas.google.com/spreadsheets/2006/extended');
		$xml->registerXPathNamespace('openSearch', 'http://a9.com/-/spec/opensearchrss/1.0/');
		return $xml;
	}
} 