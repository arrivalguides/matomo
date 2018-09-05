<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AgAnalytics;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Access;
use Piwik\Log;
use Piwik\Plugins\UsersManager\API as APIUsersManager;

/**
 * API for plugin ExamplePlugin
 *
 * @method static \Piwik\Plugins\ExamplePlugin\API getInstance()
 */
class API extends \Piwik\Plugin\API
{

    private function syncronizeUser($login, $email)
    {
        if ($this->userExists($login)) return;
        if ($this->getUserLoginFromUserEmail($email)) return;
        
        Access::doAsSuperUser(function () use ($login, $email) {
            $api = APIUsersManager::getInstance();
            $api->addUser($login, 'secure', $email);
//             $api->setSuperUserAccess($login, true);
            $userInfo   = $this->model->getUser($login);
            $token_auth = $userInfo['token_auth'];
        });
    }
    
    /**
     * Example method. Please remove if you do not need this API method.
     * You can call this API method like this:
     * /index.php?module=API&method=ExamplePlugin.getAnswerToLife
     * /index.php?module=API&method=ExamplePlugin.getAnswerToLife&truth=0
     *
     * @param  bool $truth
     *
     * @return int
     */
    public function getAnswerToLife($truth = true)
    {
        if ($truth) {
            return 42;
        }

        return 24;
    }

    /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
    public function getExampleReport($idSite, $period, $date, $segment = false)
    {
        $table = DataTable::makeFromSimpleArray(array(
            array('label' => 'My Label 1', 'nb_visits' => '1'),
            array('label' => 'My Label 2', 'nb_visits' => '5'),
        ));

        return $table;
    }
    
        /**
     * Another example method that returns a data table.
     * @param int    $idSite
     * @param string $period
     * @param string $date
     * @param bool|string $segment
     * @return DataTable
     */
//     public function getExampleReport($idSite, $period, $date, $segment = false)
//     {
//         $table = new DataTable();
// 
//         $table->addRowFromArray(array(Row::COLUMNS => array('nb_visits' => 5)));
// 
//         return $table;
//     }
    
    public function getFacebookReport($idSite, $period, $date, $segment = false)
    {
        
        $rows = AgAnalytics::getReport();

        $arr = [];

        foreach($rows as $row) {
            $arr[] = array(
                'campaign_id' => sprintf("%d ", $row['campaign_id']),
                'campaign_name' => $row['campaign_name'],
                'impressions' => $row['impressions'],
                'clicks' => $row['clicks'],
                'cpc' => $row['cpc'],
                'cpm' => $row['cpm'],
                'cpp' => $row['cpp'],
                'ctr' => $row['ctr'],
            );
        }

        return DataTable::makeFromSimpleArray($arr);

//         $table = DataTable::makeFromSimpleArray(array(
//             array('label' => 'My Label 1', 'nb_visits' => '1'),
//             array('label' => 'My Label 2', 'nb_visits' => '5'),
//         ));

//         return $table;
        
    }
    
    
}
