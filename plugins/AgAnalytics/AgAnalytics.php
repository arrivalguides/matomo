<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AgAnalytics;

use Piwik\Common;
use Piwik\API\Request;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Cache;
use Piwik\CacheId;


class AgAnalytics extends \Piwik\Plugin
{

    const DEFAULT_AVATAR_URL = 'plugins/AgAnalytics/images/default_avatar.png';

    const DEFAULT_AVATAR_DESCRIPTION = 'Visitor Default Avatar';
    
    const AVATAR_URL = 'http://www.arrivalguides.com/content/data/distributionpartners/%s/logo.png?height=120&width=122&scale=both&bgcolor=';

    private static $rawPrefix = 'facebook_report';


    public function install()
    {
        $reportTable = "`compaign_id` VARCHAR(20) NOT NULL,
					    `info` TEXT NOT NULL,
					    `reports` TEXT NULL,
					    PRIMARY KEY (`compaign_id`)";

        DbHelper::createTable(self::$rawPrefix, $reportTable);
    }
    
    public static function getReportTable()
    {
            return Common::prefixTable(self::$rawPrefix);
    }
    
    public static  function updateReport($id, $info = 'test1', $report = 'test2')
    {

        $sql  = 'INSERT INTO `' . self::getReportTable() . '` (compaign_id, info, reports) ' .
                ' VALUES (?, ?, ?) ' .
                ' ON DUPLICATE KEY UPDATE reports = ?';
        $bind = array($id, $info, $report, $report);

        Db::query($sql, $bind);

    }
    
    public static function getReport()
    {

        $reports = Db::fetchAll('SELECT info, reports FROM `' . Common::prefixTable(self::$rawPrefix) . '` ' .
                              'WHERE reports != \'\'');

        $result = [];                      
                              
            // get the archive IDs
            foreach ($reports as $row) {
                $result[] = json_decode($row['reports'], true);
            }
            
            return $result;
        
    }
    
        /**
     *
     * @see Piwik\Plugin::getListHooksRegistered
     */
    public function getListHooksRegistered()
    {
        return array(
            'Live.getExtraVisitorDetails' => 'getVisitorAvatarDetails'
        );
    }

    /**
     *
     * @see http://developer.piwik.org/api-reference/events#livegetextravisitordetails
     */
    public function getVisitorAvatarDetails(&$result)
    {
        // default const
        $visitorAvatar = self::DEFAULT_AVATAR_URL;
        $visitorDescription = self::DEFAULT_AVATAR_DESCRIPTION;
        
        if(!empty($result['userId'])) {
            $cache = Cache::getTransientCache();
            $cachKey = 'AgAnalytics.avatar' . $result['userId'];
            if (!$cache->contains($cachKey)) {
                $avatarUrl = sprintf(self::AVATAR_URL, $result['userId']);
                $ch = curl_init($avatarUrl);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_exec($ch);
                $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                // $retcode >= 400 -> not found, $retcode = 200, found.
                curl_close($ch);
                if($retcode == 200) {
                    $visitorAvatar = $avatarUrl;
                }
                $cache->save($cachKey, $visitorAvatar);
            }
            
            $visitorAvatar = $cache->fetch($cachKey);
            $visitorDescription = $result['userId'];
        }
        
        // sync result
        $result['visitorAvatar'] = $visitorAvatar;
        $result['visitorDescription'] = $visitorDescription;
    }
    
}
