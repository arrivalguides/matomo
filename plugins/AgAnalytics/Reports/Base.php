<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\AgAnalytics\Reports;

use Piwik\Plugin\Report;

abstract class Base extends Report
{
    protected function init()
    {
        $this->categoryId = 'AgAnalytics_ArrivalGuidesAB';
    }
}
