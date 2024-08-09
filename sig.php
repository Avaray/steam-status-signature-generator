<?php

// Set the timezone
date_default_timezone_set('UTC');

// --------------------------------------------------------------------------------------------
// STEAM API - GETTING INFORMATION
// --------------------------------------------------------------------------------------------

define('STEAM_APIKEY', '5A864F6A173036BD6D458B23198AF1D5');
define('STEAM_PROFILEID', '76561198037068779');
define('STEAM_BASEURL', 'http://api.steampowered.com');
define('STEAM_INTERFACE_USER', 'ISteamUser');
define('STEAM_FORMAT', 'json');
define('STATUS_FILE', 'sig.txt');

function get_request_url()
{
    $domain = $_SERVER['SERVER_NAME'];
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $request = $_SERVER['REQUEST_URI'];
    return $protocol . '://' . $domain . $request;
}

function get_player_summaries()
{
    $url = STEAM_BASEURL . '/' . STEAM_INTERFACE_USER . '/GetPlayerSummaries/v2/';
    $params = [
        'key' => STEAM_APIKEY,
        'steamids' => STEAM_PROFILEID,
        'format' => STEAM_FORMAT
    ];
    $url .= '?' . http_build_query($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }
    return json_decode($response, true);
}

$summary = get_player_summaries();
$summary = isset($summary['response']['players'][0]) ? $summary['response']['players'][0] : null;
if (!$summary) {
    die('ERROR: could not retrieve info');
}

$personaname = strtoupper(isset($summary['personaname']) ? $summary['personaname'] : '');
$avatar = isset($summary['avatarmedium']) ? $summary['avatarmedium'] : '';
$personastate = isset($summary['personastate']) ? $summary['personastate'] : '';
$gameextrainfo = isset($summary['gameextrainfo']) ? $summary['gameextrainfo'] : '';
$gameid = isset($summary['gameid']) ? $summary['gameid'] : '';

// --------------------------------------------------------------------------------------------
// IMAGE GENERATION SETTINGS
// --------------------------------------------------------------------------------------------

chdir(dirname(__FILE__));

$img = imagecreatetruecolor(470, 70);

$profileimg = @imagecreatefromstring(file_get_contents($avatar));

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

$statusID = $gameid + $personastate;
$statusID_prev = file_exists(STATUS_FILE) ? explode("\n", file_get_contents(STATUS_FILE)) : [];

if (isset($statusID_prev[0]) && $statusID_prev[0] == $statusID) {
    echo "- Nothing changed.<br><br>Current image:<br><a href='sig.png'>sig.png</a>";
    exit;
} else {
    file_put_contents(STATUS_FILE, $statusID);
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
    imagecopy($img, $profileimg, 3, 3, 0, 0, 64, 64);
}

imagepng($img, 'sig.png');
imagedestroy($img);

echo "- Image generated:<br><a href='sig.png'>sig.png</a><br><br>";
