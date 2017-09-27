<?php
const DEFAULT_CSV = 'category_export.csv';
const DEFAULT_CSV_PATH =  'var/export/';
$exclude  = array('path','id');
define('MAGENTO', realpath(dirname(__FILE__)));
require_once MAGENTO . '/app/Mage.php';
Mage::app();
mkdir(DEFAULT_CSV_PATH,777,TRUE);
if($argv[1] == 'import')
{
$category = Mage::getModel('catalog/category');
$tree = $category->getTreeModel();
$tree->load();
$ids = $tree->getCollection()->getAllIds();
$categories = array();
if ($ids)
{
    $file = $argv[2];
 
    $fp = fopen(($file != ''?  $file : DEFAULT_CSV_PATH.DEFAULT_CSV), 'r');
    $i = 0;

  while (($data = fgetcsv($fp) ) !== FALSE ) {
                if(!$i)
                {
                    $header = $data;
                    $i++;
                    continue;
                }
                else
                {   

                    $toSave  = array();
                    
                    foreach ($header as $head) {
                        if(in_array($head,$exclude))
                        {
                            $j++;
                            continue;
                        }

                        $toSave[$head] = $data[$j];
                            $j++;
                    }
                    try
                    {
                    Mage::getModel('catalog/category')->load($data[0],'entity_id')->addData($toSave)->setId($data[0])->save();
                        
                    }
                    catch(Exception $e)
                    {
                        echo $e->getMessage();
                    }


                }
                die;
        }

}
}

else if($argv[1] == 'export')
{
$category = Mage::getModel('catalog/category');
$tree = $category->getTreeModel();
$tree->load();
$ids = $tree->getCollection()->getAllIds();
$categories = array();
if ($ids)
{
    // Open file and print headers
    $file = $argv[2];
    $fp = fopen($file ? $file : DEFAULT_CSV_PATH.DEFAULT_CSV, 'w');
    $headers = ['id', 'name', 'path', 'url','meta_description', 'meta_keywords'];
    fputcsv($fp, $headers, ",", '"');
    // Loop throught every category ID in the category tree
    foreach ($ids as $id)
    {
        // Ignore categories 1 and 2
        if(in_array($id, [1,2])) {
            continue;
        };
        // Load the category and get the URL key, name, and path
        $category->load($id);
        $url_key = Mage::helper('catalog/category')->getCategoryUrlPath($category->getUrlPath(), true);
        $categories[$id]['name'] = $category->getName();
        $categories[$id]['path'] = $category->getPath();
        // Convert the numeric path into category names
        $path = explode('/', $categories[$id]['path']);
        $pathnames = array();
        foreach ($path as $pathId)
        {
            // Ignore categories 1 and 2
            if(in_array($pathId, [1,2])) {
                continue;
            };
            //$pathCat=Mage::getModel('catalog/category')->load($pathId);
            //$pathnames[]=$pathCat->getName();
            $pathnames[] = $categories[$pathId]['name'];
        }
        // Save this line to the CSV file
        $line = [$id, $category->getName(), implode(' > ', $pathnames), $url_key, $category->getMetaDescription(),$category->getMetaKeywords()];
        fputcsv($fp, $line, ",", '"');
    }
    // Close the file handle
    fclose($fp);
}
}
else
{
    echo "Unsupported Operation";
}
