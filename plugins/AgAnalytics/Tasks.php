<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AgAnalytics;

use Piwik\Piwik;
use Piwik\Plugins\AgAnalytics\AgAnalytics;
use FacebookAds\Object\AdAccount;
use FacebookAds\Api as FacebookAPI;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Values\AdsInsightsLevelValues;
use FacebookAds\Object\Values\AdsInsightsDatePresetValues;
use FacebookAds\Object\AdsInsights;
use FacebookAds\Object\Fields\CampaignFields;


class Tasks extends \Piwik\Plugin\Tasks
{

    const app_id = '238786920023198';
    const app_secret = '9a00e2c2679c35d25249dbd0fc4d4e11'; 
    
    const access_token = 
    'EAADZALOmLhJ4BAEQaSXX35JIxleK6PGp1isEBx6o37qgVDFcHN7WdFG5LD6yKZAQK5SfGDjq9swgwlGabWEJIUNZCb8MPWdxLKjV2KMgyUKKk8R2OacZBp2Mn2s5eU0ulTVm8R4FAcPdjUJ1dyK0PSly1VXutA1HEsxyPipxEz1ja6nv8GAC6n8WJUdl6LAZD';
    
    const ad_id = 'act_171951759892573';


    public function schedule()
    {
//         $this->hourly('myTask');  // method will be executed once every hour
//         $this->daily('myTask');   // method will be executed once every day
//         $this->weekly('myTask');  // method will be executed once every week
//         $this->monthly('myTask'); // method will be executed once every month
// 
//         // pass a parameter to the task
//         $this->weekly('myTaskWithParam', 'anystring');
// 
//         // specify a different priority
//         $this->monthly('myTask', null, self::LOWEST_PRIORITY);
//         $this->monthly('myTaskWithParam', 'anystring', self::HIGH_PRIORITY);
        
        $this->daily('importFacebookReportTask');   // method will be executed once every day
    }
    
    public function importFacebookReportTask()
    {
        // do something
                // Initialize a new Session and instantiate an API object
        FacebookAPI::init(
            self::app_id, // App ID
            self::app_secret,
            self::access_token // Your user access token
        );
        
        $account = new AdAccount(self::ad_id);
        
        $campaigns = $account->getCampaigns(
            Campaign::getFieldsEnum()->getValues()
        );
        
        foreach ($campaigns as $campaign) {
            
            $compain_info = [];
            foreach(Campaign::getFieldsEnum()->getValues() as $field) {
                    $compain_info[$field] = $campaign->{$field};
            }

    
            $fields =  AdsInsights::getFieldsEnum()->getValues();
            $insights = $campaign->getInsights($fields, array(
                'date_preset' => AdsInsightsDatePresetValues::LAST_7D
            ));
            
            if($insights->count() == 0) continue;

            $compain_report = [];
            foreach ($insights as $insight) {
                foreach($fields as $field) {
                     $compain_report[$field] = $insight->{$field};
                }
            }
            
            AgAnalytics::updateReport($campaign->{CampaignFields::ID},
                json_encode($compain_info),
                json_encode($compain_report)
            );

        }
    }
    

    public function myTask()
    {
        // do something
    }

    public function myTaskWithParam($param)
    {
        // do something
    }
}
