<?php

require_once "CsvHelper.php";
require_once "includes/simpleHtmlDom.php";

class LivemasterParser
{
    private $siteUrl = "https://www.livemaster.ru/";
    private $imagesPath = "images/";

    public function loadProducts($productsData, $numberOfThreads = 10)
    {
        $fromPage = $productsData["fromPage"];
        $toPage = $productsData["toPage"];
        $categoryUrl = $productsData["categoryUrl"];

        if(strpos($categoryUrl, "https://") == false)
        {
            $categoryUrl = $this->siteUrl . $categoryUrl;
        }

        $categoriesPagesUrls = array();

        for($i = $fromPage; $i < $toPage; $i++)
        {
            $categoriesPagesUrls[] = $this->generateProductsCategoryUrlByPageNumber($categoryUrl, $i);
        }

        $curlDescriptors = $this->getCurlDescriptors(count($categoriesPagesUrls), $categoriesPagesUrls);
        $curlMulti = $this->createCurlMulti($curlDescriptors);
        $htmlArray = $this->executeCurlMultiAndGetHtmlArray($curlMulti, $curlDescriptors);

        foreach($htmlArray as $singleHtmlFile)
        {
            $csvHelper = new CsvHelper();
            $productsUrls = $this->parseProductsUrls($singleHtmlFile["html"]);

            $amountOfElements = count($productsUrls);
            $partOfProductsArray = array();

            // делит массив url на части в соотвествии с количеством потоков установленных пользователем
            for ($i = 0; $i < $amountOfElements; $i = $i + $numberOfThreads) {
                $urlsList = array();
                for ($j = $i; $j < $i + $numberOfThreads; $j++) {
                    if (isset($productsUrls[$j]) && !(is_null($productsUrls[$j]))) {
                        $urlsList[] = $productsUrls[$j];
                    }
                }
                $curlDescriptorsProducts = $this->getCurlDescriptors(count($urlsList), $urlsList);
                $curlMultiProducts = $this->createCurlMulti($curlDescriptorsProducts);
                $htmlProductsArray = $this->executeCurlMultiAndGetHtmlArray($curlMultiProducts, $curlDescriptorsProducts);

                foreach($htmlProductsArray as $singleProductHtml)
                {
                    $partOfProductsArray[] = $this->parseProductInfo($singleProductHtml["html"]);
                }

                var_dump($partOfProductsArray);

                $csvHelper->generateProductsCsvFile($partOfProductsArray);
            }
        }
    }

    public function getInstance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new LivemasterParser();
        }
        return $inst;
    }

    public function getProductsUrlsByCategoryAndPageNumber($categoryUrl, $pageNumber)
    {
        $productsPageHtml = $this->getHtml($this->siteUrl . $categoryUrl);
        $productsUrls = $this->parseProductsUrls($productsPageHtml);

        return $productsUrls;
    }

    public function getNumberOfProductPagesByCategoryUrl($categoryUrl)
    {
        $categoryHtml = $this->getHtml($this->siteUrl . $categoryUrl);

        $html = str_get_html($categoryHtml);
        $numberOfPages = $html->find('#ajax-content form[method="post"] ', 1)->innertext;

        return $numberOfPages;
    }

    public function getSubcategoriesByCategoryUrl($categoryUrl)
    {
        $categoryHtml = $this->getHtml($categoryUrl);
        $subcategoriesList = $this->parseSubcategoriesList($categoryHtml);

        return $subcategoriesList;
    }

    public function getProductInfoByProductUrl($productUrl)
    {

        $productHtml = $this->getHtml($this->siteUrl . $productUrl);
        $productInfo = $this->parseProductInfo($productHtml);

        return $productInfo;
    }

    public function getMainCategoriesList()
    {
        $mainPageHtml = $this->getHtml($this->siteUrl);
        $categoriesList = $this->parseMainCategoriesList($mainPageHtml);

        return $categoriesList;
    }

    public function generateProductsCategoryUrlByPageNumber($categoryUrl, $pageNumber)
    {
        if($pageNumber != 1)
        {
            $from = ($pageNumber - 1) * 40;
            $categoryUrl .= "?from=" . $from;
        }

        return $categoryUrl;
    }

    private function __construct()
    {

    }

    private function getHtml($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $html = curl_exec($ch);
        return $html;
    }

    private function parseMainCategoriesList($html)
    {
        $categoriesList = array();
        $html = str_get_html($html);
        $counter = 0;


        foreach($html->find("#sidebar-catalogue-main-text a") as $singleCategoryLink)
        {
            $categoriesList[$counter] = array();
            $categoriesList[$counter]["categoryName"] = strip_tags($singleCategoryLink->innertext); 
            $categoriesList[$counter]["categoryUrl"] = $singleCategoryLink->href;  
            $counter++;
        }

        return $categoriesList;
    }


    private function parseSubcategoriesList()
    {
        
    }

    private function decodeTextInGoodForm($text)
    {
        $text = strip_tags($text);
        $text = html_entity_decode($text);
        $text = trim($text);
        $text = preg_replace( "/\r|\n/", "", $text);
        $text = str_replace("   ", "", $text);
        $text = str_replace("\"", "'", $text);

        $text = preg_replace('/\s\s+/', ' ',  $text);

        $text  = trim(preg_replace('/\s\s+/', ' ',  $text));
        return $text;
    }

    private function parseProductInfo($html)
    {
        $productInfo = array();
        $html = str_get_html($html);

        $productInfo["name"] = $this->decodeTextInGoodForm($html->find("h1.item-page-item-name span", 0)->innertext);
        $productInfo["price"] = $this->decodeTextInGoodForm($html->find("div.item-page-item-price span span", 0)->innertext);

        $mainPhotoUrl = $this->decodeTextInGoodForm($html->find("#item-page-main-photo-img", 0)->src);

        $photoPath = $this->loadImageFromUrl($mainPhotoUrl);

        $productInfo["photoPath"] = $photoPath;

        $productInfo["description"] = $this->decodeTextInGoodForm($html->find(".container-main div.item-page-desc-block", 0)->innertext);

        return $productInfo;
    }

    private function parseProductsUrls($html)
    {
        $productsUrlsList = array();

        $html = str_get_html($html);

        foreach($html->find("#objects .grid-item .title a") as $singleProductLink)
        {
            $productsUrlsList[] = $this->siteUrl . $singleProductLink->href;
        }

        return $productsUrlsList;
    }

    private function getCurlDescriptor($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        return $ch;
    }

    private function createCurlMulti($descriptors)
    {
        $mh = curl_multi_init();

        foreach ($descriptors as $singleDescriptor) {
            curl_multi_add_handle($mh, $singleDescriptor);
        }

        return $mh;
    }

    private function getCurlDescriptors($amount, $urls)
    {
        $curlDescriptors = array();

        for ($i = 0; $i < $amount; $i++) {
            $curlDescriptors[$i] = $this->getCurlDescriptor($urls[$i]);
        }

        return $curlDescriptors;
    }

    private function executeCurlMultiAndGetHtmlArray($mh, $curlArr)
    {
        $htmlArray = array();
      
        //запускаем дескрипторы
        do {
            curl_multi_exec($mh, $running);
        } while($running > 0);

        $counter = 0;    
        $node_count = count($curlArr);
           
        for($i = 0; $i < $node_count; $i++)
        {
            $htmlArray[$counter]         = array();
           // $htmlArray[$counter]["url"]  = curl_getinfo($curlArr[$i], CURLINFO_EFFECTIVE_URL);
            $htmlArray[$counter]["html"] = curl_multi_getcontent( $curlArr[$i]  );
            $counter++;
            curl_close($curlArr[$i]);
        }
        curl_multi_close($mh);

        return $htmlArray;
    }
    private function loadImageFromUrl($imageUrlToLoad)
    {
        $loadedFileType = pathinfo($imageUrlToLoad, PATHINFO_EXTENSION);
        $loadedFileName = $this->generateRandomString(10) . "." . $loadedFileType;
        $loadedImageBinaryData = file_get_contents($imageUrlToLoad);

        $fullLoadedFileName = $this->imagesPath . $loadedFileName;

        if(!file_exists($fullLoadedFileName))
        {
            file_put_contents($fullLoadedFileName, $loadedImageBinaryData);
            return $fullLoadedFileName;
        }
        else
        {
            return loadImageFromUrl($imageUrlToLoad);
        }
    }

    function generateRandomString($length = 10) 
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
}