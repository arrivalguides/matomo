<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AgAnalytics\Reports;

use Piwik\Piwik;
use Piwik\Plugin\Report;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugins\Actions\Columns\ExitPageUrl;
use Piwik\View;

use Piwik\Plugins\AgAnalytics\API;

/**
 * This class defines a new report.
 *
 * See {@link http://developer.piwik.org/api-reference/Piwik/Plugin/Report} for more information.
 */
class GetFacebookReport extends Base
{
    protected function init()
    {
        parent::init();

        $this->name          = 'Facebook Report';
        // If a subcategory is specified, the report will be displayed in the menu under this menu item
        $this->subcategoryId = $this->name;
        $this->documentation = 'Facebook Report Documentation';

        // This defines in which order your report appears in the mobile app, in the menu and in the list of widgets
        $this->order = 999;


        // Uncomment the next line if your report does not contain any processed metrics, otherwise default
        // processed metrics will be assigned
        // $this->processedMetrics = array();

        // Uncomment the next line if your report defines goal metrics
        // $this->hasGoalMetrics = true;

        // Uncomment the next line if your report should be able to load subtables. You can define any action here
        // $this->actionToLoadSubTables = $this->action;

        // Uncomment the next line if your report always returns a constant count of rows, for instance always
        // 24 rows for 1-24hours
        // $this->constantRowsCount = true;

    }

    /**
     * Here you can configure how your report should be displayed. For instance whether your report supports a search
     * etc. You can also change the default request config. For instance change how many rows are displayed by default.
     *
     * @param ViewDataTable $view
     */
    public function configureView(ViewDataTable $view)
    {

        $view->config->show_search = false;
        $view->requestConfig->filter_sort_column = 'campaign_name';
        // $view->requestConfig->filter_limit = 10';
        
        
//                 $view->config->display_logo_instead_of_label = true;
         $view->config->columns_to_display = array(
//                 'campaign_id',
                'campaign_name',
                'impressions',
                'clicks',
                'cpc',
                'cpm',
                'cpp',
                'ctr',
        );
        $view->config->addTranslation('campaign_id', 'Campaign ID');
        $view->config->addTranslation('campaign_name', 'Campaign Name');

    }

    /**
     * Here you can define related reports that will be shown below the reports. Just return an array of related
     * report instances if there are any.
     *
     * @return \Piwik\Plugin\Report[]
     */
    public function getRelatedReports()
    {
        return array(); // eg return array(new XyzReport());
    }
    

    /**
     * By default your report is available to all users having at least view access. If you do not want this, you can
     * limit the audience by overwriting this method.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return Piwik::hasUserSuperUserAccess();
    }
    
}
