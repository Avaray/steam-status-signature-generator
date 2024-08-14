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
function msg($message, $die = false)
{
    $dateTime = date('Ymd.His'); // YYYYMMDD.HHMMSS
    echo "\e[34m{$dateTime}\e[0m : {$message}\n";
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
        msg("Imported 1 {$type} from {$source}");
    } elseif ($amount > 1) {
        msg("Imported {$amount} {$type}s from {$source}");
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
    global $steam_api_keys;
    $new_keys = array_map('strval', $keys);
    $new_keys = array_filter(array_unique($new_keys), function ($key) use ($steam_api_keys) {
        return is_valid_key($key) && !in_array($key, $steam_api_keys);
    });
    $steam_api_keys = array_merge($steam_api_keys, $new_keys);
    add_message(count($new_keys), 'API Key', $source);
}

// Function to calculate minimal interval based on the number of Steam IDs
function calculate_min_interval($steam_ids, $output_format = 'seconds', $info = false)
{
    $max_requests_per_day = 100000;
    $max_entries_per_request = 100;
    $max_requests_per_process = ceil(count($steam_ids) / $max_entries_per_request);
    $seconds_per_day = 86400;
    $min_interval = $seconds_per_day / $max_requests_per_day * $max_requests_per_process;
    // $rounded = ceil($min_interval * 10) / 10; // Round to the nearest 0.1
    $rounded = ceil($min_interval); // Round to the nearest second
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
        msg("Loading database from a file.");
        return json_decode(file_get_contents($database_file), true);
    } else {
        msg("Database file not found. Creating a new database.");
        return create_database();
    }
}

// Function to save database to a file
function save_database()
{
    global $database, $database_file;
    $json = json_encode($database, JSON_PRETTY_PRINT);
    file_put_contents($database_file, $json);
    msg("Database saved to a file");
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
        msg("Removed {$removed_entries} entries from the database");
    } else {
        msg("Database is up to date");
    }
}

// --------------------------------------------------------------------------------------------
// CONFIGURATION
// --------------------------------------------------------------------------------------------

// Set the current directory to the script directory
chdir(dirname(__FILE__));

// Declare global variables
$steam_api_keys = array();
$steam_ids = array();
$config = array();
$config_file = 'config.json';
$database = array();
$database_file = 'db.json';

// Read config file
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    if (!empty($config['timezone'])) {
        date_default_timezone_set($config['timezone']);
    }
}

// Start Message
$php_version = phpversion();
msg("Starting script with \e[35mPHP {$php_version}\e[0m");

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
        msg("Input file {$file_path} not found");
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
                msg("Found unknown argument: \e[31m{$arg[0]}\e[0m");
            }
        }
    }
}

// Check if Steam API Keys are set in the environment variables
$steam_api_keys_env = getenv('STEAM_API_KEYS');
if (!empty($steam_api_keys_env)) {
    add_keys(explode(',', $steam_api_keys_env), 'environment variables');
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
            msg("Found unknown query parameter: \e[31m{$key}\e[0m");
        }
    }
}

// Last check for Steam ID
if (empty($steam_ids)) {
    msg("\e[31mSteam ID not found. Please set Steam ID in the config file or pass it as an argument\e[0m", true);
} else {
    msg("Using " . count($steam_ids) . " Steam ID" . (count($steam_ids) > 1 ? 's in total' : '') . "");
}

// Last check for Steam API Key
if (empty($steam_api_keys)) {
    msg("\e[31mSteam API Key not found. Please set Steam API Key in the config file or pass it as an argument\e[0m", true);
} else {
    msg("Using " . count($steam_api_keys) . " Steam API Key" . (count($steam_api_keys) > 1 ? 's in total' : '') . "");
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
// STEAM API FUNCTIONS
// --------------------------------------------------------------------------------------------

// TODO: Write code

// --------------------------------------------------------------------------------------------
// IMAGE GENERATION
// --------------------------------------------------------------------------------------------

// TODO: Write code

// --------------------------------------------------------------------------------------------
// MAIN
// --------------------------------------------------------------------------------------------

if ($config['self_running']) {
    msg("Script is set to run automatically");
    $calculated_interval = calculate_min_interval($steam_ids, 'seconds');
    $wanted_interval = !empty($config['interval']) ? $config['interval'] : 60;

    if ($wanted_interval < $calculated_interval) {
        msg("WARNING: Interval {$wanted_interval} is set too low. Using {$calculated_interval} seconds");
        $interval = $calculated_interval;
    } else {
        msg("Interval is set to {$wanted_interval} seconds");
        $interval = $wanted_interval;
    }

    $last_run = time();
    // $last_database_save = time();

    while (true) {

        $current_time = time();
        $time_diff = seconds_diff($current_time, $last_run);

        if ($time_diff >= $interval) {
            $last_run = $current_time;
            // msg("Executing");
        } else {
            // Sleep for the remaining time. Max value 0 is to prevent negative values.
            $sleep_time = max(0, $interval - $time_diff);
            // msg("Sleeping for {$sleep_time} seconds...");
            sleep($sleep_time);
        }
    }
} else {
    msg("Script is set to run manually");
    // TODO: Write code
}
