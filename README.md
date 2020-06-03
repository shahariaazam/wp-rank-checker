# WordPress Plugins/Themes Keyword Search Rank Checker

Check **ranks** for your plugins or themes with specific **keyword** and check how your plugins and themes are growing in WordPress public repository.
This library will calculate the search result ranking from [WordPress.org plugin repository search page](https://wordpress.org/plugins/search/mail/).

## Installation

You can add this as a composer package. So to add this package in your application, just
run the following command.

```bash
composer require shahariaazam/wp-rank-checker
```

## Usage

Easy to use. Following code snippet will give you an idea about how to get ranking position
of any specific plugin for any specific keyword.

```php
<?php

use Http\Adapter\Guzzle6\Client;
use ShahariaAzam\WPRankChecker\RankChecker;

require "vendor/autoload.php";

$client = new Client();

$rankChecker = new RankChecker('mail');
$rankChecker->setHttpClient($client);
$result = $rankChecker->checkRanks();

// Check positon of a plugin "wp-mail-gateway" for keyword "mail"
print_r($result->getRankBySlug('wp-mail-gateway')); // will return integer
print_r($result->getResults()); // will return a list of all plugins with search result position
```

## Issues

If you find any issues, please create an issue from [here](https://github.com/shahariaazam/wp-rank-checker/issues/new)

## Contribution

Any kinds of help to improve this library is welcome. Do something amazing and open a PR. I would be happy
to review and merge.

## Contributor

- [Shaharia Azam](https://github.com/shahariaazam)

Full list of contributors can be found from [here](https://github.com/shahariaazam/wp-rank-checker/graphs/contributors)