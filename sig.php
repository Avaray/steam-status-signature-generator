<?php

// --------------------------------------------------------------------------------------------
// CONFIGURATION AND BASIC FUNCTIONS
// --------------------------------------------------------------------------------------------

// Set the current directory to the script directory
chdir(dirname(__FILE__));

// Declare global variables
$steam_ids = array();
$steam_api_key = '';
$config = array();
$database = array();
$database_file = file_exists('db.json') ? 'db.json' : null;

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
        } else {
            $content = file_get_contents($file_path);
            $extracted = preg_split('/[^\d]+/', $content);
            $steam_ids = array_filter($extracted, function ($value) {
                return strlen($value) === 17;
            });
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
                $ids = array_filter($ids, function ($id) {
                    return is_string($id) && strlen($id) === 17 && ctype_digit($id);
                });
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

// Function to load the database from a file
function load_database($database_file)
{
    // I think I should clean this function later. Checking if file exist might be not necessary.
    // Maybe I should use try-catch block here. Need to re-think this.
    if (file_exists($database_file)) {
        $database = json_decode(file_get_contents($database_file), true);
        msg("Database loaded with " . count($database) . " entries.");
        return $database;
    }
    return null;
}

// Function to save database to a file
function save_database($database_file, $database)
{
    $json_data = json_encode($database, JSON_PRETTY_PRINT) . "\n";
    file_put_contents($database_file, $json_data);
    msg("Database saved with " . count($database) . " entries.");
}

// Function to clean the database from old entries (remove entries that does not exist in the current list of Steam IDs)
function update_database($database, $steam_ids)
{
    $new_database = array();
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

// Check if custom database is specified and if it exists
// Otherwise use default database file
if (!empty($config['db_file']) && file_exists($config['db_file'])) {
    msg("Custom database file found: " . $config['db_file']);
    $database_file = $config['db_file'];
} elseif ($database_file === null) {
    msg("Database file not found. Using default database file: db.json");
    $database_file = 'db.json';
    update_database();
}

// Load database from a file for the first time
if ($database === null) {
    $database = load_database($database_file);
}

// Check if default database file exists
if ($database === null && file_exists('db.json')) {
    $database = load_database('db.json');
}

// Check if database is empty
if ($database !== null && empty($database)) {
    msg("Database file not found: " . (!empty($config['db_file']) ? $config['db_file'] : 'db.json'));
    msg("Creating new database file.");
    // save_database($database_file, $database);
}

// Update database for the first time
update_database($database, $steam_ids);

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
// THIS IS THE MAIN PROGRAM
// DO NOT REMOVE THIS
// --------------------------------------------------------------------------------------------

if ($config['self_running']) {
    msg("Script is set to run automatically.");
    $min_interval = calculate_min_interval($steam_ids, 'seconds');
    $wanted_interval = isset($config['interval']) ? $config['interval'] : 60;
    // $interval = $wanted_interval < $min_interval ? $min_interval : $wanted_interval;

    // TODO: Add minimal value of 1 second. This should be lowest possible value.
    if ($wanted_interval < $min_interval) {
        msg("WARNING: Interval {$wanted_interval} is set too low. Using {$min_interval} seconds.");
        $interval = $min_interval;
    } else {
        msg("Interval is set to {$wanted_interval} seconds.");
        $interval = $wanted_interval;
    }

    $last_run = time();
    $last_database_save = time();

    while (true) {
        // Call your function here
        msg(file_hash('users.json'));

        $current_time = time();
        $time_diff = seconds_diff($current_time, $last_run);

        if ($time_diff >= $interval) {
            $last_run = $current_time;
            msg("Running the script...");
        } else {
            // Sleep for the remaining time. Max value 0 is to prevent negative values.
            $sleep_time = max(0, $interval - $time_diff);
            msg("Sleeping for {$sleep_time} seconds...");
            sleep($sleep_time);
        }
    }
} else {
    msg("Script is set to run manually.");
    // TODO Handle manual run
}
