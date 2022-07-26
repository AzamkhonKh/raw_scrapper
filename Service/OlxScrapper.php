<?php
namespace Service;
use Symfony\Component\Panther\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\IndexLink;
class OlxScrapper
{
    public Crawler $crawler;
    public Client $client;
    public string $endpoint;
    public array $page;
    public int $page_number = 1;
    public function __construct($endpoint = 'http://www.olx.uz/')
    {
        $this->setClient();
        $this->client->ping(10000);
        $this->endpoint = $endpoint;
        $this->page = [];
    }

    public function setClient(): Client{
        return $this->client = Client::createChromeClient(null, [
            '--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.90 Safari/537.36',
            '--window-size=1200,1100',
        ], 
        [
            "port" => 9080,
        ]);
        // return $this->client = Client::createFirefoxClient(base_path("drivers/geckodriver"), null, ['capabilities' => ['acceptInsecureCerts' => true]]);
    }

    public function index($url, int $page_number = 1, $method = 'GET'): Crawler{
        $this->setClient();
        $page_url = $this->endpoint.$url;
        if($page_number == 1){
            $crawler = $this->client->request($method, $page_url);
        }else{
            $crawler = $this->client->clickLink($page_number);
        }
        //data-testid="listing-grid"
        return $crawler->filter('div[data-testid="listing-grid"] > div[data-cy="l-card"]');
    }
    public function load(): Crawler
    {
        $this->crawler = $this->client->request('GET', $this->endpoint);
        echo "loaded watit for item  ! \n";
        $this->crawler = $this->client->waitFor('a[data-icon="close"]');
        $button_text = $this->crawler->filter('#cookiesBar > a[data-icon="close"]')->first()->text();
        $this->client->clickLink($button_text);
        echo "wait 5 sec ! \n";
        sleep(5);
        echo "let's crawl ! \n";
        $this->crawler = $this->client->refreshCrawler();
        $this->getButtons($this->crawler);
        return $this->crawler->filter('div.maincategories-list > div');
    }

    public function getButtons(Crawler $crawler): array
    {
        $this->page['subcat'] = array();
        $this->page['cat'] = ($crawler->filter('div.item > a[data-id]')->each(function (Crawler $node, $i) {
            $href = $node->attr('href');
            $id = $node->attr('data-id');
            $text = $node->text();
            if(!in_array($text, ["Отдам даром", "Обмен"])){
                sleep(2);
                $this->client->clickLink($text);
                $this->client->waitFor('#bottom'.$id);
                $this->crawler = $this->client->refreshCrawler();
                $this->getSubCategories($this->crawler, $id);
            }
            return [
                'url' => $href,
                'olx_id' => $id,
                'title' => $text
            ];
        }));
        return $this->page['cat'];
    }

    public function getSubCategories(Crawler $crawler, $id): array
    {
        // ->filter('#botton'.$id.' > ul > li > a')
        $this->page['subcat'][$id] = $crawler->filter('a[data-category-id="'.$id.'"]')->each(function(Crawler $node, $i){
            $href = $node->attr('href');
            $id = $node->attr('data-id');
            $text = $node->text();
            return [
                'url' => $href,
                'olx_id' => $id,
                'title' => $text
            ];
        });

        return $this->page['subcat'];
    }

    public function getProductPage($url) : Crawler
    {
        $this->setClient();
        $page_url = $this->endpoint.$url;
        $this->page['url'] = $page_url;
        echo $page_url . " - entering ... ";
        $crawl = $this->client->request('GET', $page_url);
        echo ' request ended waiting description ! \n';
        // is it 404
        if($crawl->filter('#ticTacToeTheGame')->count() != 0){
            // OMG it's 404
            $path = $this->base_path.'/errors/sreen-'.date('Y-m-d_H:i:s').'.png';
            echo "screenshot path: ".$path."\n";
            $this->client->takeScreenshot($path);
            $this->client->close();
            $this->client->quit();
            exit();
        }

        $this->crawler = $this->client->waitFor('button[data-testid="show-phone"]');
        //$this->crawler = $this->client->waitFor('div[data-cy="ad_description"]');
        // $this->crawler = $this->client->waitForEnabled('button[data-cy="ad-contact-phone"]');
        return $this->crawler;
    }

    public function debug(Crawler $crawler)
    {
        dd($crawler->each(function (Crawler $node, $i) {
            $html = $node->html();
            return $html;
        }));
    }
}
