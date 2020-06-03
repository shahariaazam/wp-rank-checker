<?php
/**
 * RankChecker class
 *
 * @package ShahariaAzam\WPRankChecker
 */

namespace ShahariaAzam\WPRankChecker;

use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
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
     * @var CacheItemPoolInterface
     */
    private $cache;

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
     * @param ClientInterface $httpClient
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(ClientInterface $httpClient, CacheItemPoolInterface $cache = null)
    {
        $this->setHttpClient($httpClient);
        $this->searchPageLimit = 5;
        $this->cache = $cache;
    }

    /**
     * @param int $searchPageLimit
     * @return RankChecker
     */
    public function setSearchPageLimit(int $searchPageLimit)
    {
        $this->searchPageLimit = $searchPageLimit;
        return $this;
    }

    /**
     * @param mixed $keyword
     * @return RankChecker
     */
    public function setKeyword($keyword)
    {
        $this->keyword = $keyword;
        return $this;
    }

    /**
     * @return RankChecker
     * @throws RankCheckerException
     */
    public function checkRanks()
    {
        if (empty($this->keyword)) {
            throw new RankCheckerException("You didn't provide any keyword");
        }

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
     * @param int $page
     * @return string
     * @throws RankCheckerException
     */
    private function fetchPage($page = 1)
    {
        $cacheItemKey = 'WP_Rank_Checker_FetchPage_' . $page;

        if (!empty($this->cache)) {
            if ($this->cache->hasItem($cacheItemKey)) {
                return $this->cache->getItem($cacheItemKey)->get();
            }
        }

        $resultPageUrl = sprintf("https://wordpress.org/plugins/search/%s/page/%d", $this->keyword, $page);

        try {
            $response = $this->httpRequest('GET', $resultPageUrl);
        } catch (ClientExceptionInterface $e) {
            throw new RankCheckerException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        if ($response->getStatusCode() !== 200) {
            throw new RankCheckerException("Failed to fetch " . $resultPageUrl, $response->getStatusCode());
        }

        $responseBody = (string) $response->getBody();
        if (empty($responseBody)) {
            throw new RankCheckerException("Empty response from " . $resultPageUrl, $response->getStatusCode());
        }

        $responseData = (string) $response->getBody();

        if (!empty($this->cache)) {
            $cachedItem = $this->cache->getItem($cacheItemKey);
            $cachedItem->set($responseData);
            $this->cache->save($cachedItem);
        }

        return $responseData;
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
            throw new RankCheckerException(".plugin-card HTML node couldn't be found in the DOM tree");
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
