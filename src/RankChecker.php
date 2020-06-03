<?php
/**
 * RankChecker class
 *
 * @package ShahariaAzam\WPRankChecker
 */

namespace ShahariaAzam\WPRankChecker;

use Exception;
use Psr\Http\Client\ClientExceptionInterface;
use ShahariaAzam\HTTPClientSupport\Exception\FlexiHTTPException;
use ShahariaAzam\HTTPClientSupport\HTTPSupport;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class RankChecker
 *
 * @package ShahariaAzam\WPRankChecker
 */
class RankChecker extends HTTPSupport
{
    /**
     * How many pages we need to crawl.
     * Currently WordPress displays 20 plugins in it's every search result page.
     * i.e: https://wordpress.org/plugins/search/mail/page/1
     *
     * So we will crawl 5 pages to get 100 plugins for that keyword
     *
     * @var int
     */
    private $searchPageLimit;

    /**
     * Keyword that we need to check rank for
     *
     * @var
     */
    private $keyword;

    /**
     * @var string
     */
    private $pluginListsHTML;

    /**
     * @var PluginEntity[]
     */
    private $results;

    /**
     * RankChecker constructor.
     *
     * @param $keyword
     */
    public function __construct($keyword)
    {
        $this->keyword = $keyword;
        $this->searchPageLimit = 5;
        $this->pluginListsHTML = null;
    }

    /**
     * @return RankChecker
     * @throws ClientExceptionInterface
     * @throws FlexiHTTPException
     */
    public function checkRanks()
    {
        for ($i = 1; $i <= $this->searchPageLimit; $i++) {
            $contents = $this->fetchPage($i);
            $dom = new Crawler($contents);
            $this->filterHTML($dom);
        }

        $this->parseResult();

        return $this;
    }

    /**
     * @return PluginEntity[]
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param  $slug
     * @return int|null
     */
    public function getRankBySlug($slug)
    {
        $result = array_filter(
            $this->results,
            function (PluginEntity $entity) use ($slug) {
                return $entity->getSlug() === $slug;
            }
        );

        if (empty($result)) {
            return null;
        }

        return array_keys($result)[0];
    }

    /**
     * @param  int $page
     * @return string
     * @throws ClientExceptionInterface
     * @throws FlexiHTTPException
     */
    private function fetchPage($page = 1)
    {
        $resultPageUrl = 'https://wordpress.org/plugins/search/' . $this->keyword . '/page/' . $page;
        $response = $this->httpRequest('GET', $resultPageUrl);

        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to fetch " . $resultPageUrl, $response->getStatusCode());
        }

        $responseBody = (string) $response->getBody();
        if (empty($responseBody)) {
            throw new Exception("Empty response received from " . $resultPageUrl, $response->getStatusCode());
        }

        return (string) $response->getBody();
    }

    /**
     * @param  Crawler $dom
     * @return RankChecker
     * @throws Exception
     */
    private function filterHTML(Crawler $dom)
    {
        $pc = $dom->filter('article.plugin-card');
        if ($pc->count() < 1) {
            throw new Exception(".plugin-card HTML node couldn't be found in the DOM tree");
        }

        $pc->each(
            function (Crawler $crawler) {
                $this->pluginListsHTML .= "<div class='plugin-card'>".$crawler->html()."</div>";
            }
        );

        return $this;
    }

    /**
     * @return RankChecker
     */
    private function parseResult()
    {
        $pcDom = new Crawler($this->pluginListsHTML);

        $pluginCard = $pcDom->filter('.plugin-card');

        $rank = 1;

        $pluginCard->each(
            function (Crawler $crawler) use (&$rank) {
                $titleDom = $crawler->filter('h3.entry-title');
                if (!empty($titleDom)) {
                    $title = $titleDom->eq(0);
                }

                $linkDom = $titleDom->filter('a');
                if (!empty($linkDom)) {
                    $link = $linkDom->attr('href');
                }

                $pluginRatingDom = $crawler->filter('.plugin-rating')->eq(0);
                $pluginRating = $pluginRatingDom->filter('.wporg-ratings')->eq(0)->attr('data-rating');

                $summaryExcerpt = $crawler->filter('.entry-excerpt')->text();
                $author = $crawler->filter('.plugin-author')->text();

                $plugin = new PluginEntity();
                $plugin->setTitle($title->text());
                $plugin->setUrl($link);

                $re = '/\/plugins\/(.*)\//';

                preg_match($re, $plugin->getUrl(), $matches, PREG_OFFSET_CAPTURE, 1);
                if (!empty($matches)) {
                    $plugin->setSlug($matches[1][0]);
                }

                $plugin->setRating((float) $pluginRating);
                $plugin->setSummary(trim($summaryExcerpt));
                $plugin->setAuthor(trim($author));


                $this->results[$rank] = $plugin;
                $rank++;
            }
        );

        return $this;
    }
}
