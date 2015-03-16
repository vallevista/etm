<?php
    global $security;

    $security = (isset($security) ? $security : null);


    if ($security == null) {
        require_once("security.php");
    }

	$_REQUEST = array_merge(array(
		"territory" => "1",
		"congregation" => "",
		"locality" => ""
	), $_REQUEST);

    if (!file_exists('my_files/card.png')) {
        //throw new Exception("It looks like you don't yet have the 'card.png' file in the 'my_files' folder.  Please scan a S-12-E or similar and place there to continue.  This is not a digitally distributed file, which is why this measure is in place.");
    }

	require_once('lib/EasyTerritoryMaker.php');
	$etm = new EasyTerritoryMaker($security);
	$territory = $etm->lookup($_REQUEST['territory']);
    if ($territory === null) {
        echo "You entered something that did not match what is on file.  Inform the territory servant so you can proceed.";
        exit;
    }

?><!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="apple-mobile-web-app-capable" content="yes">

	<title>Territory <?php echo $territory->territory ?></title>

	<link href="bower_components/jquery-ui/themes/smoothness/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<link href="bower_components/leaflet/dist/leaflet.css" type="text/css" rel="Stylesheet" />

	<script src="bower_components/jquery/dist/jquery.js"></script>
    <script src="bower_components/jquery-ui/ui/jquery-ui.js"></script>
    <script src="bower_components/jquery-ui/ui/i18n/jquery-ui-i18n.js"></script>
    <script src="bower_components/leaflet/dist/leaflet.js"></script>
	<script src="bower_components/togeojson/togeojson.js"></script>

    <style type="text/css">
        html, body {
            padding: 0;
            margin: 0;
        }
        .olPopup p {
            margin:0px;
        }
        #card {
	        width: 100%;
        }
        #cardLabel {
	        position: absolute;
        }
        #cardLabel .top {
	        cursor: pointer;
        }
        #cardLabel table, #cardLabel table td {
            border: 1px solid #C3C3C3;
            border-collapse: collapse;
        }
        #cardLabel table th {
            border: 1px solid #C3C3C3;
            text-align: center;
            font-weight: bold;
        }
        h3 {
            margin: 0px;
            padding: 0px;
            width: 100%;
        }
	    #directions ul {
		    padding-left: 7px;
	    }
	    #aerial-map-note {
		    color: red;
		    line-height: auto;
	    }
    </style>
<?php if (!isset($_REQUEST['debug'])) {?>
    <style>
        .olControlAttribution,
        .olControlZoom,
        .maximizeDiv,
        .gmnoprint,
        .gm-style a {
            display: none ! important;
        }
        .ui-resizable-handle {
            background-image: none ! important;
        }
    </style>
<?php }?>
    <script>
	    var aerialMapNote,
		    map,
		    mapMini,
		    mapElement,
		    mapMiniElement,
		    north,
		    territoryName,
		    locality,
		    card,
		    cardLabel,
		    directions,
		    height,
		    width,
		    options = {
			    style: {
				    color: '#50B414',
				    weight: 5,
				    opacity: 0.65
			    }
		    };

        $(function() {


	        aerialMapNote = $('#aerial-map-note');
            card = $('#card');
	        cardLabel = $('#cardLabel');
	        directions = $('#directions');
	        mapElement = $('#map');
	        mapMiniElement = $('#mapMini');
	        north = $('#north');
            territoryName = $('#territory').val();
            locality = $('#locality').val();

	        $(window)
		        .resize(resetLabels)
		        .resize();

            //console.log(new Map(map, territoryName, locality));
            //console.log(new Map(mapMini, territoryName, locality, true));


	        map = L.map('map');
	        mapMini = L.map('mapMini');

	        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
		        attribution: '&nbsp;'
	        }).addTo(map);

	        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
		        attribution: '&nbsp;'
	        }).addTo(mapMini);

	        if (territoryName) {
		        $.when(
			        $.ajax("kmlFolder.php?territory=" + territoryName + '&locality=' + locality),
			        $.ajax("kmlFolder.php?territory=" + territoryName + '&mini&locality=' + locality)
		        ).then(function(mapXml, mapMiniXml) {
		            var mapGeoJson = L.geoJson(toGeoJSON.kml(mapXml[0]), options).addTo(map),
			            mapMiniGeoJson = L.geoJson(toGeoJSON.kml(mapMiniXml[0]), options).addTo(mapMini);

			        map.fitBounds(mapGeoJson.getBounds());
			        mapMini
				        .fitBounds(mapMiniGeoJson.getBounds())
				        .zoomOut()
				        .zoomOut()
				        .zoomOut();

				    provideDirections(map.getCenter());
		        });
	        }

        });

        function resetLabels() {
            var mapPosition = mapElement.position(),
                cardPosition = card.position();

            height = card.height();
            width = card.width();

            cardLabel.css({
                width: width + 'px',
                left: '1px',
                top: (cardPosition.top + (width * 0.08)) + 'px',
                fontSize: (width * 0.04) + 'px'
            });

            north.css({
                position: 'absolute',
                top: (mapPosition.top = (height * 0.4)) + 'px',
                left: (mapPosition.left + 10) + 'px'
            });

	        directions.css('font-size', (width * 0.011) + 'px');
	        aerialMapNote.css('font-size', (width * 0.012) + 'px');

	        north.css('top', (height * 1.2) + 'px');

	        mapElement
		        .height(height)
		        .width(width);

	        mapMiniElement.height(height * 0.57);
        }

        function onPopupClose(evt) {
            select.unselectAll();
        }

	    function provideDirections(latLng) {
		    cardLabel.find('.top').click(function() {
			    document.location = 'https://maps.google.com/maps?q=' + latLng.lat + ',' + latLng.lng;
		    });
	    }
    </script>
</head>
<body>
    <input type="hidden" id="territory" value="<?php echo $territory->territory; ?>" />
    <input type="hidden" id="locality" value="<?php echo $territory->locality; ?>" />
    <input type="hidden" id="congregation" value="<?php echo $territory->congregation; ?>" />

    <img id="card" src="my_files/card.png" />
    <table id="cardLabel" style="position: absolute;" border="0">
        <tr class="top">
            <td style="width: 3%;"></td>
            <td style="width: 10%;"></td>
            <td style="width: 35%; max-width:35%;">
                <span style="position: absolute; max-width: 12em; top: 0.23em; line-height: 0.9em;"><?php echo $territory->locality; ?></span>
            </td>
            <td style="width: 1%;"></td>
            <td style="width: 10%;"></td>
            <td style="width: 12%;"><?php echo $territory->territory; ?></td>
            <td style="width: 1%;"></td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center;padding-left: 1%; vertical-align: top;">
                <div id="mapMini" style="border: none;"></div>
		        <div id="aerial-map-note">
			        (aerial map, larger map on back)
		        </div>
            </td>
            <td></td>
            <td id="directions" colspan="2">
                <h3>Directions</h3>
                <ul>
                    <li>Work <span style="font-weight: bold;">houses, apartments, and businesses</span> that are encompassed within the <span style="color: #50B414;">green highlighted area</span>.</li>
                    <li>Keep track of do not calls on front.</li>
                </ul>
                <h3>Do Not Calls</h3>
                <table style="width: 100%;" border="1">
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br style="line-height: 4px;" />
    <img id="north" src="assets/img/n.png" />
    <div id="map" class="smallmap" style="border: none;"></div>
</body>
</html>
