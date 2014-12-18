<?php
mysql_connect('localhost', 'marketsq_mageapi', 'hfm)u3@sXx0;');
mysql_select_db('marketsq_store');

$access_token = '71f5ac4b28bf6bcf861c2f2304c7d6';
$access_token_secret = 'e1b0206e8e';

$oauth = new OAuth('8na3d500djz7qs9q3lvdqa4o', 'jmfzsz1yoj',
                   OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
$oauth->setToken($access_token, $access_token_secret);
$oauth->disableSSLChecks();
try {
    //Setup the Description 
    /*$description = $data['description']."\r\n\r\nTotal Weight: ".$weight."\r\n";

    if($data['type'])
        $description .= "Type: ".$data["type"]."\r\n";                    
    if($data['metal'])
        $description .= "Metal: ".$data["metal"]."\r\n";
    if($data['style'])
        $description .= "Style: ".$data["style"]."\r\n";
    if($data['stone_shape'])
        $description .= "Stone Shape: ".$data["stone_shape"]."\r\n";
    if($data['stone_type'])
        $description .= "Stone Type: ".$data["stone_type"]."\r\n";
    if($data['stone_size'])
        $description .= "Stone Size: ".$data["stone_size"]."\r\n";
    if($data['jewelry_type'])
        $description .= "Jewelry Type: ".$data["jewelry_type"]."\r\n";
    if($data['era'])
        $description .= "Time Period: ".$data["era"]."\r\n";
    if($data['width'])
        $description .= "Size of Ring: ".$data["width"].", but can be sized to fit almost any finger (please see our sizing)"."\r\n";*/
    
    $description = $data['description'];
    
    $description .= "\nSKU: ".$data['sku']."\r\n\r\n";
    $description .= "Each piece has been identified and graded by a Graduate Gemologist who has been certified by the Gemological Institute of America (GIA). We have three brick and mortar storefronts in Massachusetts and New Hampshire and have been in business for over 25 years! Please visit our Shop's About Page or our website for more information about our jewelry.For questions about diamond grading, we recommend the Gemological Institute of America (GIA) who were the founders of the 4C's.\n
    If you have any questions about this piece or if we can help you with any of our other products please feel free to contact us through Etsy, through our website at www.marketsquarejewelers.com, or by phone at (603) 343-2705. Thanks for checking out our shop!";
    
    //Check if any special price is available
    if($special_price) $etsy_price = $special_price;
    else               $etsy_price = $price;
    
    //Add the product to ETSY
    $param = array('quantity' => '1',
                   'title' => ucwords(strtolower($data['name'])).' '.$data['sku'],
                   'description' => $description,
                   'price' => $etsy_price,
                   'category_id' => $data['etsy_category'],
                   'who_made' => $data['who_made'],
                   'is_supply' => '0',
                   'when_made' => $data['when_made'],
                   'tags' => $data['tags'],
                   'shipping_template_id' => '178198155',
                   'materials' => $data['materials']
                   );
    
    $query = mysql_query('SELECT * FROM msj_etsy_listings WHERE sku LIKE "'.$data['sku'].'%"');
    if(mysql_num_rows($query)) {
        //NO ACTION for now in product update in etsy
        /*$result = mysql_fetch_assoc($query);
        $param['listing_id'] = $result['listing_id'];
        
        //Update the existing Product(Listing)
        $oauth->fetch("https://openapi.etsy.com/v2/listings/".$result['listing_id'], $param, OAUTH_HTTP_METHOD_PUT);
        
        //Overwrite the existing the images
        $url = "https://openapi.etsy.com/v2/listings/".$result['listing_id']."/images";
        for($i = 5;$i >= 1;$i--) {
            $filename = $data['image_'.$i];
            if($filename) {
                $source_file = dirname(realpath(__FILE__)) ."/images/".$filename;
                $params = array('listing_id' => $result['listing_id'],
                                'rank'       => $i,
                                '@image'     => '@'.$source_file.';type='.$mimetype,
                                'overwrite'  => '1'
                                );
                $oauth->fetch($url, $params, OAUTH_HTTP_METHOD_POST);
            }
        }*/
        
    }
    else {
        //Create new product in draft mode
        $param['state'] = 'draft';
        
        //Create new Product(Listing)
        $oauth->fetch("https://openapi.etsy.com/v2/listings", $param, OAUTH_HTTP_METHOD_POST);
        $json = $oauth->getLastResponse();
        $results = json_decode($json, true);
        
        //Get the Listing Id generated in ETSY
        $listing_id = $results['results'][0]['listing_id'];
        
        //Insert the Listing Id against SKU for future reference
        mysql_query('INSERT INTO msj_etsy_listings(listing_id,sku) VALUES("'.$listing_id.'","'.$data['sku'].'")');
        
        //Process the images and add it to the product in ETSY
        $mimetype = 'multipart/form-dataheader';
        $url = "https://openapi.etsy.com/v2/listings/".$listing_id."/images";
        for($i = 5;$i >= 1;$i--) {
            $filename = $data['image_'.$i];
            if($filename) {
                $source_file = dirname(realpath(__FILE__)) ."/images/".$filename;
                $params = array('@image' => '@'.$source_file.';type='.$mimetype);
                $oauth->fetch($url, $params, OAUTH_HTTP_METHOD_POST);
            }
        }
    }
} catch (OAuthException $e) {
    
    $response = array('status' => '0',
                  'message' => "Error in processing the ETSY listing\n\n".$oauth->getLastResponse()."\n\n",
                  'data'    => $data
                );

    ob_clean();
    
    $to      = 'mak.vitaldesign@gmail.com,kelly@vtldesign.com,kelly.a.ohalloran@gmail.com,jahumada@vtldesign.com,MSJMagento@gmail.com';
    $subject = 'ETSY Product Entry Error - '.$data['sku'];
    $message = 'Error: ('.$data['sku'].')'.$response['message'];
    $message .= implode("\n", $data);
    
    $headers = 'From: info@madebyvital.com' . "\r\n" .
        'X-Mailer: PHP/' . phpversion();
    
    mail($to, $subject, $message, $headers);
    
    echo json_encode($response);
    die();
}