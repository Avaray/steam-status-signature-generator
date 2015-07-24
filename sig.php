<!DOCTYPE html>
<html>
<meta charset="UTF-8">
<body>

<?php

	// --------------------------------------------------------------------------------------------
	// STEAM API - GETTING INFORMATIONS
	// --------------------------------------------------------------------------------------------
	
	$STEAM_APIKEY = "HERE-MUST-BE-YOUR-STEAM-API-KEY"; // Here insert your Steam Web Api Key - get from here https://steamcommunity.com/dev/apikey
	$STEAM_PROFILEID = "76561198037068779"; // Here insert your steamID64
	$error = null;
	$STEAM_BASEURL = "api.steampowered.com";
	$STEAM_FORMAT = "json";
	$STEAM_INTERFACES = array("user" => "ISteamUser");

	function get_request_url()
	{
		$domain = $_SERVER['SERVER_NAME'];
		$protocol = ((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "off") ? 'http' : 'https');
		$request = $_SERVER['REQUEST_URI'];

		return ($protocol . '://' . $domain . $request);
	}

	function GetPlayerSummaries()
	{
		global $STEAM_APIKEY, $STEAM_PROFILEID;
		global $STEAM_BASEURL, $STEAM_FORMAT, $STEAM_INTERFACES;

		$url = 'http://' . $STEAM_BASEURL . '/' . $STEAM_INTERFACES['user'] . '/GetPlayerSummaries/v0002/?key=' . $STEAM_APIKEY . '&steamids=' . $STEAM_PROFILEID . '&format=' . $STEAM_FORMAT;

		$response = '';

		$referer = get_request_url();
		$headers = "GET {$url} HTTP/1.1\r\n";
		$headers .= "Host: {$STEAM_BASEURL}\r\n";
		$headers .= "Referer: {$referer}\r\n";
		$headers .= "Connection: close\r\n";

		$headers .= "\r\n";
		$fp = fsockopen($STEAM_BASEURL, 80, $errno, $errstr, 60);
		if($fp)
		{
			fwrite($fp, $headers);
			while (!feof($fp))
			{
				$response .= fgets($fp, 128);
			}
			fclose($fp);
			$response = explode("\r\n\r\n", $response, 2);
			$response = $response[1];
		}
		else
		{
			$error = "ERROR: could not retrieve info"; 
		}
		if(!empty($response))
		{
			return json_decode($response, true);
		}
		else
		{
			return null;
			echo ($gameextrainfo);
		}
	}

	$summary = GetPlayerSummaries();
	$summary = $summary['response']['players'][0];
	if($summary != null)
	{
		$personaname = (isset($summary['personaname']) ? $summary['personaname'] : '');
		$avatar = (isset($summary['avatarmedium']) ? $summary['avatarmedium'] : '');
		$personastate = (isset($summary['personastate']) ? $summary['personastate'] : '');
		$gameextrainfo = (isset($summary['gameextrainfo']) ? $summary['gameextrainfo'] : '');
		$gameid = (isset($summary['gameid']) ? $summary['gameid'] : '');
	}
	else
	{
		$error = "ERROR: could not retrieve info";
	}
	
	// --------------------------------------------------------------------------------------------
	// SOME RANDOM SETTINGS
	// --------------------------------------------------------------------------------------------
	
	chdir(dirname(__FILE__));
	
	$img = imagecreatetruecolor(470, 70);
	
	$profileimg = imagecreatefromstring(file_get_contents($avatar));
	
	$av_bg_offline=('img/bg_offline.png');
	$av_bg_ingame=('img/bg_ingame.png');
	$av_bg_online=('img/bg_online.png');
	
	$av_bg_error=('img/bg_error.png');
	$av_avatar_error=('img/avatar_error.png');
	
	$av_font1=('font/RobotoCondensed-Regular.ttf');
	$av_font2=('font/RobotoCondensed-Italic.ttf');
	$av_font3=('font/RobotoCondensed-BoldItalic.ttf');
	
	$av_text_color_offline=imagecolorallocate($img, 190, 190, 190);
	$av_text_color_ingame=imagecolorallocate($img, 238, 238, 238);
	$av_text_color_online=imagecolorallocate($img, 238, 238, 238);
	
	
	// --------------------------------------------------------------------------------------------
	// SAVING THE UNIQUE STATUS AND CHECKING FOR CHANGES
	// --------------------------------------------------------------------------------------------
	
	$statusID = ($gameid + $personastate); // for example Team Fortress 2 ID is 440 + status Online (1) = 441 and this is Unique ID. this is my strange idea, but it works.
	$statusID_prev = explode("\n", file_get_contents('sig.txt'));
	
	if ($statusID_prev[0] == $statusID) {
		echo "- Nothings changed ";
		exit; 
	} else { 
		$status_file = fopen("sig.txt","w");
		fwrite($status_file, $statusID);
		echo "-  New status saved in file ";
	}

	// --------------------------------------------------------------------------------------------
	// IMAGE CREATION
	// --------------------------------------------------------------------------------------------
	
	$w = 300;
	$h = 70;

	$img = imagecreatetruecolor($w, $h);
	$personaname = strtoupper($personaname); // UPPERCASE LETTERS FORCE

	if($error != null) // ERROR
	{
		$background=imagecreatefrompng($av_bg_error);
		imagecopy($img,$background,0,0,0,0,300,100);
		$avatar_error=imagecreatefrompng($av_avatar_error);
		imagecopy($img, $avatar_error,3,3,0,0,64,64);
		imagettftext($img, 12, 0, 74, 42, $av_text_color_offline, $av_font2, "ERROR: could not retrieve info");
	}

	if($personastate == 0) // STATUS OFFLINE
	{
		$background=imagecreatefrompng($av_bg_offline);
		imagecopy($img,$background,0,0,0,0,300,100);
		imagettftext($img, 16, 0, 76, 24, $av_text_color_offline, $av_font3, $personaname);
		imagettftext($img, 12, 0, 74, 42, $av_text_color_offline, $av_font2, "OFFLINE");
		imagecopy($img, $profileimg,3,3,0,0,64,64);
		// imagefilter($img, IMG_FILTER_GRAYSCALE);
	}
	else if($personastate == '1' && $gameid != '') // STATUS INGAME
	{
		($gameextrainfo != '' ? $state = 'is playing now in' : $state = 'is dropping cards right now');
		$background=imagecreatefrompng($av_bg_ingame);
		imagecopy($img,$background,0,0,0,0,300,100);
		imagettftext($img, 16, 0, 76, 24, $av_text_color_ingame, $av_font3, $personaname);
		imagettftext($img, 12, 0, 74, 42, $av_text_color_ingame, $av_font2, $state);
		imagettftext($img, 12, 0, 74, 60, $av_text_color_ingame, $av_font2, $gameextrainfo);
		imagecopy($img, $profileimg,3,3,0,0,64,64);
	}
	else // STATUS ONLINE
	{
		if ($personastate == '1' && $gameID == ''){ $state = 'ONLINE';
		} elseif ($personastate == '2'){$state = 'ONLINE (Busy)';
		} elseif ($personastate == '3'){$state = 'ONLINE (Away)';
		} elseif ($personastate == '4'){$state = 'ONLINE (Snooze)';
		} elseif ($personastate == '5'){$state = 'Looking to Trade';
		} else {$state = 'Looking to Play';
		}
		
		$background=imagecreatefrompng($av_bg_online);
		imagecopy($img,$background,0,0,0,0,300,100); 
		imagettftext($img, 16, 0, 76, 24, $av_text_color_online, $av_font3, $personaname);
		imagettftext($img, 12, 0, 74, 42, $av_text_color_online, $av_font2, $state);
		imagecopy($img, $profileimg,3,3,0,0,64,64);
	}

	// --------------------------------------------------------------------------------------------
	// OUTPUT
	// --------------------------------------------------------------------------------------------
	
	$link_address='http://YOUR-WEBSITE.COM/sig.png';
	imagepng($img, 'sig.png');
	echo "<br>- You can find your image here<br><br><a href='$link_address'>http://YOUR-WEBSITE.COM/sig.png</a>";
	exit;
?>

</body>
</html>