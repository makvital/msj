<?php
//$Enum_type          = array("Boxes", "Chains", "Cigarette Accessories", "Colored stones", "Cufflinks/Shirtstuds", "Diamond", "Drop", "Flatware", "Gold Necklaces", "Pearls", "Pendants", "Pocket Watches", "Rings", "Stud", "Tie bars/pins", "Wrist Watches");
$Enum_gender        = array("Female", "Male", "Unisex");
$Enum_stone_shape   = array("Antique Cut", "Cushion Cut", "Emerald Cut", "Marquise Cut", "Oval Cut", "Pear Cut", "Princess Cut", "Round Brilliant");
$Enum_stone_type    = array("Agate", "Alexandrite", "Amazonite", "Amber", "Amethyst", "Andalusite", "Apatite", "Aquamarine", "Aventurine", "Azurite", "Beryl", "Bloodstone", "Cameo", "Carnelian", "Cat's Eye", "Chalcedony", "Chrome Diopside", "Chrysoberyl", "Chrysocolla", "Chrysophrase", "Citrine", "Coral", "Demantoid Garnet", "Diamond", "Diopside", "Dravite Tourmaline", "Emerald", "Garnet", "Gold", "Heliodor", "Hematite", "Iolite", "Jade", "Jasper", "Jet", "Kornerupine", "Kunzite", "Kyanite", "Labradorite", "Lapis", "Moonstone", "Morganite", "Obsidian", "Onyx", "Opal", "Pearl", "Peridot", "Petrified Wood", "Prehnite", "Pyrite", "Quartz", "Rhodochrosite", "Rhodonite", "Rose Quartz", "Rubellite", "Ruby", "Rutilated Quartz", "Rutile", "Sapphire", "Sardonyx", "Scapolite", "Serpentine", "Shell", "Silimanite", "Smoky Quartz", "Sodalite", "Spessartine", "Sphene", "Spinel", "Spinel", "Sunstone", "Synthetic Alexandrite", "Tanzanite", "Tiger's Eye", "Topaz", "Tourmaline", "Turquoise", "Unakite", "Zircon", "Zoisite");
$Enum_stone_size    = array("0-0.25 ct", "0.26-.50 ct", "0.51-0.75 ct", "0.76-1.00 ct", "1.00-2.00 ct", "2.00+ ct");
$Enum_metal         = array("Base Metal", "Gold Filled", "Green Gold", "Mixed Metals", "Palladium", "Platinum", "Rose Gold", "Sterling Silver", "White Gold", "Yellow Gold");
$Enum_width         = array("0-1.5 mm", "1.6-2.2 mm", "2.3-4.0 mm", "4.1-6 mm", "6+ mm");
$Enum_jewelry_type  = array("Rings", "Bracelets", "Earrings", "Pins/Brooches", "Necklaces/Pendants", "Men's Collection", "Accessories");
$Enum_era           = array("Georgian (1714-1837)", "Victorian (1837-1901)", "Edwardian (1901-1915)", "Art Nouveau (1890-1915)", "Art Deco (1920-1935)", "Retro (1930-1950)", "Mid-Century (1950-1970)", "Modern (1970-1995)", "Contemporary (1990-Present)", "Mixed Era");
$Enum_style         = array("Diamond Bands", "Engraved or Patterned Bands", "Eternity Bands", "Plain Bands");
$Enum_who_made      = array('i_did','collective','someone_else');
$Enum_when_made     = array('made_to_order','2010_2014','2000_2009','1995_1999','before_1995','1990_1994','1980s','1970s','1960s','1950s','1940s','1930s','1920s','1910s','1900s','1800s','1700s','before_1700');

$data = json_decode(file_get_contents('php://input'), true);

//Authenticate the access
$secret			        = "bf79ce4c-d123-41f7-8b0a-391909c83006";
$plaintext 	            = $data['sku'] . $data['name'] . $secret;
$encrypted_plaintext 	= strtoupper( sha1( $plaintext ) );
$hash 			        = $encrypted_plaintext;

$success = 0;
$message = '';
$result = '';

$price = str_replace(',','',$data['price']); 
$price = $price * 1;

$special_price = str_replace(',','',$data['special_price']); 
$special_price = $special_price * 1;

$data['etsy_category'] = trim($data['etsy_category']);

$weight = $data['weight'] * 1;
if($hash != $data['hash']) {
    $success = 0;
    $message = 'Error Code 99: Unauthorized Access';
}
else if(sizeof($data) != '29') {
    $success = 0;
    $message = 'Error Code 2: InSufficient Data';
}
else if(!$data['sku']) {
    $success = 0;
    $message = 'Error Code 4: Product SKU is mandatory field';
}
else if(preg_match('/[^a-z\-0-9]/i', $data['sku'])) {
    $success = 0;
    $message = 'Error Code 3: SKU should contains only Alphanumeric separated by hyphens';
}
else if(!$data['name']) {
    $success = 0;
    $message = 'Error Code 4: Product Title is mandatory field';
}
else if(!$data['mage_category']) {
    $success = 0;
    $message = 'Error Code 4: Category Id is mandatory field';
}
else if(!$data['etsy_category']) {
    $success = 0;
    $message = 'Error Code 4: ETSY Category Id is mandatory field';
}
else if(!is_numeric($data['etsy_category'])) {
    $success = 0;
    $message = 'Error Code 5: ETSY Category Id must be an Integer';
}
else if(!$data['price']) {
    $success = 0;
    $message = 'Error Code 4: Product Price is mandatory field';
}
else if(!is_float($price)) {
    $success = 0;
    $message = 'Error Code 5: Product Price must be in FLOAT';
}
else if($data['special_price'] && !is_float($special_price)) {
    $success = 0;
    $message = 'Error Code 5: Product Special Price must be in FLOAT';
}
else if(!$data['weight']) {
    $success = 0;
    $message = 'Error Code 4: Weight is mandatory field';
}
else if(!is_float($weight)) {
    $success = 0;
    $message = 'Error Code 5: Weight must be in FLOAT';
}
/*else if($data['type'] && !in_array($data['type'], $Enum_type)) {
    $success = 0;
    $message = 'Error Code 6: Type value not matches';
}*/
else if($data['gender'] && !in_array($data['gender'], $Enum_gender)) {
    $success = 0;
    $message = 'Error Code 6: Gender value not matches';
}
else if($data['stone_shape'] && !in_array($data['stone_shape'], $Enum_stone_shape)) {
    $success = 0;
    $message = 'Error Code 6: Stone_shape value not matches';
}
else if($data['stone_type'] && !in_array($data['stone_type'], $Enum_stone_type)) {
    $success = 0;
    $message = 'Error Code 6: Stone_type value not matches';
}
else if($data['stone_size'] && !in_array($data['stone_size'], $Enum_stone_size)) {
    $success = 0;
    $message = 'Error Code 6: Stone_size value not matches';
}
else if($data['metal'] && !in_array($data['metal'], $Enum_metal)) {
    $success = 0;
    $message = 'Error Code 6: Metal value not matches';
}
else if($data['width'] && !in_array($data['width'], $Enum_width)) {
    $success = 0;
    $message = 'Error Code 6: Width value not matches';
}
else if($data['jewelry_type'] && !in_array($data['jewelry_type'], $Enum_jewelry_type)) {
    $success = 0;
    $message = 'Error Code 6: Jewelry_type value not matches';
}else if($data['era'] && !in_array($data['era'], $Enum_era)) {
    $success = 0;
    $message = 'Error Code 6: Era value not matches';
}
else if($data['style'] && !in_array($data['style'], $Enum_style)) {
    $success = 0;
    $message = 'Error Code 6: Style value not matches';
}
else if($data['who_made'] && !in_array($data['who_made'], $Enum_who_made)) {
    $success = 0;
    $message = 'Error Code 6: Who_made value not matches';
}
else if($data['when_made'] && !in_array($data['when_made'], $Enum_when_made)) {
    $success = 0;
    $message = 'Error Code 6: When_made value not matches';
}
else if(preg_match('/[^\p{L}\p{Nd}\p{Zs},]/u', $data['tags'])) {
    $success = 0;
    $message = 'Error Code 8: A tag is valid if it does not match the pattern: /[^\p{L}\p{Nd}\p{Zs}]/u';
}
else if(preg_match('/[^\p{L}\p{Nd}\p{Zs},]/u', $data['materials'])) {
    $success = 0;
    $message = 'Error Code 8: A material is valid if it does not match the pattern: /[^\p{L}\p{Nd}\p{Zs}]/u';
}
else if(!$data['image_1'] && !$data['image_2'] && !$data['image_3'] && !$data['image_4'] && !$data['image_5']) {
    $success = 0;
    $message = 'Error Code 7: Minimum one image is necessary for a product';
}
else if(!$data['description']) {
    $success = 0;
    $message = 'Error Code 4: Product Description is mandatory field';
}
else {
    //Add Product to Magento
    if(strpos($data['mage_category'],',')) 
        $mage_category = explode(',', $data['mage_category']);
    else
        $mage_category[0] = $data['mage_category'];
        
    //Few Adjustments
    /*if($data['stone_type'] == 'Tigers Eye')
        $data['stone_type'] = "Tiger's Eye";
        
    if($data['jewelry_type'] == 'Mens Collection')
        $data['jewelry_type'] = "Men's Collection";
    */
    
    require('addProductMage.php');
    
    //Add Product to ETSY
    require('addProductEtsy.php');
       
    
    $success = 1;
    $message = 'Product successfully added';
    $result = $data;
}

$response = array('status' => $success,
                  'message' => $message,
                  'data'    => $result
                );

$to      = 'mak.vitaldesign@gmail.com,kelly@vtldesign.com,kelly.a.ohalloran@gmail.com,jahumada@vtldesign.com,MSJMagento@gmail.com';
//$to      = 'mak@vtldesign.com';
$subject = 'New Product Entry - '.$data['sku'];
$message = $success.': ('.$data['sku'].')'.$message;
//if($result)
    $message .= implode("\n", $data);
$headers = 'From: info@madebyvital.com' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();

mail($to, $subject, $message, $headers);

ob_clean();
echo json_encode($response);
die();
?>