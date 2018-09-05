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
// use Piwik\Plugins\VisitorGenerator\Generator\Websites;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

//750518125387-oaua4h7sbd0790bfst001qmrrne39svq.apps.googleusercontent.com
//3_6IFMMzK8wSX5lJ67LszF06
//4/AADQA_HOLWndh2pLHq4q4pq-uC0QCyIYt6z77HnH0o2NqT4a_LJAF9A

// Copy the following lines to your 'adsapi_php.ini' file:
// clientId = "750518125387-oaua4h7sbd0790bfst001qmrrne39svq.apps.googleusercontent.com"
// clientSecret = "3_6IFMMzK8wSX5lJ67LszF06"
// refreshToken = "1/aUwRGEpiQ0ajpdGpB4Y0QUIcvHIq_haLwZizQYc_vwA"




use Google\AdsApi\AdWords\AdWordsServices;
use Google\AdsApi\AdWords\AdWordsSession;
use Google\AdsApi\AdWords\AdWordsSessionBuilder;
use Google\AdsApi\AdWords\Query\v201802\ServiceQueryBuilder;
use Google\AdsApi\AdWords\v201802\cm\CampaignService;
use Google\AdsApi\AdWords\v201802\cm\Page;
use Google\AdsApi\Common\OAuth2TokenBuilder;



class ImportAdwords extends ConsoleCommand
{

    const PAGE_LIMIT = 500;

    
    protected function configure()
    {
        $this->setName('aganalytics:import-adwords');
        $this->setDescription('Import adwords metrics data. This command is intended for developers.');
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Defines how many websites should be generated', 10);
        
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    

    // Generate a refreshable OAuth2 credential for authentication.
        $oAuth2Credential = (new OAuth2TokenBuilder())->fromFile()->build();

        // Construct an API session configured from a properties file and the
        // OAuth2 credentials above.
        $session = (new AdWordsSessionBuilder())->fromFile()->withOAuth2Credential($oAuth2Credential)->build();
        self::runExample(new AdWordsServices(), $session);
    
    
    
    
        $message = sprintf('<info>Ok</info>');

        $output->writeln($message);
   
        
        
    }
    
    
    public static function runExample(
        AdWordsServices $adWordsServices,
        AdWordsSession $session
    ) {
        $campaignService = $adWordsServices->get($session,
            CampaignService::class);

        // Create AWQL query.
        $query = (new ServiceQueryBuilder())
            ->select(['Id', 'Name', 'Status'])
            ->orderByAsc('Name')
            ->limit(0, self::PAGE_LIMIT)
            ->build();

        do {

            // Advance the paging offset in subsequent iterations only.
            if (isset($page)) {
                $query->nextPage();
            }

            // Make a request using an AWQL string. This request will return the
            // first page containing up to `self::PAGE_LIMIT` results
            $page = $campaignService->query(sprintf('%s', $query));

            // Display results from second and subsequent pages.
            if ($page->getEntries() !== null) {
                foreach ($page->getEntries() as $campaign) {
                    printf(
                        "Campaign with ID %d and name '%s' was found.\n",
                        $campaign->getId(),
                        $campaign->getName()
                    );
                }
            }
        } while ($query->hasNext($page));

        printf("Number of results found: %d\n",
            $page->getTotalNumEntries());
    }
    

}
