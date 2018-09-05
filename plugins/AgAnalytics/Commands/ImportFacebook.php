<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\AgAnalytics\Commands;

use Piwik\Access;
use Piwik\Piwik;
use Piwik\Plugin\ConsoleCommand;

use Piwik\Plugins\AgAnalytics\AgAnalytics;


// use Piwik\Plugins\VisitorGenerator\Generator\Websites;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// use Facebook\Facebook;
// use Facebook\Exceptions\FacebookResponseException;
// use Facebook\Exceptions\FacebookSDKException;

// use FacebookAds\Object\AdUser;
use FacebookAds\Object\AdAccount;
// use FacebookAds\Object\Fields\AdSetFields;
// use FacebookAds\Object\Fields\UserFields;
// use FacebookAds\Object\Fields\AdAccountFields;
// use FacebookAds\Object\Values\InsightsPresets;
use FacebookAds\Api;
// use FacebookAds\Object\AdReportRun;
use FacebookAds\Object\Campaign;
use FacebookAds\Object\Values\AdsInsightsLevelValues;
use FacebookAds\Object\Values\AdsInsightsDatePresetValues;
// use FacebookAds\Object\Fields\AdsInsightsFields;
use FacebookAds\Object\AdsInsights;

use FacebookAds\Object\Fields\CampaignFields;


use Piwik\DataTable;
use Piwik\DataTable\Row;


class ImportFacebook extends ConsoleCommand
{

    const app_id = '238786920023198';
    const app_secret = '9a00e2c2679c35d25249dbd0fc4d4e11'; 
    
    const access_token = 
    'EAADZALOmLhJ4BAEQaSXX35JIxleK6PGp1isEBx6o37qgVDFcHN7WdFG5LD6yKZAQK5SfGDjq9swgwlGabWEJIUNZCb8MPWdxLKjV2KMgyUKKk8R2OacZBp2Mn2s5eU0ulTVm8R4FAcPdjUJ1dyK0PSly1VXutA1HEsxyPipxEz1ja6nv8GAC6n8WJUdl6LAZD';
    
    const ad_id = 'act_171951759892573';
    
    
    protected function configure()
    {
        $this->setName('aganalytics:import-facebook');
        $this->setDescription('Import facebook metrics data. This command is intended for developers.');
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Defines how many websites should be generated', 10);
        
        // Initialize a new Session and instantiate an API object
        API::init(
            self::app_id, // App ID
            self::app_secret,
            self::access_token // Your user access token
        );
        
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    
    
//         $table = DataTable::makeFromSimpleArray(array(
//             array('label' => 'My Label 1', 'nb_visits' => '1'),
//             array('label' => 'My Label 2', 'nb_visits' => '5'),
//         ));
//         
//     print_r($table);
//     exit;
    
//         print_r(AgAnalytics::getReport());
//         exit;
    
    
        $account = new AdAccount(self::ad_id);

//         $campaigns = $account->getCampaigns(array(
//             CampaignFields::NAME
//         ));
        
        $campaigns = $account->getCampaigns(
            Campaign::getFieldsEnum()->getValues()
        );
        

        $i = 1;
        foreach ($campaigns as $campaign) {
            echo $i . ') ' .  $campaign->{CampaignFields::ID} . ' "' . $campaign->{CampaignFields::NAME} . "\"\n";
            $i++;
            
            
                $compain_info = [];
            
                foreach(Campaign::getFieldsEnum()->getValues() as $field) {
                    if(is_array($campaign->{$field})) {
                                echo json_encode($campaign->{$field}).PHP_EOL;
                    } else {
                        if(!empty($campaign->{$field})) {
                                echo $field . ': ' . $campaign->{$field}.PHP_EOL;
                        }
                    }
                    
                    $compain_info[$field] = $campaign->{$field};
                }
            
            echo "\n";

    
            $fields =  AdsInsights::getFieldsEnum()->getValues();
            $insights = $campaign->getInsights($fields, array(
                'date_preset' => AdsInsightsDatePresetValues::LAST_7D
            ));
            
            if($insights->count() == 0) continue;

            $compain_report = [];
            
            foreach ($insights as $insight) {
                foreach($fields as $field) {
                    if(is_array($insight->{$field})) {
                                echo $field . ': ' . json_encode($insight->{$field}).PHP_EOL;
                    } else {
                        if(!empty($insight->{$field})) {
                                echo $field . ': ' . $insight->{$field}.PHP_EOL;
                        }
                    }
                    
                     $compain_report[$field] = $insight->{$field};
                }
            }
            
            
            AgAnalytics::updateReport($campaign->{CampaignFields::ID},
                json_encode($compain_info),
                json_encode($compain_report)
            );
            
            echo "\n\n";
        }

        echo "Total: " . $campaigns->count() . "\n\n";
        
    }
    
//     protected function getReport($name)
//     {
//         $this->autoload();
//         if (isset($this->all[$name])) {
//             return $this->all[$name];
//         }
// 
//         $value = Db::fetchOne('SELECT option_value FROM `' . Common::prefixTable('option') . '` ' .
//                               'WHERE option_name = ?', $name);
// 
//         if ($value === false) {
//             return false;
//         }
// 
//         $this->all[$name] = $value;
//         
//         return $value;
//     }

//     protected function updateReport($id, $info = 'test1', $report = 'test2')
//     {
// 
//         $sql  = 'INSERT INTO `' . AgAnalytics::getReportTable() . '` (compaign_id, info, reports) ' .
//                 ' VALUES (?, ?, ?) ' .
//                 ' ON DUPLICATE KEY UPDATE reports = ?';
//         $bind = array($id, $info, $report, $report);
// 
//         Db::query($sql, $bind);
// 
//     }

}
