<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Steam Signature</title>
</head>
<body>
    <?php

    // This is my attempt to create a new version of this script.
    // First script was created almost 10 years ago and might not work anymore.
    // To be honest I don't like PHP at all, I don't know this language, I prefer JS/TS, but my curiosity leads me here.
    // So I used Claude.ai to help me with the code to make it modern and more professional.
    // This code have some errors to fix, needs to be tweaked and improved.
    // Maybe someday I will finish it, but for now I have more interesting projects to work on.

    define('repository_url', 'https://github.com/Avaray/personal-steam-signature');
    
    $config = require 'config.php';

    if (empty($config['steam_id'])) {
        die('ERROR: Steam ID not specified in config.php');
    }

    if (empty($config['steam_api_key'])) {
        die('ERROR: Steam API key not specified in config.php');
    }

    // --------------------------------------------------------------------------------------------
    // STEAM API - GETTING INFORMATION
    // --------------------------------------------------------------------------------------------

    $base_url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/';
    $api_url = "{$base_url}?key={$config['steam_api_key']}&steamids={$config['steam_id']}";

    function get_player_summaries($api_url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) {
            return null;
        }
        return json_decode($response, true);
    }

    $summary = get_player_summaries($api_url);

    if (!isset($summary['response']['players'][0])) {
        $status_url = 'https://steamstat.us/';
        die("ERROR: Could not retrieve info. Check your API key or visit {$status_url} to check Steam services status.");
    }

    if (empty($summary['response']['players'])) {
        die('ERROR: Invalid Steam Community ID. Must be valid SteamID64.');
    }

    $summary = isset($summary['response']['players'][0]) ? $summary['response']['players'][0] : null;
    if (!$summary) {
        die('ERROR: Could not retrieve info');
    }

    $personaname = strtoupper(isset($summary['personaname']) ? $summary['personaname'] : '');
    $avatar = isset($summary['avatarmedium']) ? $summary['avatarmedium'] : '';
    $personastate = isset($summary['personastate']) ? $summary['personastate'] : '';
    $gameextrainfo = isset($summary['gameextrainfo']) ? $summary['gameextrainfo'] : '';
    $gameid = isset($summary['gameid']) ? $summary['gameid'] : '';
    
    // Until now everything works
    exit();

    // --------------------------------------------------------------------------------------------
    // IMAGE GENERATION SETTINGS
    // --------------------------------------------------------------------------------------------

    chdir(dirname(__FILE__));

    $img = imagecreatetruecolor(470, 70);

    $profile_image = @imagecreatefromstring(file_get_contents($avatar));

    $backgrounds = [
        'offline' => 'img/bg_offline.png',
        'ingame' => 'img/bg_ingame.png',
        'online' => 'img/bg_online.png',
        'error' => 'img/bg_error.png'
    ];

    $avatars = [
        'error' => 'img/avatar_error.png'
    ];

    $fonts = [
        'regular' => 'font/RobotoCondensed-Regular.ttf',
        'italic' => 'font/RobotoCondensed-Italic.ttf',
        'bold_italic' => 'font/RobotoCondensed-BoldItalic.ttf'
    ];

    $text_colors = [
        'offline' => imagecolorallocate($img, 190, 190, 190),
        'ingame' => imagecolorallocate($img, 238, 238, 238),
        'online' => imagecolorallocate($img, 238, 238, 238)
    ];

    // --------------------------------------------------------------------------------------------
    // CHECK USER STATUS
    // --------------------------------------------------------------------------------------------

    $status_file = 'status.txt';
    $statusID = $gameid + $personastate;
    $statusID_prev = file_exists($status_file) ? explode("\n", file_get_contents($status_file)) : [];

    if (isset($statusID_prev[0]) && $statusID_prev[0] == $statusID) {
        echo "- Nothing changed.<br><br>Current image:<br><a href='sig.png'>sig.png</a>";
        exit;
    } else {
        file_put_contents($status_file, $statusID);
        echo "- New status saved to text file (sig.txt)<br><br>";
    }

    // --------------------------------------------------------------------------------------------
    // GENERATING IMAGE
    // --------------------------------------------------------------------------------------------

    $w = 300;
    $h = 70;

    $img = imagecreatetruecolor($w, $h);

    function load_image($path)
    {
        $image = @imagecreatefrompng($path);
        if (!$image) {
            die("ERROR: Could not load image $path");
        }
        return $image;
    }

    if (!$summary) {
        $background = load_image($backgrounds['error']);
        imagecopy($img, $background, 0, 0, 0, 0, $w, $h);
        $avatar_error = load_image($avatars['error']);
        imagecopy($img, $avatar_error, 3, 3, 0, 0, 64, 64);
        imagettftext($img, 12, 0, 74, 42, $text_colors['offline'], $fonts['italic'], "ERROR: could not retrieve info");
    } else {
        switch ($personastate) {
            case 0:
                $background = load_image($backgrounds['offline']);
                imagecopy($img, $background, 0, 0, 0, 0, $w, $h);
                imagettftext($img, 16, 0, 76, 24, $text_colors['offline'], $fonts['bold_italic'], $personaname);
                imagettftext($img, 12, 0, 74, 42, $text_colors['offline'], $fonts['italic'], "OFFLINE");
                break;
            case 1:
                if ($gameid == 0) {
                    $state = 'is online';
                } elseif ($gameextrainfo) {
                    $state = 'is playing';
                } else {
                    $state = 'is playing a game';
                }
                $background = load_image($backgrounds['ingame']);
                imagecopy($img, $background, 0, 0, 0, 0, $w, $h);
                imagettftext($img, 16, 0, 76, 24, $text_colors['ingame'], $fonts['bold_italic'], $personaname);
                imagettftext($img, 12, 0, 74, 42, $text_colors['ingame'], $fonts['italic'], $state);
                imagettftext($img, 12, 0, 74, 60, $text_colors['ingame'], $fonts['italic'], $gameextrainfo);
                break;
            default:
                $states = [
                    1 => 'ONLINE',
                    2 => 'ONLINE (Busy)',
                    3 => 'ONLINE (Away)',
                    4 => 'ONLINE (Snooze)',
                    5 => 'Looking to Trade',
                    6 => 'Looking to Play'
                ];
                $state = isset($states[$personastate]) ? $states[$personastate] : 'ONLINE';
                $background = load_image($backgrounds['online']);
                imagecopy($img, $background, 0, 0, 0, 0, $w, $h);
                imagettftext($img, 16, 0, 76, 24, $text_colors['online'], $fonts['bold_italic'], $personaname);
                imagettftext($img, 12, 0, 74, 42, $text_colors['online'], $fonts['italic'], $state);
        }
        imagecopy($img, $profile_image, 3, 3, 0, 0, 64, 64);
    }

    imagepng($img, 'sig.png');
    imagedestroy($img);

    echo "- Image generated:<br><a href='sig.png'>sig.png</a><br><br>";
    ?>
</body>
</html>
