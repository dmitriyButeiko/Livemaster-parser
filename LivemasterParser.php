<?php

require_once "includes/simpleHtmlDom.php";

class LivemasterParser
{

    /**
     * @var void
     */
    private $siteUrl = "https://www.livemaster.ru/";

    /**
     * @var void
     */
    private $simpleHtmlDom;



    public function loadProducts($productsData)
    {
        $fromPage = $productsData["fromPage"];
        $toPage = $productsData["toPage"];
        $categoryUrl = $productsData["categoryUrl"];

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
            $productsUrls = $this->parseProductsUrls($singleHtmlFile["html"]);

            $curlDescriptorsProducts = $this->getCurlDescriptors(count($productsUrls), $productsUrls);
            $curlMultiProducts = $this->createCurlMulti($curlDescriptorsProducts);
            $htmlProductsArray = $this->executeCurlMultiAndGetHtmlArray($curlMultiProducts, $curlDescriptorsProducts);

           // var_dump($htmlProductsArray);
            //echo count($htmlProductsArray) . "\n";
            foreach($htmlProductsArray as $singleProductHtml)
            {
                echo strlen($singleProductHtml["html"]) . "\n";
            }
            exit;
        }

    }



    /**
     *
     */
    public function getInstance()
    {
        static $inst = null;
        if ($inst === null) {
            $inst = new LivemasterParser();
        }
        return $inst;
    }
    /*
    *
    */
    public function getProductsUrlsByCategoryAndPageNumber($categoryUrl, $pageNumber)
    {
        $productsPageHtml = $this->getHtml($this->siteUrl . $categoryUrl);

        $productsUrls = $this->parseProductsUrls($productsPageHtml);

        return $productsUrls;
    }

    /*
    *
    */
    public function getNumberOfProductPagesByCategoryUrl($categoryUrl)
    {
        $categoryHtml = $this->getHtml($this->siteUrl . $categoryUrl);

        $html = str_get_html($categoryHtml);
        $numberOfPages = $html->find('#ajax-content form[method="post"] ', 1)->innertext;
        var_dump($numberOfPages);

        return $numberOfPages;
    }

    /*
    *
    */
    public function getProductInfoByProductUrl($productUrl)
    {

        $productHtml = $this->getHtml($this->siteUrl . $productUrl);
        $productInfo = $this->parseProductInfo($productHtml);

        return $productInfo;
    }

    /**
     *
     */
    public function getCategoriesList()
    {
        $mainPageHtml = $this->getHtml($this->siteUrl);

        $categoriesList = $this->parseCategoriesList($mainPageHtml);

        return $categoriesList;
    }

    /**
     *
     */
    private function __construct()
    {

    }

    /*
    *
    */
    public function generateProductsCategoryUrlByPageNumber($categoryUrl, $pageNumber)
    {
        if($pageNumber != 1)
        {
            $from = ($pageNumber - 1) * 40;
            $categoryUrl .= "?from=" . $from;
        }

        return $categoryUrl;
    }

    /*
    *
    */
    private function getHtml($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $html = curl_exec($ch);
        return $html;
    }

    /**
     * @param void $html
     */
    private function parseCategoriesList($html)
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

    /**
     * @param void $html
     */
    private function parseProductInfo($html)
    {
        $productInfo = array();
        $html = str_get_html($html);

        $productInfo["name"] = $html->find("h1.item-page-item-name span", 0)->innertext;
        $productInfo["price"] = strip_tags($html->find("div.item-page-item-price span span", 0)->innertext);
        $productInfo["mainPhotoUrl"] = $html->find("#item-page-main-photo-img", 0)->src;


        $productInfo["description"] = trim(strip_tags($html->find(".container-main div.item-page-desc-block", 0)->innertext));

        return $productInfo;
    }

    /**
     * @param void $html
     */
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

    /*
    *
    */
    private function getCurlDescriptor($url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        return $ch;
    }

    /*
    *
    */
    private function createCurlMulti($descriptors)
    {
        $mh = curl_multi_init();

        foreach ($descriptors as $singleDescriptor) {
            curl_multi_add_handle($mh, $singleDescriptor);
        }

        return $mh;
    }

    /*
    *
    */
    private function getCurlDescriptors($amount, $urls)
    {
        $curlDescriptors = array();

        for ($i = 0; $i < $amount; $i++) {
            $curlDescriptors[$i] = $this->getCurlDescriptor($urls[$i]);
        }

        return $curlDescriptors;
    }

    /*
    *
    */
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
    private function loadImageFromUrl($url)
    {
        $type = pathinfo($url, PATHINFO_EXTENSION);
        $fileName = $this->generateRandomString . "." . $type;

        $content = file_get_contents($url);
    }


    /*
    *
    */
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