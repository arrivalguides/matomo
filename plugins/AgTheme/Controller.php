<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\AgTheme;

use Piwik\Development;
use Piwik\Piwik;
use Piwik\View;

class Controller extends \Piwik\Plugin\Controller
{
    public function demo()
    {
        if (! Development::isEnabled() || !Piwik::isUserHasSomeAdminAccess()) {
            return;
        }

        return $this->renderTemplate('demo');
    }
}
