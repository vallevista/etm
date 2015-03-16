<?php

class EasyTerritoryMaker
{
    public $secure = false;
	public $store = array();
	public $storeByLocality = array();
	public $storeByName = array();
	public $dateFormat;

	public $territoryString;
    /**
     * @var SimpleXMLElement
     */
    public $territoryXML;

	/**
	 * @var SimpleXmlElement
	 */
	public $worksheetXml;
    /**
     * @var array(SimpleXMLElement)
     */
    public $territoryActivityXML = [];

	/**
	 * @var SimpleXMLElement
	 */
	public $dncXML;

	/**
     * @var TerritoryCollection[]
     */
    public $territories = array();

    /**
     * @var Territory[]
     */
    public $territoriesOut = array();

	function __construct($secure = false)
	{
        $this->secure = $secure;

        //Include types
        require_once('Territory.php');
        require_once('TerritoryCollection.php');
		require_once('GoogleSpreadsheet.php');

		$dir = dirname(dirname(__FILE__));
		$kmlFileLocation = $dir . '/my_files/territory.kml';

        //Throw helpful error if territory.kml doesn't exist
        if (!file_exists($kmlFileLocation)) {
            //throw new Exception("The 'territory.kml' file, created with Google Earth, does not exist in the 'my_files' folder.  Please save it there, and continue.");
        }

        //get territory.kml file, and read it to xml, and setup kml namespace
        $this->territoryString = file_get_contents($kmlFileLocation);
        $territoryXML = simplexml_load_string($this->territoryString);
        $territoryXML->registerXPathNamespace('kml', 'http://earth.google.com/kml/2.2');
        $this->territoryXML = $territoryXML;

        global $etm_config; require_once($dir . "/config.php");
        //get google.spreadsheet.key, a spreadsheet, for use with tracking changes with territory over time

        $key = $etm_config->googleSpreadsheetKey;
        $worksheetUrl = 'https://spreadsheets.google.com/feeds/worksheets/' . $key . '/public/values';
		$this->worksheetXml = GoogleSpreadsheet::fromUrl($worksheetUrl);
		$this->openWorksheet();

		$this->dateFormat = $etm_config->dateFormat;

		//Search through the xml at Document.Folder, or Document.Placemark
		$this->store = $this->territoryXML->xpath(<<<XPATH
//kml:Document
    /kml:Folder
|//kml:Document
    /kml:Placemark
XPATH
		);

		$this->readKML();
		$this->readActivity();
		$this->readDNCs();

	}

	private function readKML()
	{
		foreach($this->store as $data) {
			if (isset($data->Placemark)) {
				foreach($data->Placemark as $territory) {
					$name = $territory->name . '';
					$territory->locality = $data->name . '';
					$this->storeByName[$name] = $territory;
				}
			}
		}
	}

	private function openWorksheet() {
		global $etm_config;
		$worksheet = $this->worksheetXml;
		$title = null;

		foreach ($worksheet->entry as $spreadsheetReference) {
			$title = $spreadsheetReference->title . '';

			//first is xml list feed
			$url = $spreadsheetReference->link[0]['href'] . '';

			if ($title === $etm_config->activityTitle) {
				$this->territoryActivityXML[] = GoogleSpreadsheet::fromUrl($url);
			}

			else if ($title === $etm_config->activityArchiveTitle) {
				$this->territoryActivityXML[] = GoogleSpreadsheet::fromUrl($url);
			}

			else if ($title === $etm_config->dncTitle) {
				$this->dncXML = GoogleSpreadsheet::fromUrl($url);
			}
		}

	}

	private function readActivity()
	{
		$dateFormat = $this->dateFormat;
		foreach ($this->territoryActivityXML as $xml) {
			foreach ($xml->entry as $child) {
				$row = $child->children('gsx', TRUE);
				$territoryName = $row->territory . '';
				$locality = $this->storeByName[$territoryName]->locality . '';

				if (empty($this->territories[$territoryName])) {
					$this->territories[$territoryName] = new TerritoryCollection();
				}

				$territory = new Territory($row, $locality, $dateFormat);
				$this->territories[$territoryName]->add($territory);

				if (empty($territory->in)) {
					$this->territories[$territoryName]->out = true;
					$this->territoriesOut[$territoryName] = $territory;
				}
			}
		}
	}

	private function readDNCs()
	{

		foreach ($this->dncXML->entry as $child) {
			$row = $child->children('gsx', TRUE);

			$territoryName = $row->territory . '';
			if (isset($this->territories[$territoryName])) {
				$this->territories[$territoryName]->dnc[] = array(
					'address'=>$row->address . '',
					'name'=>$row->name . '',
					'date'=>$row->date . '',
					'territory'=>$row->date . ''
				);
			}
		}
	}

    /**
     * @return SimpleXMLElement[]
     */
    function all()
	{
		return $this->store;
	}

    /**
     * @param string $territory
     * @param string $locality
     * @return SimpleXMLElement[]
     */
    function lookupKml($territory, $locality = null)
	{
		$territoryKML = null;
        //If locality is set (ie Folder), use folder, otherwise, use Placemark
        if (empty($locality)) {
	        //try first for  Placemark, then Folder
	        $territoryKML = $this->territoryXML->xpath(<<<XPATH
//kml:Document
    /kml:Placemark[kml:name/text()='$territory']
XPATH
            );

	        if (empty($territoryKML)) {
		        $territoryKML = $this->territoryXML->xpath(<<<XPATH
//kml:Document
    /kml:Folder
        /kml:Placemark[kml:name/text()='$territory']
XPATH
				);
	        }
        } else {
	        $territoryKML = $this->territoryXML->xpath(<<<XPATH
//kml:Document
    /kml:Folder[kml:name/text()='$locality']
        /kml:Placemark[kml:name/text()='$territory']
XPATH
            );
        }

		if (empty($territoryKML)) {
			return null;
		}

		return $territoryKML;
	}

	/**
	 * @param $territory
	 * @param $locality
	 * @return null|Territory
	 */
	public function lookup($territory, $locality = null)
	{
		$kml = $this->lookupKml($territory, $locality);

		if ($kml == null) {
			return null;
		}

		$locality = $kml[0]->xpath("..");

		$root = $locality[0]->xpath("..");

		require_once('Territory.php');

        if (isset($root[0]->Document)) {
            $foundTerritory = new Territory();
            $foundTerritory->territory = $kml[0]->name . '';
            $foundTerritory->congregation = $locality[0]->name . '';
        }

        else {
            $foundTerritory = new Territory();
            $foundTerritory->territory = $kml[0]->name . '';
            $foundTerritory->locality = $locality[0]->name . '';
            $foundTerritory->congregation = $root[0]->name . '';
        }

        if ($this->secure) {
            if (isset($this->territories[$foundTerritory->territory])) {
                $preSecureTerritoryCollection = $this->territories[$foundTerritory->territory];
                $preSecureTerritory = $preSecureTerritoryCollection->mostRecent();
                $publisherNameParts = explode(" ", $preSecureTerritory->publisher);
                $initials = '';
                foreach($publisherNameParts as $part) {
                    $initials .= $part{0};
                }

                $attemptedInitials = strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_REQUEST['initials']));
                $actualInitials = strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $initials));
                if (
                    !empty($attemptedInitials)
                    && $attemptedInitials ===  $actualInitials
                ) {
                    //SUCCESS!
                    session_start();
                    $_SESSION['viewFolder'] = true;
                    return $foundTerritory;
                } else {
                    //Failed attempt
                    session_destroy();
                    return null;
                }
            }
        }

		return $foundTerritory;
	}

    /**
     * @param $territory
     * @param $locality
     * @return string
     */
    function getSingleKml($territory, $locality)
	{
		$result = '';
		$territoryItems = $this->lookupKml($territory, $locality);

		//list folders if is set
		if (empty($territoryItems) == false) {
			foreach($territoryItems as $territoryItem) {
                if (empty($territoryItems->Placemark)) {
                    $territoryItem->styleUrl = '#standardStyle';
                } else {
                    foreach($territoryItem->Placemark as $placemark) {
                        $placemark->styleUrl = '#standardStyle';
                    }
                }
				$result = $territoryItem->asXML();
			}
		}

		return $result;
	}


	/**
	 * @param $territory
	 * @return null|Territory
	 *
	 */
	function getSingleStatus($territory)
	{
		$activity = null;
		$mostRecentActivity = null;
		if (array_key_exists($territory, $this->territories)) {
			$activity = $this->territories[$territory];
		}

		if ($activity != null) {
			if ($activity->out) {
				$mostRecentActivity = $activity->mostRecent();
			}
		}

		return $mostRecentActivity;
	}

	public function sort()
	{
		usort($this->territoriesOut, function (Territory $a, Territory $b) {
			return $a->out - $b->out;
		});
	}

	/**
	 * @return Territory[]
	 */
	function getIdealReturnDates()
	{
		$this->sort();
		return $this->territoriesOut;
	}

	/**
	 * @return Territory[]
	 */
	public function getPriority()
	{
		$territories = array();
		foreach ($this->territories as $territoryCollection) {
			$territory = $territoryCollection->mostRecent();
			if (!empty($territory->in)) {
				$territories[$territory->territory] = $territory;
			}
		}

		usort($territories, function (Territory $a, Territory $b) {
			return $a->in - $b->in;
		});

		return $territories;
	}

	public function lastTerritoryName()
	{

		//First look up folder structure Document / Folder / Placemark
		$last = $this->territoryXML->xpath(<<<XPATH
//kml:Document
    /kml:Folder[last()]
XPATH
);
		if (!empty($last[0]->Placemark)) {
			$name = $last[0]->Placemark->name . '';
			return $name;
		}


		//If the above structure doesn't exist, look up folder structure Document / Placemark
		else {
			foreach($this->territoryActivityXML as $xml) {
				$last = $xml->xpath(<<<XPATH
//kml:Document
    /kml:Placemark[last()]
XPATH
);
				if (!empty($last[0])) {
					$name = $last[0]->name . '';
					return $name;
				}
			}
		}

		//If all fails, return empty string.
		return '';
	}
}