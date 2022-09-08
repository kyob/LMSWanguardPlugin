<?php

// REPLACE THIS WITH PATH TO YOUR CONFIG FILE
$CONFIG_FILE = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'lms.ini';

// PLEASE DO NOT MODIFY ANYTHING BELOW THIS LINE UNLESS YOU KNOW
// *EXACTLY* WHAT ARE YOU DOING!!!
// *******************************************************************

define('CONFIG_FILE', $CONFIG_FILE);

$CONFIG = (array) parse_ini_file($CONFIG_FILE, true);

// Check for configuration vars and set default values
$CONFIG['directories']['sys_dir'] = (!isset($CONFIG['directories']['sys_dir']) ? getcwd() : $CONFIG['directories']['sys_dir']);
$CONFIG['directories']['lib_dir'] = (!isset($CONFIG['directories']['lib_dir']) ? $CONFIG['directories']['sys_dir'] . DIRECTORY_SEPARATOR . 'lib' : $CONFIG['directories']['lib_dir']);

define('SYS_DIR', $CONFIG['directories']['sys_dir']);
define('LIB_DIR', $CONFIG['directories']['lib_dir']);

// Load autoloader
$composer_autoload_path = SYS_DIR . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($composer_autoload_path)) {
    require_once $composer_autoload_path;
} else {
    die("Composer autoload not found. Run 'composer install' command from LMS directory and try again. More informations at https://getcomposer.org/" . PHP_EOL);
}

// Init database

$DB = null;

try {
    $DB = LMSDB::getInstance();
} catch (Exception $ex) {
    trigger_error($ex->getMessage(), E_USER_WARNING);
    // can't working without database
    die("Fatal error: cannot connect to database!" . PHP_EOL);
}

// Include required files (including sequence is important)

require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'common.php');
require_once(LIB_DIR . DIRECTORY_SEPARATOR . 'language.php');
include_once(LIB_DIR . DIRECTORY_SEPARATOR . 'definitions.php');

$SYSLOG = SYSLOG::getInstance();
$AUTH = null;
$curl = null;

function getNodeByAnomalyID(int $anomaly_id)
{
    $api_user = ConfigHelper::getConfig('wanguard.api_user');
    $api_password = ConfigHelper::getConfig('wanguard.api_password');
    $api_auth = base64_encode($api_user . ":" . $api_password);

    $api_url = ConfigHelper::getConfig('wanguard.api_url');
    $anomaly = array();

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $api_url . 'anomalies/' . $anomaly_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Authorization: Basic ' . $api_auth;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    $anomaly = array_merge_recursive($anomaly, json_decode($result, true));
    // input IP format: 1.2.3.4/32
    $ip = explode("/", $anomaly['prefix']);

    $DB = LMSDB::getInstance();
    $node = $DB->GetAll('SELECT * FROM vnodes WHERE ipaddr = ?', array(ip2long($ip[0])));

    if ($node > 0) {
        $anomaly = array_merge(array('anomaly_id' => $anomaly_id), $anomaly, array('anomaly_id' => $anomaly_id, 'node' => $node[0], 'node_id' => $node[0]['id'], 'ipaddr' => $node[0]['ipaddr'], 'location' => $node[0]['location']));
    } else {
        $anomaly = 0;
    }

    //echo ($anomaly_id . "\n\r" . PHP_EOL);

    return $anomaly;
}

function getAnomaliesFromWanguard()
{
    $api_user = ConfigHelper::getConfig('wanguard.api_user');
    if (empty($api_user)) {
        echo 'Missing API_USER in WANGUARD section.';
        die();
    }

    $api_password = ConfigHelper::getConfig('wanguard.api_password');
    if (empty($api_password)) {
        echo 'Missing API_PASSWORD in WANGUARD section.';
        die();
    }

    $api_auth = base64_encode($api_user . ":" . $api_password);

    $api_url = ConfigHelper::getConfig('wanguard.api_url');
    if (empty($api_url)) {
        echo 'Missing API_URL in WANGUARD section.';
        die();
    }

    $anomalies = array();

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $api_url . 'anomalies?status=Finished');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

    $headers = array();
    $headers[] = 'Accept: application/json';
    $headers[] = 'Authorization: Basic ' . $api_auth;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    $anomalies = array_merge_recursive($anomalies, json_decode($result, true));

    return $anomalies;
}

function updateAnomalies()
{
    $anomalies = array();
    $anomalies = getAnomaliesFromWanguard();
    $DB = LMSDB::getInstance();

    foreach ($anomalies as $anomaly) {
        $args = array(
            'href' => $anomaly['href'],
            'hash' => sha1($anomaly['href'])
        );

        $anomaly_id = basename($anomaly['href']);

        $anomalies2node[] = getNodeByAnomalyID($anomaly_id);

        if (is_array($anomalies2node)) { // protection against empty anomaly when IP is not assigned to customer in LMS

            foreach ($anomalies2node as $anomaly) {
                $args = array(
                    'anomaly_id' => $anomaly['anomaly_id'],
                    'status' => $anomaly['status'],
                    'anomaly' => $anomaly['anomaly'],
                    'direction' => $anomaly['direction'],
                    'until' => $anomaly['until']['unixtime'],
                    'node_id' => $anomaly['node_id'],
                    'ipaddr' => $anomaly['ipaddr'],
                    'location' => $anomaly['location'],
                );

                if ($args['anomaly_id']) {
                    $DB->Execute(
                        'INSERT INTO alfa_wanguard (anomaly_id, status, anomaly, direction, until, node_id, ipaddr, location) 
                                VALUES(?,?,?,?,?,?,?,?)
                                 ON CONFLICT (anomaly_id) DO NOTHING',
                        array_values($args)
                    );
                }
            };
        };
    }
}

updateAnomalies();

$DB->Destroy();
