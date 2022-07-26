<?php

namespace Service\OLX;
use Symfony\Component\DomCrawler\Crawler;
use Facebook\WebDriver\Exception\InvalidArgumentException;
use Symfony\Component\Panther\Client;

class Page{

    public Client $client;

    public function __construct(Client $client){
        $this->client = $client;
    }

    public array $page = array();
    public ?int $data_id = null;
    public function getBodyData(Crawler $crawler): void
    {
        $this->getTitle($crawler);
        $this->getPrice($crawler);
        $this->getDescription($crawler);
        $this->getOlxID($crawler);
        $this->getPublishedAt($crawler);
        $this->getTags($crawler);
        $this->getContact($crawler);
    }

    public function getTitle(Crawler $crawler) : string
    {
        $this->page['title'] = $crawler->filter('h1[data-cy="job_ad_title"]')->first()->text();
        return $this->page['title'];
    }

    public function getPrice(Crawler $crawler) : string
    {
        $this->page['price'] = $crawler->filter('div[data-testid="ad-price-container"] > h3')->eq(0)->text();
        return $this->page['price'];
    }

    public function getDescription(Crawler $crawler) : string
    {
        $this->page['description'] = $crawler->filter('.css-1shxysy > div')->text();
        return $this->page['description'];
    }
    public function getTags(Crawler $crawler) : array
    {
        $this->page['tags'] = [];
        $crawler->filter('ul[data-testid="job-ad-parameters"] > li')->each(function (Crawler $node, $i) {
            $add_tag = [];
            if($node->text() == ""){
                return null;
            }
            $html = $node->filter('div')->each(fn(Crawler $node, $i) => $node->html());
            $add_tag = [
                'title' => $html[0], 
                'value' => (new Crawler($html[1]))->text()
            ];

            if(!is_null($this->data_id)){
                $add_tag['data_id'] = $this->data_id;
            }
            $add_tag['created_at'] = $add_tag['updated_at'] = now();
            $this->page['tags'][] = $add_tag;

            return $html;
        });
        return $this->page['tags'];
    }

    public function getContact(Crawler $crawler) : array
    {
        $this->page['seller'] = [];
        $chat = $crawler->filter('div[data-cy="seller_card"] > div');
        $this->getSellerName($chat);
        $this->getSellerPhone($chat);

        return $this->page['seller'];

    }

    public function getSellerName(Crawler $crawler) : string
    {
        $this->page['seller']['name'] = $crawler->filter('h4')->first()->text();
        return $this->page['seller']['name'];
    }
    public function getSellerPhone($crawler) : string
    {
        // echo "getting phone ... \n";
        // // there we should click to button
        // $this->client->executeScript("document.querySelector('[data-cy=\"ad-contact-phone\"]').click()");

        $this->client->executeScript("document.querySelector('#root > div.css-50cyfj > div.css-1on7yx1 > div:nth-child(3) > div.css-1pyxm30 > div:nth-child(1) > div:nth-child(3) > div > button.css-65ydbw-BaseStyles').click()");

        sleep(5);

        $crawler = $this->client->waitForVisibility('.css-65ydbw-BaseStyles');

        $this->page['seller']['phone'] = $crawler->filter('.css-v1ndtc')->text();

        return $this->page['seller']['phone'];
    }

    public function getOlxID(Crawler $crawler): string
    {
        $this->page['ID'] = $crawler->filter('div[data-cy="ad-footer-bar-section"] > span')->first()->html();

        return $this->page['ID'];
    }

    public function getPublishedAt(Crawler $crawler): string
    {
        switch($this->from){
            case IndexLink::OLX_JOBS:{
                $this->page['published'] = $crawler->filter('span.css-ubdo89-Text > span')->first()->html();
                break;
            }
            default:{
                $this->page['published'] = $crawler->filter('span[data-cy="ad-posted-at"]')->first()->html();
            }
        }

        return $this->page['published'];
    }

    public function debug(Crawler $crawler)
    {
        dd($crawler->each(function (Crawler $node, $i) {
            $html = $node->html();
            return $html;
        }));
    }
}