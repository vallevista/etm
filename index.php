<?php
if (!file_exists('config.php')) {
	echo "You need to setup your config.php file in the root directory before running this.";
}
if (isset($_REQUEST['territory'])) {
    global $security;
    $security = true;
	require_once('viewTerritory.php');
	exit;
}
?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<title>Valle Vista Territory</title>
</head>
<body>
	<form>
        <table>
            <tr>
                <td>Territory #:</td>
                <td><input type="text" name="territory"/></td>
            </tr>
            <tr>
                <td>Your Initials:</td>
                <td><input type="text" name="initials" /></td>
	            <td>(First and Last only)</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center;">
                    <input type="submit" value="Go"/>
                </td>
            </tr>
        </table>
	</form>
</body>
</html>