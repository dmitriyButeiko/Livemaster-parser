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

    /**
     *
     */
    private function __construct()
    {

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

        $productHtml = $this->getHtml($productUrl);
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
    private function parseProductInfo($html)
    {
        $productInfo = array();
        $html = str_get_html($html);

        $productInfo["name"] = $html->find("h1.item-page-item-name span", 0)->innertext;
        $productInfo["price"] = $html->find("h1.item-page-item-price span span", 0)->innertext;
        $productInfo["price"] = $html->find("h1.item-page-item-price span span", 0)->innertext;
        $productInfo["mainPhotoUrl"] = $html->find("#item-page-main-photo-img", 0)->src;


        
        return $productInfo;
    }

    /**
     * @param void $html
     */
    private function parseProductsUrls($html)
    {
        
    }
}