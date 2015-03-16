<?php

if (!file_exists('../config.php')) {
	echo "You need to setup your config.php file in the root directory before running this.";
}

require_once("../security.php");

if (!file_exists('../bower_components')) {
    throw new Exception("It looks like you have not setup 'bower'.  Please set it up first, and continue.");
}

global $etm_config; require_once('../config.php');

$key = $etm_config->googleSpreadsheetKey;

//include and instantiate EasyTerritoryMaker
include_once('../lib/EasyTerritoryMaker.php');
$etm = new EasyTerritoryMaker();
$list = '';
$territoryAssignmentRecords = '';
$territoryAssignmentRecordsTick = 0;
$territoryAssignmentRecordsIndex = 0;
$index = 0;
$lastTerritoryNumber = $etm->lastTerritoryName();

$overview = '';
//write string from etm->all()
foreach($etm->all() as $locality) {
    $localityName = $locality->name;
    $localityNameEncoded = urlencode($locality->name);

    //The very first map is of all of the territory
    if ($index == 0) {
        $overview = <<<HTML
<li id='overview' title='Click for map of all territories' class='ui-button ui-widget territory thin' style='float: right'>
    <a href='../viewTerritories.php?index=$index' title='$localityName - Overview' target='_blank'><img src='../assets/img/web22.svg' class='territory-icon'></a>
</li>
HTML;
        $index++;
    }


    //the following maps, are of individual parts of the territory
    else {

        //If has a placemark, it is a Locality (ie Folder), so list it's Placemarks (IE Territories)
        if (!empty($locality->Placemark)) {
            foreach($locality as $territory) {
                if (!empty($territory->name)) {
                    $territoryName = $territory->name . '';

                    $territoryNameEncoded = urlencode($territory->name);

                    $status = $etm->getSingleStatus($territoryName);
	                $statusTemplate = '';
	                $publisher = '';

	                if ($status == null) {
		                $statusTemplate .= '<span style="color: green;">In</span>';
	                } else {
	                    $expected = date("m/d/Y", $status->idealReturnDate);
		                $statusTemplate .= "<span style='color: blue;'>Out - Expected $expected</span>";
		                $publisher .= $status->publisher;
	                }

                    $list .= <<<HTML
<tr onclick="window.open('../viewTerritory.php?territory=$territoryNameEncoded&locality=$localityNameEncoded', '_blank', '');">
    <td id='territory$index' class='territory' data-index='$index' >$territoryName</td>
    <td>$localityName</td>
    <td>$statusTemplate</td>
    <td>$publisher</td>
</tr>
HTML;

                    //write the territory assignment records
                    if ($territoryAssignmentRecordsTick == 0) {
	                    $beginningIndex = $index;
	                    $endIndex = $index + 4;
                        $territoryAssignmentRecords .= "<li><a href='../viewTerritoryAssignmentRecords.php?at=$index&max=$lastTerritoryNumber' target='_blank'>Set " . $beginningIndex . ' to ' . $endIndex . "</a></li>";
	                    $territoryAssignmentRecordsIndex++;
	                    $territoryAssignmentRecordsTick++;
                    }

	                if ($territoryAssignmentRecordsTick >= 5) {
                        $territoryAssignmentRecordsTick = 0;
                    } else {
                        $territoryAssignmentRecordsTick++;
                    }

	                $index++;
                }
            }
        }

        //Otherwise it is a Placemark (ie Territory)
        else {
            $status = $etm->getSingleStatus($localityName . '');

            //write the standard list of territories
            $list .= "<tr>
                <td id='territory$index' class='territory' data-index='$index'>
                    <a href='../viewTerritory.php?territory=$localityNameEncoded&index=$index' target='_blank'>$localityName</a>
                </td>
                <td>$status</td>
            </tr>";

            $index++;
        }
    }
}


//create priority
$priority = '';
$dateFormat = $etm_config->dateFormat;
foreach($etm->getPriority() as $territory) {
    $publisher = $territory->publisher;
    $territoryNameEncoded = urlencode($territory->territory);
	$territoryLocalityEncoded = urlencode($territory->locality);
    $date = date($dateFormat, $territory->in);
    $priority .= <<<HTML
<tr onclick="window.open('../viewTerritory.php?territory=$territoryNameEncoded&locality=$territoryLocalityEncoded', '_blank', '');">
    <td class='center'>{$territory->territory}</td>
    <td class='center'>$date</td>
</tr>
HTML;
}


//create ideal return dates
$idealReturnDates = '';
foreach($etm->getIdealReturnDates() as $territory) {
	$territoryNameEncoded = urlencode($territory->territory);
	$localityNameEncoded = urlencode($territory->locality);
    $date = date($dateFormat, $territory->idealReturnDate);

    $idealReturnDates .= <<<HTML

<tr onclick='document.location = "../viewTerritory.php?territory=$territoryNameEncoded&locality=$localityNameEncoded";'>
    <td>{$territory->publisher}</td>
    <td>{$territory->territory}</td>
    <td>{$territory->locality}</td>
    <td>$date</td>
</tr>
HTML;
}
?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<title>Territories</title>
    <script src="../bower_components/jquery/dist/jquery.js"></script>
    <script src="../bower_components/jquery-ui/jquery-ui.js"></script>

    <link href="../bower_components/jquery-ui/themes/smoothness/jquery-ui.css" type="text/css" rel="Stylesheet" />
    <link href="../assets/style.css" type="text/css" rel="Stylesheet" />
	<script>
		$(function() {

			$('.ui-button').button();

			$('#tabs').tabs();

			$('#spreadsheet').mousedown(function(e) {
				var a = $(this).find('a');
				window.open(a.attr('href'), '_blank', '');
				e.stopPropagation();
				e.preventDefault();
			});
		});
	</script>
</head>
<body>
	<div id="tabs">
		<ul>
			<li><a href="#list">List</a></li>
			<li><a href="#priority">Need Reworked</a></li>
			<li><a href="#idealReturnDates">Ideal Return Dates</a></li>
			<li><a href="#territoryAssignmentRecords">Territory Assignment Records</a></li>
			<li id="spreadsheet" class="ui-button ui-widget thin" style="float:right;">
				<a href="https://docs.google.com/spreadsheets/d/<?php echo $key; ?>" target="_top"><img src="../assets/img/spreadsheet7.svg" class="territory-icon"></a>
			</li>
			<?php echo $overview;?>
		</ul>
		<div id="list">
			<table class="territory-detail">
				<thead>
					<tr>
						<th>Territory</th>
						<th>Locality</th>
						<th>Status</th>
						<th>Publisher</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $list;?>
				</tbody>
			</table>
		</div>
		<div id="priority">
			<table class="territory-detail">
				<thead>
					<tr>
						<th>Territory</th>
						<th>Last Worked On Date</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $priority;?>
				</tbody>
			</table>
		</div>
		<div id="idealReturnDates">
			<table class="territory-detail">
				<thead>
					<tr>
						<th>Publisher</th>
						<th>Territory</th>
						<th>Locality</th>
						<th>Date</th>
					</tr>
				</thead>
				<tbody>
					<?php echo $idealReturnDates;?>
				</tbody>
			</table>
		</div>
        <div id="territoryAssignmentRecords">
            <ul class="assignment-record-list">
                <?php echo $territoryAssignmentRecords; ?>
            </ul>
        </div>
	</div>
</body>
</html>