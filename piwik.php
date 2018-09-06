<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @package Piwik
 */

//file_put_contents("debug.log", print_r($_GET, true), FILE_APPEND);


define('SOURCE_SITEID', 2);
define('DESTINATION_TITLE', 'Destination');
define('DESTINATION_INDEX', 2);
 
use Piwik\SettingsServer;
use Piwik\Tracker\RequestSet;
use Piwik\Tracker;
use Piwik\Tracker\Handler;
use Piwik\API\CORSHandler;

use Piwik\Piwik;
use Piwik\Common;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Site;
use Piwik\Cache;
use Piwik\CacheId;


@ignore_user_abort(true);

// Note: if you wish to debug the Tracking API please see this documentation:
// http://developer.piwik.org/api-reference/tracking-api#debugging-the-tracker

if (!defined('PIWIK_DOCUMENT_ROOT')) {
    define('PIWIK_DOCUMENT_ROOT', dirname(__FILE__) == '/' ? '' : dirname(__FILE__));
}
if (file_exists(PIWIK_DOCUMENT_ROOT . '/bootstrap.php')) {
    require_once PIWIK_DOCUMENT_ROOT . '/bootstrap.php';
}
if (!defined('PIWIK_INCLUDE_PATH')) {
    define('PIWIK_INCLUDE_PATH', PIWIK_DOCUMENT_ROOT);
}

require_once PIWIK_INCLUDE_PATH . '/core/bootstrap.php';

require_once PIWIK_INCLUDE_PATH . '/core/Plugin/Controller.php';
require_once PIWIK_INCLUDE_PATH . '/core/Exception/NotYetInstalledException.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/ControllerAdmin.php';
require_once PIWIK_INCLUDE_PATH . '/core/Singleton.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin/Manager.php';
require_once PIWIK_INCLUDE_PATH . '/core/Plugin.php';
require_once PIWIK_INCLUDE_PATH . '/core/Common.php';
require_once PIWIK_INCLUDE_PATH . '/core/Piwik.php';
require_once PIWIK_INCLUDE_PATH . '/core/IP.php';
require_once PIWIK_INCLUDE_PATH . '/core/UrlHelper.php';
require_once PIWIK_INCLUDE_PATH . '/core/Url.php';
require_once PIWIK_INCLUDE_PATH . '/core/SettingsPiwik.php';
require_once PIWIK_INCLUDE_PATH . '/core/SettingsServer.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker.php';
require_once PIWIK_INCLUDE_PATH . '/core/Config.php';
require_once PIWIK_INCLUDE_PATH . '/core/Translate.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Cache.php';
require_once PIWIK_INCLUDE_PATH . '/core/Tracker/Request.php';
require_once PIWIK_INCLUDE_PATH . '/core/Cookie.php';
require_once PIWIK_INCLUDE_PATH . '/core/API/CORSHandler.php';

SettingsServer::setIsTrackerApiRequest();

$environment = new \Piwik\Application\Environment('tracker');
try {
    $environment->init();
} catch(\Piwik\Exception\NotYetInstalledException $e) {
    die($e->getMessage());
}

Tracker::loadTrackerEnvironment();

$corsHandler = new CORSHandler();
$corsHandler->handle();

$tracker    = new Tracker();
$requestSet = new RequestSet();

$SITE_NAME = '';

if(isset($_GET['idsite']) && $_GET['idsite'] == SOURCE_SITEID){

    if(isset($_GET['cvar'])){
        $vars = json_decode($_GET['cvar'], true);
        if(isset($vars[DESTINATION_INDEX][0]) && $vars[DESTINATION_INDEX][0] == DESTINATION_TITLE && isset($vars[DESTINATION_INDEX][1])){
            $SITE_NAME = strtoupper($vars[DESTINATION_INDEX][1]);
        }

    }

    if(isset($_GET['_cvar']) && empty($SITE_NAME)){
        $vars = json_decode($_GET['_cvar'], true);
        if(isset($vars[DESTINATION_INDEX][0]) && $vars[DESTINATION_INDEX][0] == DESTINATION_TITLE && isset($vars[DESTINATION_INDEX][1])){
            $SITE_NAME = strtoupper($vars[DESTINATION_INDEX][1]);
        }
    }

    if(isset($_GET['dimension2']) && empty($SITE_NAME)){
        $SITE_NAME = strtoupper($_GET['dimension2']);
    }

}


/*if(empty($SITE_NAME) && isset($_GET['idsite']) && $_GET['idsite'] == SOURCE_SITEID){
    file_put_contents("debug.log", ".", FILE_APPEND);
    file_put_contents("dump.log", print_r($_GET, true), FILE_APPEND);
}*/

// $result = Db::fetchAll("SELECT idsite FROM " . Common::prefixTable('site'));
// 
//         $idSites = array();
//         foreach ($result as $idSite) {
//             $idSites[] = $idSite['idsite'];
//         } 
// print_r($idSites);


if(!empty($SITE_NAME)) {

    Piwik::setUserHasSuperUserAccess();

    $cache = Cache::getTransientCache();
    $cachKey = 'AgAnalytics.destinations';
    if (!$cache->contains($cachKey)) {
        $api = SitesManagerAPI::getInstance();
        $allSites = $api->getAllSites();
        $destinations = [];
        foreach($allSites as $site){
           $destinations[$site['name']] = $site['idsite'];
        }
        $cache->save($cachKey, $destinations);
    }

    $destination = $cache->fetch($cachKey);

    $idsite = null;
    if(isset($destination[strtoupper($SITE_NAME)])){
       $idsite = $destination[strtoupper($SITE_NAME)];
    }

    if($idsite){
        $_GET['idsite'] = $idsite;
        file_put_contents("debug.log", "\n" . print_r($SITE_NAME, true) . "(" . $_GET['idsite'] . ")", FILE_APPEND);
    }else{
        file_put_contents("debug.log", "\n" . print_r($SITE_NAME, true) . "???", FILE_APPEND);

        $query = "INSERT INTO " . Common::prefixTable('site') . " VALUES (NULL, '" . addslashes(strtoupper($SITE_NAME)) . "', 'http://www.arrivalguides.com', NOW(), 0, 1, '', '', 'Europe/Stockholm', 'USD', 0, '', '', '', '', 'website', 0)";
        file_put_contents("query.log", $query . "\n", FILE_APPEND);
        $result = Db::query($query);

        $api = SitesManagerAPI::getInstance();
        $allSites = $api->getAllSites();
        $destinations = [];
        foreach($allSites as $site){
           $destinations[$site['name']] = $site['idsite'];
        }
        $cache->save($cachKey, $destinations);
        $destination = $cache->fetch($cachKey);
        if(isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
           file_put_contents("debug.log", "\n" . print_r($SITE_NAME, true) ."+++" . "(" . $_GET['idsite'] . ")", FILE_APPEND);
        }else{
           file_put_contents("debug.log", "\n" . "ERROR", FILE_APPEND);
        }
    }
}



if(isset($_GET['idsite']) && $_GET['idsite'] == 1){


    //$_GET['idsite'] = 2;
    $parts = explode("/", $_GET['url']);

    Piwik::setUserHasSuperUserAccess();

    $cache = Cache::getTransientCache();
    $cachKey = 'AgAnalytics.destinations';
    if (!$cache->contains($cachKey)) {
        $api = SitesManagerAPI::getInstance();
        $allSites = $api->getAllSites();
        $destinations = [];
        foreach($allSites as $site){
           $destinations[$site['name']] = $site['idsite'];
        }
        $cache->save($cachKey, $destinations);
    }
    $destination = $cache->fetch($cachKey);

    $SITE_NAME = "";

// 1)
    if(empty($SITE_NAME) && count($parts) == 8 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Travelguide" && isset($parts[5])){
       $SITE_NAME = $parts[5];
       if(isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

//  2)
    if(empty($SITE_NAME) && count($parts) == 5 && $parts[2] == "cmwidget.arrivalguides.com" && $parts[3] == "widget" && isset($parts[4])){
       $arr = explode("&", $parts[4]);
       if(isset($arr[1])){
          preg_match("/^iso=(.*)/i", $arr[1], $matches);
          $SITE_NAME = (isset($matches[1])) ? $matches[1] : "";
       }
       if(!empty($SITE_NAME) && isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 3)
    if(empty($SITE_NAME) && count($parts) == 5 && $parts[2] == "widget.arrivalguides.com" && $parts[3] == "widget" && isset($parts[4])){
       $arr = explode("&", $parts[4]);
       if(isset($arr[1])){
          preg_match("/^iso=(.*)/i", $arr[1], $matches);
          $SITE_NAME = (isset($matches[1])) ? $matches[1] : "";
       }
       if(!empty($SITE_NAME) && isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 4)
    if(empty($SITE_NAME) && count($parts) == 8 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Travelguides" && isset($parts[7])){
       $SITE_NAME = $parts[7];
       if(isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 5)
    if(empty($SITE_NAME) && count($parts) == 9 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Travelguides" && isset($parts[7])){
       $SITE_NAME = $parts[7];
       if(isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 6)
    if(empty($SITE_NAME) && count($parts) == 8 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Partner" && $parts[6] == "Destination" && isset($parts[7])){
       $arr = explode("&", $parts[7]);
       if(isset($arr[0])){
          preg_match("/^EmailDownload\?destination=(.*)/i", $arr[0], $matches);
          $SITE_NAME = (isset($matches[1])) ? $matches[1] : "";
       }
       if(!empty($SITE_NAME) && isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 7)
    if(empty($SITE_NAME) && count($parts) == 6 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Destination" && isset($parts[5])){
       $arr = explode("&", $parts[5]);
       if(isset($arr[1])){
          preg_match("/^destination=(.*)/i", $arr[1], $matches);
          $SITE_NAME = (isset($matches[1])) ? $matches[1] : "";
       }
       if(!empty($SITE_NAME) && isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 8)
    if(empty($SITE_NAME) && count($parts) == 8 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Partner" && $parts[6] == "Destination" && isset($parts[7])){
       preg_match("/^Download\?destination=(.*)/i", $parts[7], $matches);
       $SITE_NAME = (isset($matches[1])) ? $matches[1] : "";
       if(!empty($SITE_NAME) && isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 9)
    if(empty($SITE_NAME) && count($parts) == 6 && $parts[2] == "www.arrivalguides.com" && $parts[4] == "Travelguide" && isset($parts[5])){
       $SITE_NAME = $parts[5];
       if(isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }

// 10)
    if(count($parts) == 9 && $parts[2] == "new.arrivalguides.com" && $parts[4] == "Travelguides" && isset($parts[7])){
       $SITE_NAME = $parts[7];
       if(isset($destination[strtoupper($SITE_NAME)])){
           $_GET['idsite'] = $destination[strtoupper($SITE_NAME)];
       }else file_put_contents("front.log", strtoupper($SITE_NAME) . " not found\n", FILE_APPEND);
    }


    if(empty($SITE_NAME)){
       file_put_contents("destinations.log", print_r($_GET, true) . print_r($parts, true), FILE_APPEND);
    }else{
      file_put_contents("front.log", strtoupper($SITE_NAME) ."(" . $_GET['idsite'] . ")\n", FILE_APPEND);
    }

}



ob_start();

try {
    $handler  = Handler\Factory::make();
    $response = $tracker->main($handler, $requestSet);

    if (!is_null($response)) {
        echo $response;
    }

} catch (Exception $e) {
    echo "Error:" . $e->getMessage();
    exit(1);
}

if (ob_get_level() > 1) {
    ob_end_flush();
}
