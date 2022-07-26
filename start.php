<?php

require __DIR__.'/vendor/autoload.php'; // Composer's autoloader
spl_autoload_register(function ($class_name) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});
use Service\OlxScrapper;
use Service\OLX\Page;
$base_path = __DIR__;
$page_url = 'https://www.olx.uz/obyavlenie/rabota/dostojnaya-oplata-truda-v-yandeks-yandex-ID2QILV.html';
$scrapper = new OlxScrapper();
$scrapper->base_path = $base_path;
$crawler = $scrapper->getProductPage($page_url);
$page_scrapper = new Page($scrapper->client);
$page_scrapper->getBodyData($crawler);
print_r($page_scrapper->page);

$scrapper->client->close();
$scrapper->client->quit();
