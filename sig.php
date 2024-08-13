<?php

// --------------------------------------------------------------------------------------------
// CONFIGURATION
// --------------------------------------------------------------------------------------------

// Set the current directory to the script directory
chdir(dirname(__FILE__));

// Declare global variables
$steam_ids = array();
$steam_api_key = '';
$config = [];
$database = array();

// Custom echo function with timestamp
function msg($message, $die = false)
{
    $dateTime = date('Ymd.His'); // YYYYMMDD.HHMMSS
    echo "\e[34m{$dateTime}\e[0m - {$message}\n";
    if ($die) {
        die();
    }
}

// Read config file if it exists
class FileNotFoundException extends Exception
{}

try {
    $configFile = 'config.json';

    if (!file_exists($configFile)) {
        throw new FileNotFoundException("The configuration file (config.json) does not exist.");
    }

    $config = json_decode(file_get_contents('config.json'), true);

    // Set timezone if specified
    // It should be set before any msg function call
    if (isset($config['timezone'])) {
        date_default_timezone_set($config['timezone']);
    }

    $php_version = phpversion();
    msg("Starting script with \e[35mPHP {$php_version}\e[0m");

} catch (FileNotFoundException $e) {
    msg($e->getMessage());
} catch (Throwable $e) {
    msg($e->getMessage());
}

// Check if Steam API Key is set in the config file
if (!empty($config['key']) && empty($steam_api_key)) {
    msg("Steam API Key found in the config file.");
    $steam_api_key = $config['key'];
}

// Check if input_file is set in the config file
if (!empty($config['input_file'])) {
    $file_path = $config['input_file'];
    if (file_exists($file_path)) {
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if ($file_extension === 'json') {
            $json = json_decode(file_get_contents($file_path), true);
            if (isset($json['ids'])) {
                $steam_ids = $json['ids'];
            }
        } elseif ($file_extension === 'txt') {
            $content = file_get_contents($file_path);
            $extracted = preg_split('/[^\d]+/', $content);
            $steam_ids = array_filter($extracted);
        } else {
            msg("Unsupported file extension .{$file_extension} for the input file.");
        }
        $amount = count($steam_ids);
        if ($amount === 0) {
            msg("No Steam IDs found in the input file.");
        } elseif ($amount === 1) {
            msg("Steam ID {$steam_ids[0]} found in the input file.");
        } else {
            msg("Found {$amount} Steam IDs in the input file.");
        }
    } else {
        msg("Input file not found: {$file_path}");
    }
}

// Check if at least one Steam ID is set in the config file
if (empty($steam_ids) && count($config['ids']) > 0) {
    // Commented way of merging arrays is 5.4 compatible
    // $steam_ids = array_merge($steam_ids, $config['steam_ids']);
    $steam_ids = $config['ids']; // This should be enough for PHP 5.4
    $amount = count($steam_ids);
    if ($amount === 1) {
        msg("Steam ID {$steam_ids[0]} found in the config file.");
    } else {
        msg("Found {$amount} Steam IDs in the config file.");
    }
}

// Check if GD is installed (required for image generation)
$gd_functions = get_extension_funcs("gd");
if (!$gd_functions) {
    msg("GD not installed. Please install/enable GD extension.", true);
}

// Check if cURL is installed (required for Steam API requests)
$curl_functions = get_extension_funcs("curl");
if (!$curl_functions) {
    msg("cURL not installed. Please install/enable cURL extension.", true);
}

// Check if Steam API Key is set in the environment variables
$steam_api_key_env = getenv('STEAM_API_KEY');
if (empty($steam_api_key_env)) {
    msg("Steam API Key found in environment variables.");
    $steam_api_key = $steam_api_key_env;
}

// Read arguments passed in the command line
if (!empty($argv) && empty($steam_ids)) {
    for ($i = 1; $i < count($argv); $i++) {
        $arg = explode('=', $argv[$i]);
        if (count($arg) === 2 && !empty($arg[1])) {
            if ($arg[0] === 'ids') {
                $ids = explode(',', $arg[1]);
                $amount = count($ids);
                if ($amount === 1) {
                    msg("Steam ID {$ids[0]} found in arguments.");
                } else {
                    msg("Found {$amount} Steam IDs in arguments.");
                }
                $steam_ids = $ids;
            } elseif ($arg[0] === 'key' && empty($steam_api_key)) {
                msg("Steam API Key found in arguments.");
                $steam_api_key = $arg[1];
            } else {
                msg("Found unknown argument: {$arg[0]}");
            }
        }
    }
}

// Check if things passed in the URL
// Server should be configured to allow URL query parameters
// Otherwise array $_GET will be empty
// This shouldn't throw any errors even if the server is not configured
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        if ($key === 'ids') {
            $ids = explode(',', $value);
            $amount = count($ids);
            if ($amount === 1) {
                msg("Steam ID {$ids[0]} found in the URL.");
            } else {
                msg("Found {$amount} Steam IDs in the URL.");
            }
            $steam_ids = $ids;
        } elseif ($key === 'key' && empty($steam_api_key)) {
            msg("Steam API Key found in the URL.");
            $steam_api_key = $value;
        } else {
            msg("Found unknown argument: {$key}");
        }
    }
}

// Check if Steam API Key is set in the URL
if (isset($_GET['steam_api_key']) && !empty($_GET['steam_api_key']) && empty($steam_api_key)) {
    msg("Steam API Key found in the URL.");
    $steam_api_key = $_GET['steam_api_key'];
}

// Last check for Steam ID
if (empty($steam_ids)) {
    msg("Steam ID not found. Please set Steam ID in the config file or pass it as an argument.", true);
}

// Last check for Steam API Key
if (empty($steam_api_key)) {
    msg("Steam API Key not found. Please set Steam API Key in the config file or pass it as an argument.", true);
}

// Check if database file exists and load it
if (file_exists('db.json')) {
    $database = json_decode(file_get_contents('db.json'), true);
    msg("Database loaded with " . count($database) . " entries.");
}

// Function to save database to a file
function save_database($database)
{
    file_put_contents('db.json', json_encode($database, JSON_PRETTY_PRINT));
    msg("Database saved with " . count($database) . " entries.");
}

function round_up($number)
{
    return ceil($number * 10) / 10;
}

// Function to calculate minimal interval based on the number of Steam IDs
function calculate_min_interval($steam_ids, $output_format = 'seconds', $info = false)
{
    $max_requests_per_day = 100000;
    $max_entries_per_request = 100;
    $max_requests_per_process = ceil(count($steam_ids) / $max_entries_per_request);
    $seconds_per_day = 86400;
    $min_interval = $seconds_per_day / $max_requests_per_day * $max_requests_per_process;
    $rounded = ceil($min_interval * 10) / 10;
    if ($output_format === 'ms') {
        $rounded = $rounded * 1000;
    } elseif ($output_format === 'minutes') {
        $rounded = $rounded / 60;
    } elseif ($output_format === 'hours') {
        $rounded = $rounded / 3600;
    }
    if ($info) {
        msg("Recommended minimum request interval: {$rounded} {$output_format}");
    }
    return $rounded;
}

// Function to clean the database from old entries (remove entries that does not exist in the current list of Steam IDs)
function clean_database($database, $steam_ids)
{
    $new_database = [];
    foreach ($database as $entry) {
        if (in_array($entry['steam_id'], $steam_ids)) {
            $new_database[] = $entry;
        }
        $old_entries_removed = count($database) - count($new_database);
        if ($old_entries_removed > 0) {
            msg("Removed {$old_entries_removed} old entries from the database.");
        }
    }
    return $new_database;
}

// Until now everything should be working fine. Commiting.
exit();

// --------------------------------------------------------------------------------------------
// STEAM API - GETTING INFORMATION
// https://partner.steamgames.com/doc/webapi/ISteamUser#GetPlayerSummaries
// --------------------------------------------------------------------------------------------

// Set proper protocol based on the server configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Set the base URL for the Steam API request
$api_base_url = "{$protocol}://api.steampowered.com/ISteamUser/GetPlayerSummaries/v2/";

// Set the full URL for the Steam API request
$api_url = "{$api_base_url}?key={$steam_api_key}&steamids={$config['steam_id']}";

// Function to get player summaries from the Steam API
function get_player_summaries($api_url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($response_code === 0) {
        msg("ERROR: cURL request failed. Check internet connection and SSL.", true);
    }

    curl_close($ch);
    if ($response === false) {
        return null;
    }

    $json = json_decode($response, true);
    return $json;
}

// Get player summaries from the Steam API
$summary = get_player_summaries($api_url);

// Check if the response returned object
if (!isset($summary['response']['players'][0])) {
    $status_url = 'https://steamstat.us/';
    msg("ERROR: Could not retrieve info. Check your API key or visit {$status_url} to check Steam services status.", true);
}

// Check if the response is empty
if (empty($summary['response']['players'])) {
    msg('ERROR: Invalid Steam Community ID. Must be valid SteamID64.', true);
}

// Shorter alternative to previous checks
// I need to think which approach I want to use
$summary = isset($summary['response']['players'][0]) ? $summary['response']['players'][0] : null;
if (!$summary) {
    msg('ERROR: Could not retrieve info from Steam API.', true);
}

// Set default empty values for the variables in case they are not in the response
$personaname = isset($summary['personaname']) ? $summary['personaname'] : '';
$avatar = isset($summary['avatarmedium']) ? $summary['avatarmedium'] : '';
$personastate = isset($summary['personastate']) ? $summary['personastate'] : '';
$gameextrainfo = isset($summary['gameextrainfo']) ? $summary['gameextrainfo'] : '';
$gameid = isset($summary['gameid']) ? $summary['gameid'] : '';

// --------------------------------------------------------------------------------------------
// IMAGE GENERATION SETTINGS
// --------------------------------------------------------------------------------------------

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
];

$avatars = [
    'error' => 'img/avatar_error.png',
];

// Define default fonts
$font_primary = 'fonts/RobotoCondensed-BoldItalic.ttf';
$font_secondary = 'fonts/RobotoCondensed-Regular.ttf';

// Check if the primary font is specified in the config file and if it exists in the fonts directory
if (isset($config['font_primary'])) {
    $primary = "fonts/{$config['font_primary']}";
    if (!file_exists($primary)) {
        msg("Specified primary font not found: {$primary}");
    } else {
        $font_primary = $primary;
    }
}

// Check if the secondary font is specified in the config file and if it exists in the fonts directory
if (isset($config['font_secondary'])) {
    $secondary = "fonts/{$config['font_secondary']}";
    if (!file_exists($secondary)) {
        msg("Specified secondary font not found: {$secondary}");
    } else {
        $font_secondary = $secondary;
    }
}

exit();

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

msg('Until now everything should be working fine.');

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
