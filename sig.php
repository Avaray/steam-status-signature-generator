<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Steam Signature Generator</title>
</head>
<body>
    <?php

// This is my attempt to create a new version of this script.
// First script was created almost 10 years ago and might not work anymore.
// To be honest I don't like PHP at all, I don't know this language, I prefer JS/TS, but my curiosity leads me here.
// So I used Claude.ai to help me with the code to make it modern and more professional.
// This code have some errors to fix, needs to be tweaked and improved.
// Maybe someday I will finish it, but for now I have more interesting projects to work on.

// Check if GD is installed
$gd_functions = get_extension_funcs("gd");
if (!$gd_functions) {
    die("GD not installed. Please install/enable GD extension.");
}

// Check if cURL is installed
$curl_functions = get_extension_funcs("curl");
if (!$curl_functions) {
    die("cURL not installed. Please install/enable cURL extension.");
}

$steam_id = '';
$steam_api_key = '';

// Check if arguments are passed to the script
if (!empty($argv)) {
    $args = explode(',', $argv[0]);
    foreach ($args as $arg) {
        $arg = explode('=', $arg);
        if (count($arg) === 2 && !empty($arg[1])) {
            if ($arg[0] === 'steam_id') {
                echo "Steam ID found in arguments.\n";
                $steam_id = $arg[1];
            } elseif ($arg[0] === 'steam_api_key') {
                echo "Steam API Key found in arguments\n";
                $steam_api_key = $arg[1];
            }
        }
    }
}

// Check if arguments are passed in the URL
// Server should be configured to allow URL query parameters
// Otherwise array $_GET will be empty
if (isset($_GET['steam_id']) && !empty($_GET['steam_id'])) {
    echo "Steam ID found in the URL.\n";
    $steam_id = $_GET['steam_id'];
}

if (isset($_GET['steam_api_key']) && !empty($_GET['steam_api_key'])) {
    echo "Steam API Key found in the URL.\n";
    $steam_api_key = $_GET['steam_api_key'];
}

echo "Steam ID: {$steam_id}\n";
echo "Steam API Key: {$steam_api_key}\n";

// here I am currently, will continue later
// im adding some code to find variables in various places

exit();

define('repository_url', 'https://github.com/Avaray/personal-steam-signature');

$config = require 'config.php';

// Check if required fields are set
if (empty($config['steam_id'])) {
    die('ERROR: Steam ID not specified in config.php');
}

if (empty($config['steam_api_key'])) {
    die('ERROR: Steam API key not specified in config.php');
}

// --------------------------------------------------------------------------------------------
// STEAM API - GETTING INFORMATION
// --------------------------------------------------------------------------------------------

// https://partner.steamgames.com/doc/webapi/ISteamUser#GetPlayerSummaries
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$api_base_url = "{$protocol}://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/";
$api_url = "{$api_base_url}?key={$config['steam_api_key']}&steamids={$config['steam_id']}";

function get_player_summaries($api_url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response_code === 0) {
        die("ERROR: cURL request failed. Check internet connection and SSL.");
    }

    curl_close($ch);
    if ($response === false) {
        return null;
    }

    $json = json_decode($response, true);
    return $json;
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

$personaname = isset($summary['personaname']) ? $summary['personaname'] : '';
$avatar = isset($summary['avatarmedium']) ? $summary['avatarmedium'] : '';
$personastate = isset($summary['personastate']) ? $summary['personastate'] : '';
$gameextrainfo = isset($summary['gameextrainfo']) ? $summary['gameextrainfo'] : '';
$gameid = isset($summary['gameid']) ? $summary['gameid'] : '';

// --------------------------------------------------------------------------------------------
// IMAGE GENERATION SETTINGS
// --------------------------------------------------------------------------------------------

chdir(dirname(__FILE__));

function get_text_width($text, $font, $size)
{
    $bbox = imagettfbbox($size, 0, $font, $text);
    return abs($bbox[2] - $bbox[0]);
}

if ($config['capitalized_personaname']) {
    $personaname = $personaname;
}

$backgrounds = [
    'offline' => 'img/bg_offline.png',
    'ingame' => 'img/bg_ingame.png',
    'online' => 'img/bg_online.png',
    'error' => 'img/bg_error.png',
];

$avatars = [
    'error' => 'img/avatar_error.png',
];

$fonts = [
    'regular' => 'font/RobotoCondensed-Regular.ttf',
    'italic' => 'font/RobotoCondensed-Italic.ttf',
    'bold_italic' => 'font/RobotoCondensed-BoldItalic.ttf',
];

$avatar_size = 64;
$font_name = $fonts['bold_italic'];
$font_size_personaname = 24;
$font_size_default = 16;

$max_text_width = max(
    get_text_width($personaname, $font_name, $font_size_personaname),
    get_text_width($gameextrainfo, $font_name, $font_size_default)
);

$padding = 10;
$image_width = $avatar_size + $max_text_width + $padding * 4;
$image_height = $padding * 3 + $font_size_personaname + $font_size_default;

$img = imagecreatetruecolor($image_width, $image_height);

$text_colors = [
    'offline' => imagecolorallocate($img, 190, 190, 190),
    'ingame' => imagecolorallocate($img, 238, 238, 238),
    'online' => imagecolorallocate($img, 238, 238, 238),
];

$avatar_start = $padding + $avatar_size;
$personaname_start = $padding * 2 + $avatar_size + $font_size_personaname;
$personastate_start = $padding + $personaname_start + $font_size_default;

// --------------------------------------------------------------------------------------------
// CHECK USER STATUS
// --------------------------------------------------------------------------------------------

$status_prev = null;
$status_now = "{$gameid}-{$personastate}-{$personaname}";
$status_file_path = "status/{$config['steam_id']}";

if (!file_exists($status_file_path)) {
    file_put_contents($status_file_path, '0');
} else {
    $status_prev = file_exists('status.txt') ? file_get_contents('status.txt') : '';
}

if ($status_prev === $status_now) {
    die('Nothing changed.');
} else {
    file_put_contents($status_file_path, $status_now);
}

echo 'Until now everything should be working fine.';

# Rest of work for tomorrow

// --------------------------------------------------------------------------------------------
// GENERATING IMAGE
// --------------------------------------------------------------------------------------------

$profile_image = @imagecreatefromstring(file_get_contents($avatar));

function generate_image($state)
{
    $background = @imagecreatefrompng($backgrounds[$state]);
    imagecopy($img, $background, 0, 0, 0, 0, $image_width, $image_height);
    imagettftext($img, $font_size_personaname, 0, $padding, $personaname_start, $text_colors[$state], $fonts['bold_italic'], $personaname);
    imagettftext($img, $font_size_default, 0, $padding, $personastate_start, $text_colors[$state], $fonts['italic'], $state);
    // if ($gameextrainfo) {
    //     imagettftext($img, $font_size_default, 0, $padding, $personastate_start + $font_size_default + $padding, $text_colors[$state], $fonts['italic'], $gameextrainfo);
    // }
    return $img;
}

// switch ($personastate) {
//     case 0:
//         generate_image('offline');
//         break;
//     case 1:
//         generate_image('ingame');
//         break;
//     default:
//         generate_image('online');
//         break;
// }

switch ($personastate) {
    case 1:
        $background = @imagecreatefrompng($backgrounds['offline']);
        imagecopy($img, $background, 0, 0, 0, 0, $image_width, $image_height);
        imagettftext($img, $font_size_personaname, 0, $padding, $personaname_start, $text_colors['offline'], $fonts['bold_italic'], $personaname);
        imagettftext($img, $font_size_default, 0, $padding, $personastate_start, $text_colors['offline'], $fonts['italic'], 'OFFLINE');
        break;
}

// imagecopy($img, $profile_image, $padding, $padding, 20, 20, $avatar_size, $avatar_size);

// save image
imagepng($img, 'sig.png');

?>
</body>
</html>
