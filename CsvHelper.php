<?php 
	ini_set('auto_detect_line_endings', true);

    class CsvHelper
    {
    	public function generateCategoriesCsvFile($categoriesList, $fileName = "categories.csv")
    	{
    		$list = $this->generateListArrayFromCategoriesList($categoriesList);
            $fp = fopen($fileName, 'w');

            foreach ($list as $line) {
   		        fputcsv($fp, $line);
            }

            fclose($fp);

            return $list;
    	}

    	public function generateProductsCsvFile($cproductsList, $fileName = "products.csv")
    	{
    		$list = $this->generateListArrayFromProductsList($categoriesList);
            $fp = fopen($fileName, 'w');

            foreach ($list as $line) {
   		        fputcsv($fp, $line);
            }

            fclose($fp);
    	}





    	private function generateListArrayFromCategoriesList($categoriesList)
    	{
    		$list = array();
    		$categoryIdCounter = 100;
    		$arrayCounter = 0;
    		foreach($categoriesList as $singleCategory)
    		{
    			$list[$arrayCounter] = array();
    			$list[$arrayCounter][] = $singleCategory["categoryName"];
    			$list[$arrayCounter][] = $categoryIdCounter;

    			$categoryIdCounter++;
    			$arrayCounter++;
    		}

    		return $list;
    	}

    	private function generateListArrayFromProductsList($categoriesList)
    	{

    	}
    }

?>