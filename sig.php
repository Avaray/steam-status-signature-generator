<?php

// Check if GD is installed (required for image generation)
$gd_functions = get_extension_funcs("gd");
if (!$gd_functions) {
    die("GD not installed. Please install/enable GD extension.");
}

// Check if cURL is installed (required for Steam API requests)
$curl_functions = get_extension_funcs("curl");
if (!$curl_functions) {
    die("cURL not installed. Please install/enable cURL extension.");
}

// --------------------------------------------------------------------------------------------
// FUNCTIONS
// --------------------------------------------------------------------------------------------

// Custom echo function with timestamp
// I think I will add one more argument to this function on 1st position. It will be a type of message (info, warning, error)
function msg($type = null, $message = '', $die = false)
{
    if ($type === 'warning') {
        $color = '33'; // Yellow
        $prefix = 'WARNING';
    } elseif ($type === 'error') {
        $color = '31'; // Red
        $prefix = 'ERROR';
    } elseif ($type === 'success') {
        $color = '32'; // Green
        $prefix = 'SUCCESS';
    }

    $dateTime = date('Ymd.His'); // YYYYMMDD.HHMMSS

    if (!$type) {
        echo "{$dateTime} [INFO] {$message}\n";
    } else {
        echo "{$dateTime} \e[{$color}m[{$prefix}] {$message}\e[0m\n";
    }

    if ($die) {
        die();
    }
}

// Function to validate SteamID64
function is_valid_id($id)
{
    return is_string($id) && strlen($id) === 17 && ctype_digit($id);
}

// Function to validate Steam API Key
function is_valid_key($key)
{
    return is_string($key) && strlen($key) === 32 && ctype_alnum($key);
}

// DRY.
function add_message($amount, $type, $source)
{
    if ($amount === 1) {
        msg(null, "Imported 1 {$type} from {$source}");
    } elseif ($amount > 1) {
        msg(null, "Imported {$amount} {$type}s from {$source}");
    }
}

// Function to merge arrays without duplicates and filters out invalid Steam IDs
function add_ids($new_ids, $source)
{
    global $steam_ids;
    $new_ids = array_map('strval', $new_ids);
    $new_ids = array_filter(array_unique($new_ids), function ($id) use ($steam_ids) {
        return is_valid_id($id) && !in_array($id, $steam_ids);
    });
    $steam_ids = array_merge($steam_ids, $new_ids);
    add_message(count($new_ids), 'ID', $source);
}

// Function to add Steam API Key
// In the future, think about combining this with add_ids function
function add_keys($keys, $source)
{
    global $api_keys;
    $new_keys = array_map('strval', $keys);
    $new_keys = array_filter(array_unique($new_keys), function ($key) use ($api_keys) {
        return is_valid_key($key) && !in_array($key, $api_keys);
    });
    $api_keys = array_merge($api_keys, $new_keys);
    add_message(count($new_keys), 'API Key', $source);
}

// Function to display interval in human-readable format
function seconds_to_time($seconds)
{
    if ($seconds < 60) {
        return "{$seconds}s";
    }

    $minutes = floor($seconds / 60);
    $hours = floor($minutes / 60);
    $days = floor($hours / 24);
    $months = floor($days / 30);

    $result = '';

    if ($months > 0) {
        $result .= "{$months}mon ";
        $days %= 30;
    }

    if ($days > 0) {
        $result .= "{$days}d ";
        $hours %= 24;
    }

    if ($hours > 0) {
        $result .= "{$hours}h ";
        $minutes %= 60;
    }

    if ($minutes > 0) {
        $result .= "{$minutes}min ";
    }

    $remainingSeconds = $seconds % 60;
    if ($remainingSeconds > 0) {
        $result .= "{$remainingSeconds}s";
    }

    return trim($result);
}

// Function to calculate minimal interval based on the number of Steam IDs
function calculate_min_interval($info = true)
{
    global $steam_ids, $api_keys;
    $max_requests_per_day = 100000;
    $max_entries_per_request = 100;
    $max_requests_per_process = ceil(count($steam_ids) / $max_entries_per_request);
    // $max_requests_per_process = ceil(22345 / $max_entries_per_request);
    $seconds_per_day = 86400;
    $min_interval = ($seconds_per_day / $max_requests_per_day * $max_requests_per_process) / count($api_keys);
    // Add 15% to make it safe
    $min_interval *= 1.15;
    // $rounded = ceil($min_interval * 10) / 10; // Round to the nearest 0.1
    $rounded = ceil($min_interval); // Round to the nearest second

    $normalized_time = seconds_to_time($rounded);
    if ($info) {
        msg(null, "Recommended minimum request interval: {$rounded}s" . ($rounded > 60 ? " ({$normalized_time})" : ''));
    }
    return $rounded;
}

// Function to get file hash.
// Will be used to detect changes in users.json file
function file_hash($file_path)
{
    return md5_file($file_path);
}

// Function to calculate seconds difference between two timestamps
// Will be used in self_running mode
function seconds_diff($timestamp1, $timestamp2)
{
    return abs($timestamp1 - $timestamp2);
}

// Function to pick next key from the array, if array ends, start from the beginning
function pick_key()
{
    global $last_key_used, $api_keys;
    $last_key_used++;
    if ($last_key_used >= count($api_keys)) {
        $last_key_used = 0;
    }
    return $api_keys[$last_key_used];
}

// Set proper protocol for requests, based on the server configuration
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';

// Set the base URL for the Steam API request
$api_base_url = "{$protocol}://api.steampowered.com/";

// Fetch json data and provide response code, return data and response code
function fetch_data($url, $key)
{
    global $protocol, $api_base_url, $api_keys;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "{$url}&key={$key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $data = json_decode($response, true);
    curl_close($ch);

    // IM WORKING ON THIS FUNCTION! NEED TO BE REWRITTEN, CLEANED AND TESTED

    // Check if API Key is rejected
    if ($response_code === 403) {
        rejected_key($key);
    } elseif ($response_code !== 200) {
        msg('warning', "Failed to fetch data from Steam API. Response code: {$response_code}");
    }

    return array($data, $response_code);
}

// Handle rejected API Key
function rejected_key($key)
{
    global $api_keys, $api_keys_rejected;
    $api_keys_rejected[] = $key;
    $api_keys = array_diff($api_keys, array($key));
    msg('warning', "API Key {$key} rejected by Steam API");
    if (count($api_keys) === 0) {
        msg('error', "All API Keys have been rejected", true);
    }
}

// Check if Steam accepts the API Key
function test_key($key)
{
    // Using this endpoint because response is small (about 51 bytes)
    // If you know better endpoint, please let me know
    global $api_base_url;
    $url = "{$api_base_url}ISteamUser/ResolveVanityURL/v1/?key={$key}&vanityurl=avaray&url_type=1";
    $data = fetch_data($url, $key);
    return $data[1] === 200;
}

function format_bytes($bytes, $decimals = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    $factor = floor((strlen($bytes) - 1) / 3);
    // Calculate the human-readable value
    $formatted_size = $bytes / pow(1024, $factor);
    // Round the value to the nearest 0.5 or whole number
    $formatted_size = round($formatted_size * 2) / 2;
    // Format the output with the specified number of decimals
    return $formatted_size . ' ' . $units[$factor];
}

// Function to predict generated traffic. Return value in TB. Hourly, Daily, Monthly.
function predict_traffic()
{
    global $interval, $steam_ids;
    $users_to_update = ceil(count($steam_ids) * 0.1);
    $image_size = 25000; // Average size of the image
    $bytes_per_user = 740; // Average size of the response for one user
    $requests_per_hour = 3600 / $interval;
    $requests_per_day = $requests_per_hour * 24;
    $requests_per_month = $requests_per_day * 30;
    $traffic_per_hour = $requests_per_hour * ($image_size + $bytes_per_user);
    $traffic_per_day = $requests_per_day * ($image_size + $bytes_per_user);
    $traffic_per_month = $requests_per_month * ($image_size + $bytes_per_user);
    $traffic_per_hour_total = $traffic_per_hour * $users_to_update;
    $traffic_per_day_total = $traffic_per_day * $users_to_update;
    $traffic_per_month_total = $traffic_per_month * $users_to_update;
    $hourly = format_bytes($traffic_per_hour_total);
    $daily = format_bytes($traffic_per_day_total);
    $monthly = format_bytes($traffic_per_month_total);

    msg(null, "Predicted traffic: {$hourly}/hour, {$daily}/day, {$monthly}/month");
}

// --------------------------------------------------------------------------------------------
// DATABASE FUNCTIONS
// --------------------------------------------------------------------------------------------

// Function to create a new database
function create_database()
{
    global $database, $steam_ids;
    $database = array();
    foreach ($steam_ids as $steam_id) {
        $entry = array(
            $steam_id => '',
        );
        $database[] = $entry;
    }
    return $database;
}

// Function to load the database from a file
function load_database()
{
    global $database, $database_file;
    if (file_exists($database_file)) {
        msg(null, "Loading database from a file.");
        return json_decode(file_get_contents($database_file), true);
    } else {
        msg('warning', "Database file not found. Creating a new database.");
        return create_database();
    }
}

// Function to save database to a file
function save_database()
{
    global $database, $database_file;
    $json = json_encode($database, JSON_PRETTY_PRINT);
    file_put_contents($database_file, $json);
    msg(null, "Database saved to a file");
}

// Function to clean the database from old entries
function update_database()
{
    global $database, $steam_ids;
    $new_database = array();
    $removed_entries = 0;
    foreach ($database as $entry) {
        $key = key($entry);
        if (in_array($key, $steam_ids)) {
            $new_database[] = $entry;
        } else {
            $removed_entries++;
        }
    }
    $database = $new_database;
    if ($removed_entries > 0) {
        msg(null, "Removed {$removed_entries} entries from the database");
    } else {
        msg(null, "Database is up to date");
    }
}

// --------------------------------------------------------------------------------------------
// CONFIGURATION
// --------------------------------------------------------------------------------------------

// Set the current directory to the script directory
chdir(dirname(__FILE__));

// Declare global variables
$api_keys = array();
$api_keys_rejected = array();
$steam_ids = array();
$config = array();
$config_file = 'config.json';
$database = array();
$database_file = 'db.json';
// $self_running = false;
$interval = 60;
$last_key_used = 0;

// Read config file
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    if (!empty($config['timezone'])) {
        date_default_timezone_set($config['timezone']);
    }
}

// Start Message
$php_version = phpversion();
msg('success', "Starting script with PHP {$php_version}");

// Check if Steam API Key is set in the config file
if (!empty($config['keys'])) {
    add_keys($config['keys'], 'the config file');
}

// Import Steam IDs from the config file
if (!empty($config['ids'])) {
    add_ids($config['ids'], 'the config file');
}

// Import Steam IDs from the input file
if (!empty($config['input_file'])) {
    $file_path = $config['input_file'];
    if (file_exists($file_path)) {
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        if ($file_extension === 'json') {
            $json = json_decode(file_get_contents($file_path), true);
            add_ids($json, $file_path);
        } else {
            $content = file_get_contents($file_path);
            $extracted = preg_split('/[^\d]+/', $content);
            add_ids($steam_ids, $file_path);

        }
    } else {
        msg('warning', "Input file {$file_path} not found");
    }
}

// Read arguments passed in the command line
if (!empty($argv)) {
    for ($i = 1; $i < count($argv); $i++) {
        $arg = explode('=', $argv[$i]);
        if (count($arg) === 2 && !empty($arg[1])) {
            if ($arg[0] === 'ids') {
                // Split the string by non-digit characters
                $ids = preg_split('/\D+/', $arg[1]);
                add_ids($ids, 'arguments');
            } elseif ($arg[0] === 'keys') {
                $keys = explode(',', $arg[1]);
                add_keys($keys, 'arguments');
            } else {
                msg('warning', "Found unknown argument: \e[31m{$arg[0]}\e[0m");
            }
        }
    }
}

// Check if Steam API Keys are set in the environment variables
$api_keys_env = getenv('STEAM_API_KEYS');
if (!empty($api_keys_env)) {
    add_keys(explode(',', $api_keys_env), 'environment variables');
}

// Check if things passed in the URL
// Server should be configured to allow URL query parameters
// Otherwise array $_GET will be empty
if (!empty($_GET)) {
    foreach ($_GET as $key => $value) {
        if ($key === 'ids') {
            $ids = explode(',', $value);
            add_ids($ids, 'URL');
        } elseif ($key === 'keys') {
            // I think I should move "explode" to other function to avoid repetition
            $keys = explode(',', $value);
            add_keys($keys, 'URL');
        } else {
            msg('warning', "Found unknown query parameter: {$key}");
        }
    }
}

// Check if API keys are valid
foreach ($api_keys as $key) {
    if (!test_key($key)) {
        rejected_key($key);
    }
}

// Last check for Steam ID
if (empty($steam_ids)) {
    msg('error', "Steam ID not found", true);
} else {
    msg(null, "Using " . count($steam_ids) . " Steam ID" . (count($steam_ids) > 1 ? 's in total' : '') . "");
}

// Last check for Steam API Key
if (empty($api_keys)) {
    msg('error', "Steam API Key not found", true);
} else {
    msg(null, "Using " . count($api_keys) . " Steam API Key" . (count($api_keys) > 1 ? 's in total' : '') . "");
}

// Load database from a file for the first time
if (file_exists($database_file)) {
    $database = json_decode(file_get_contents($database_file), true);
    update_database($database);
} else {
    $database = create_database();
    save_database($database);
}

// --------------------------------------------------------------------------------------------
// IMAGE GENERATION
// --------------------------------------------------------------------------------------------

function generate_image()
{
    // TODO: Write code
    msg('success', "Generating image");
}

// --------------------------------------------------------------------------------------------
// MAIN
// --------------------------------------------------------------------------------------------

if ($config['self_running']) {
    msg(null, "Script is set to run automatically");
    // In the future interval should re-calculated after database update / changes in $steam_ids
    $calculated_interval = calculate_min_interval();
    $wanted_interval = !empty($config['interval']) ? $config['interval'] : 60;

    if ($wanted_interval < $calculated_interval) {
        msg('warning', "Interval {$wanted_interval}s is set too low. Using {$calculated_interval}s");
        $interval = $calculated_interval;
    } else {
        msg(null, "Interval is set to {$wanted_interval}s");
        $interval = $wanted_interval;
    }

    $last_run = time();
    // $last_database_save = time();

    predict_traffic();

    msg('success', "Running the script in self-running mode");

    while (true) {

        $current_time = time();
        $time_diff = seconds_diff($current_time, $last_run);

        if ($time_diff >= $interval) {
            $last_run = $current_time;

            $ids_chunked = array_chunk($steam_ids, 100);

            foreach ($ids_chunked as $ids) {
                $key = pick_key();
                msg(null, "Using API Key: {$key}");

                // $url = "{$api_base_url}ISteamUser/GetPlayerSummaries/v2/?key={$key}&steamids=" . implode(',', $ids);
                // $data = fetch_data($url, $key);
                // $response = $data[0];
                // $response_code = $data[1];

                // need to pass data to the function below
                generate_image();
            }

        } else {
            // Sleep for the remaining time. Max value 0 is to prevent negative values.
            $sleep_time = max(0, $interval - $time_diff);
            // msg(null, "Sleeping for {$sleep_time} seconds...");
            sleep($sleep_time);
        }
    }
} else {
    msg('success', "Running the script in manual mode");
    // TODO: Write code
}
