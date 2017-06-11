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
        $productsPageHtml = $this->getHtml($categoryUrl);

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
            $productsUrlsList[] = $singleProductLink->href;
        }

        return $productsUrlsList;
    }
}