<!DOCTYPE html>
<html>
<meta charset="UTF-8">
<body>

<?php

	// --------------------------------------------------------------------------------------------
	// STEAM API - GETTING INFORMATIONS
	// --------------------------------------------------------------------------------------------
	
	$STEAM_APIKEY = "DB4794EB61702CB24FF458365D0B6E66";
	$STEAM_PROFILEID = "76561198037068779";
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
	// SOME SETTINGS
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
	// SAVING USER STATUS AND CHECKING IF SOMETHING CHANGED
	// --------------------------------------------------------------------------------------------
	
	$statusID = ($gameid + $personastate); // for example TF2 (440) + status Online (1) = 441
	$statusID_prev = explode("\n", file_get_contents('sig.txt'));
	
	if ($statusID_prev[0] == $statusID) {
		echo "- Nothing changed.<br><br>Current image:<br><a href='http://av.execute.run/sig/sig.png'>http://av.execute.run/sig/sig.png</a>";
		exit; 
	} else { 
		$status_file = fopen("sig.txt","w");
		fwrite($status_file, $statusID);
		echo "- New status saved to text file (sig.txt)<br><br>";
	}

	// --------------------------------------------------------------------------------------------
	// GENERATING IMAGE
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
	else if((in_array($statusID, range(212, 218)) && $gameextrainfo != '' ) || (in_array($statusID, range(441, 447)) && $gameextrainfo == '' )) // STATUS MAKING MAP IN SOURCE SDK
	{
		$background=imagecreatefrompng($av_bg_ingame);
		imagecopy($img,$background,0,0,0,0,300,100);
		imagettftext($img, 16, 0, 76, 24, $av_text_color_ingame, $av_font3, $personaname);
		imagettftext($img, 12, 0, 74, 42, $av_text_color_ingame, $av_font2, "is making map with");
		imagettftext($img, 12, 0, 74, 60, $av_text_color_ingame, $av_font2, "Hammer Editor (Source SDK)");
		imagecopy($img, $profileimg,3,3,0,0,64,64);
	}
	else if($personastate == ( '1' || '2' || '3' || '4' || '5' || '6' ) && $gameid != '') // STATUS INGAME
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

	imagepng($img, 'sig.png');
	echo "- Image generated:<br><a href='http://av.execute.run/sig/sig.png'>http://av.execute.run/sig/sig.png</a><br><br>";
	
	exit; // exit the script to avoid image errors.
?>

</body>
</html>
