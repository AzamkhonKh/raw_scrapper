<?php

namespace Service\OLX;
use Service\OlxScrapper;
use Symfony\Component\DomCrawler\Crawler;

class MainPage extends OlxScrapper
{
    public function load($port = 9515): Crawler
    {
        $this->setClient($port);
        $this->crawler = $this->client->request('GET', $this->endpoint);
        echo "loaded watit for item  ! \n";
        $this->client->waitFor('.item');
        echo "let's crawl ! \n";
        return $this->crawler->filter('div.maincategories-list > div');
    }
}