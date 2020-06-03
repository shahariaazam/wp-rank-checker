<?php
/**
 * RankCheckerTests class
 *
 * @package  ShahariaAzam\WPRankChecker\Tests\src
 */

namespace ShahariaAzam\WPRankChecker\Tests\src;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use ShahariaAzam\WPRankChecker\PluginEntity;
use ShahariaAzam\WPRankChecker\RankChecker;
use ShahariaAzam\WPRankChecker\RankCheckerException;
use Symfony\Component\Cache\Adapter\NullAdapter;

class RankCheckerTests extends TestCase
{
    public function testRank()
    {
        $fakePlugin = new PluginEntity();
        $fakePlugin->setTitle('Demo 1');
        $fakePlugin->setSlug('demo-1');
        $fakePlugin->setRating(3);
        $fakePlugin->setSummary('Dummy Summary');
        $fakePlugin->setAuthor('John Doe');

        $httpClient = $this->getMockHttpClient(200, [], $this->getMockPluginCardDom([$fakePlugin]));

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('Test')
            ->setSearchPageLimit(2)
            ->checkRanks();
        $pluginLists = $rankChecker->getResults();

        $this->assertIsArray($pluginLists);
        $this->assertEquals('demo-1', $pluginLists[1]->getSlug());
        $this->assertCount(2, $pluginLists);
    }

    public function testGetRankBySlug()
    {
        $fakePluginOne = new PluginEntity();
        $fakePluginOne->setTitle('Demo 1');
        $fakePluginOne->setSlug('demo-1');

        $fakePluginTwo = new PluginEntity();
        $fakePluginTwo->setTitle('Demo 2');
        $fakePluginTwo->setSlug('demo-2');

        $httpClient = $this->getMockHttpClient(200, [], $this->getMockPluginCardDom([$fakePluginOne, $fakePluginTwo]));

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('demo-2')
            ->checkRanks();
        $this->assertEquals(2, $rankChecker->getRankBySlug('demo-2'));
        $this->assertNull($rankChecker->getRankBySlug(null));
    }

    public function testFetchWithDifferentHTTPStatusCode()
    {
        $this->expectException(RankCheckerException::class);

        $httpClient = $this->getMockHttpClient(201, [], null);

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('Test')
            ->checkRanks()
            ->getResults();
    }

    public function testIfEmptyKeyword()
    {
        $this->expectException(RankCheckerException::class);

        $httpClient = $this->getMockHttpClient(201, [], null);

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->checkRanks()
            ->getResults();
    }

    public function testFetchWithEmptyResponse()
    {
        $this->expectException(RankCheckerException::class);

        $httpClient = $this->getMockHttpClient(200, [], '');

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('Test')
            ->checkRanks()
            ->getResults();
    }

    public function testHTTPExceptions()
    {
        $this->expectException(RankCheckerException::class);

        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClient->method('sendRequest')->willThrowException($this->getMockBuilder(ClientExceptionInterface::class)->getMock());

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('Test')
            ->checkRanks()
            ->getResults();

        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClient->method('sendRequest')->willThrowException($this->getMockBuilder(ClientExceptionInterface::class)->getMock());

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('Test')
            ->checkRanks()
            ->getResults();
    }

    public function testFetchWithInvalidDOMStructure()
    {
        $this->expectException(RankCheckerException::class);

        $httpClient = $this->getMockHttpClient(200, [], '<div></div>');

        $rankChecker = new RankChecker($httpClient, new NullAdapter());
        $rankChecker->setKeyword('Test')
            ->checkRanks()
            ->getResults();
    }

    /**
     * @param int $statusCode
     * @param array $headers
     * @param null $data
     * @return MockObject|ClientInterface
     */
    private function getMockHttpClient($statusCode = 200, array $headers = [], $data = null)
    {
        $responseMock = new Response($statusCode, $headers, $data);
        $httpClientMock = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpClientMock->method('sendRequest')->willReturn($responseMock);
        return $httpClientMock;
    }

    /**
     * @param $plugins  PluginEntity[]
     * @return string
     */
    private function getMockPluginCardDom($plugins)
    {
        $pluginCards = null;
        foreach ($plugins as $plugin) {
            $pluginCards .= <<<EOF
<article class="plugin-card post-110743 plugin type-plugin status-publish hentry plugin_category-communication plugin_contributors-shahariaazam plugin_committers-shahariaazam plugin_tags-email plugin_tags-gateway plugin_tags-mailgun plugin_tags-mailjet plugin_tags-mandrill">
	<div class="entry-thumbnail">
		<a href="https://wordpress.org/plugins/{$plugin->getSlug()}/" rel="bookmark">
			<img class="plugin-icon" srcset="https://ps.w.org/{$plugin->getSlug()}/assets/icon-128x128.jpg?rev=2172598, https://ps.w.org/{$plugin->getSlug()}/assets/icon-256x256.jpg?rev=2172598 2x" src="https://ps.w.org/{$plugin->getSlug()}/assets/icon-256x256.jpg?rev=2172598">		</a>
	</div><div class="entry">
		<header class="entry-header">
			<h3 class="entry-title"><a href="https://wordpress.org/plugins/{$plugin->getSlug()}/" rel="bookmark">{$plugin->getTitle()}</a></h3>		</header><!-- .entry-header -->

		<div class="plugin-rating"><div class="wporg-ratings" aria-label="3.5 out of 5 stars" data-title-template="%s out of 5 stars" data-rating="{$plugin->getRating()}" style="color:#ffb900"><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-filled"></span><span class="dashicons dashicons-star-half"></span><span class="dashicons dashicons-star-empty"></span></div><span class="rating-count">(<a href="https://wordpress.org/support/plugin/{$plugin->getSlug()}/reviews/">3<span class="screen-reader-text"> total ratings</span></a>)</span></div>
		<div class="entry-excerpt">
			<p>{$plugin->getSummary()}</p>
		</div><!-- .entry-excerpt -->
	</div>
	<hr>
	<footer>
		<span class="plugin-author">
			<i class="dashicons dashicons-admin-users"></i> {$plugin->getAuthor()} 		</span>
		<span class="active-installs">
			<i class="dashicons dashicons-chart-area"></i>
			80+ active installations		</span>
					<span class="tested-with">
				<i class="dashicons dashicons-wordpress-alt"></i>
				Tested with 5.4.1			</span>
				<span class="last-updated">
			<i class="dashicons dashicons-calendar"></i> 
			Updated 1 month ago		</span>
	</footer>
	</article>
EOF;
        }

        return $pluginCards;
    }
}
