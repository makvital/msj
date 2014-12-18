<?php
include('SimpleImage.php');
$_image = new SimpleImage();

$baseurl = 'http://dev.marketsquarejewelers.com/store';

$options = array(
    'trace' => true,
    'connection_timeout' => 120,
    'wsdl_cache' => WSDL_CACHE_NONE,
);
$proxy = new SoapClient($baseurl.'/index.php/api/v2_soap/?wsdl=1', $options);
$sessionId = $proxy->login('msj-api', 'Msj@123');


if(strpos($data['mage_category'],',')) 
    $mage_category = explode(',', $data['mage_category']);
else
    $mage_category[0] = $data['mage_category'];

$mage_category_trimmed=array_map('trim',$mage_category);
    
$productData = array(
    'name' => $data['name'],
    'categories' => $mage_category_trimmed,
    'description' => $data['description'],
    'short_description' => $data['short_description'],
    'website_ids' => array('base'), // Id or code of website
    'status' => 1, // 1 = Enabled, 2 = Disabled
    'visibility' => 4, // 1 = Not visible, 2 = Catalog, 3 = Search, 4 = Catalog/Search
    'tax_class_id' => 0, // No Tax
    'weight' => $weight,
    'stock_data' => array(
        'qty' => 1, 
        'is_in_stock' => 1,
        'manage_stock' => 1
    ),
    'price' => $price,
    'special_price' => $special_price,
    'additional_attributes' => array(
        'single_data' => array(
            /*array(
                'key'   => 'type',
                'value' => $data['type'], 
            ),*/
            array(
                'key'   => 'gender',
                'value' => $data['gender'], 
            ),
            array(
                'key'   => 'stone_shape',
                'value' => $data['stone_shape'], 
            ),
            array(
                'key'   => 'stone_type',
                'value' => $data['stone_type'], 
            ),
            array(
                'key'   => 'stone_size',
                'value' => $data['stone_size'], 
            ),
            array(
                'key'   => 'metal',
                'value' => $data['metal'], 
            ),
            array(
                'key'   => 'width',
                'value' => $data['width'], 
            ),
            array(
                'key'   => 'jewelry_type',
                'value' => $data['jewelry_type'], 
            ),
            array(
                'key'   => 'era',
                'value' => $data['era'], 
            ),
            array(
                'key'   => 'style',
                'value' => $data['style'], 
            ),
        ),
    ),
);

$exists = NULL;
try {
    //Check if Product exists already
    $proxy->catalogProductInfo($sessionId, $data['sku']);
    
    //Then update the product details
    $exists = $proxy->catalogProductUpdate($sessionId, $data['sku'], $productData);
    
    // Commented for now, as we are processing the images from ETSY 
    //Remove the existing images
    $images = $proxy->catalogProductAttributeMediaList($sessionId, $data['sku']);
    if($images) {
        foreach($images as $image) {
            $proxy->catalogProductAttributeMediaRemove($sessionId, $data['sku'], $image->file);
        }
    }
    
    //Reupload the new images
    $loop = 1;
    for($i = 1; $i <= 5; $i++) {
        $filename = $data['image_'.$i];
        $imageurl = 'images/'.$filename;
        
        //Image Resizing
        if(filesize($imageurl) > '400000') {
            $_image->load($imageurl);
            $_image->resizeToWidth(800);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $name = $name.'_resized';
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $newurl = 'images/'.$name.'.'.$ext;
            $_image->save($newurl);
        }
        else {
            $newurl = $imageurl;
        }
        
        if (getimagesize($newurl) !== false) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if($ext) $ext = 'jpeg';
            
            $newImage = array(
                'file' => array(
                    'name' => $data['name'],
                    'content' => base64_encode(file_get_contents($newurl)),
                    'mime'    => 'image/'.$ext
                ),
                'label'    => $data['name'],
                'position' => $i,
                'exclude'  => 0
            );
            
            if($loop == 1) $newImage['types'] = array('image','small_image','thumbnail');
            $loop++;
            
            $imageFilename = $proxy->catalogProductAttributeMediaCreate($sessionId, $data['sku'], $newImage);
        }
        
        //Remove the files from directory
        /*if (file_exists($imageurl)) 
            unlink($imageurl);
            
        if (file_exists($newurl)) 
            unlink($newurl);*/
    }
    
    
}
catch(Exception $e) {
    //No Action   
}


if(!$exists) {
    try {
        //Create a new product        
        $productId = $proxy->catalogProductCreate($sessionId, 'simple', 'Default', $data['sku'], $productData);
      
        //Upload the images for the product
        $loop = 1;
        for($i = 1; $i <= 5; $i++) {
            
            $filename = $data['image_'.$i];
            $imageurl = 'images/'.$filename;
        
            //Image Resizing
            if(filesize($imageurl) > '400000') {
                $_image->load($imageurl);
                $_image->resizeToWidth(800);
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $name = $name.'_resized';
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $newurl = 'images/'.$name.'.'.$ext;
                $_image->save($newurl);
            }
            else {
                $newurl = $imageurl;
            }
            
            
            if (getimagesize($newurl) !== false) {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if($ext) $ext = 'jpeg';
                
                $newImage = array(
                    'file' => array(
                        'name' => $data['name'],
                        'content' => base64_encode(file_get_contents($newurl)),
                        'mime'    => 'image/'.$ext
                    ),
                    'label'    => $data['name'],
                    'position' => $i,
                    'exclude'  => 0
                );
                if($loop == 1) $newImage['types'] = array('image','small_image','thumbnail');
                $loop = 2;
                
                $imageFilename = $proxy->catalogProductAttributeMediaCreate($sessionId, $productId, $newImage);
            }
            
            //Remove the files from directory
            /*if (file_exists($imageurl)) 
                unlink($imageurl);
                
            if (file_exists($newurl)) 
                unlink($newurl);*/
        }
        
    }
    catch(Exception $e) {
        $response = array('status' => '0',
                      'message' => 'Error in adding the products in Online Store\n'.$e,
                      'data'    => $productData
                    );
    
        ob_clean();
        echo json_encode($response);
        die();
    }
}
    
    


