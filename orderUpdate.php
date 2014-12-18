<?php
$mageFilename = '../app/Mage.php';
require_once $mageFilename;
umask(0);
Mage::app('admin');
Mage::register('isSecureArea', 1);
Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
 
set_time_limit(0);
ini_set('memory_limit','1024M');

function _getConnection($type = 'core_read'){
    return Mage::getSingleton('core/resource')->getConnection($type);
}
 
function _getTableName($tableName){
    return Mage::getSingleton('core/resource')->getTableName($tableName);
}

//Get the Order from ETSY and update the SOLD product in Magento and also send it to BusinessMind POS
//$access_token = 'a82b67e6f28cab4b04f132d2299f38';
//$access_token_secret = '82d7b5ec2f';
$access_token = '71f5ac4b28bf6bcf861c2f2304c7d6';
$access_token_secret = 'e1b0206e8e';

//$oauth = new OAuth('4x0rfbuiox1bi1mswjv63jaa', 'gr02w4cimg',
$oauth = new OAuth('8na3d500djz7qs9q3lvdqa4o', 'jmfzsz1yoj',
                   OAUTH_SIG_METHOD_HMACSHA1, OAUTH_AUTH_TYPE_URI);
$oauth->setToken($access_token, $access_token_secret);
$oauth->disableSSLChecks();
try {
    $connection     = _getConnection('core_read');
    
    $param = array('min_created' => mktime(date('H'), date('i')-5, 0, date('m'), date('d'), date('Y')),
                   'max_created' => mktime(date('H'), date('i'), 0, date('m'), date('d'), date('Y'))
                  );
    $oauth->fetch("https://openapi.etsy.com/v2/shops/7358189/receipts", $param, OAUTH_HTTP_METHOD_GET);
    $json = $oauth->getLastResponse();
    $results = json_decode($json, true);
        
    $loop = 0;
    foreach($results['results'] as $receipt) {
        $customer_name = $receipt['name'];
        $ord_data = $oauth->fetch("https://openapi.etsy.com/v2/receipts/".$receipt['receipt_id']."/transactions", NULL, OAUTH_HTTP_METHOD_GET);
        $ord_json = $oauth->getLastResponse();
        $orders = json_decode($ord_json, true);
        $ordersku = array();
        $itemcount = 0;
        foreach($orders['results'] as $order) {
            $sql = 'SELECT sku FROM '. _getTableName('etsy_listings').' WHERE listing_id="'.$order['listing_id'].'"';
            $sku = $connection->fetchOne($sql); 
            if($sku) {
                if(strpos($sku, '-') !== false) {
                    $getsku = explode('-', $sku);
                    $newsku = $getsku[0];
                }
                else
                    $newsku = $sku;
                    
                $skus[$loop] = $newsku;
                $loop++;
                $ordersku[$itemcount] = $newsku;
                $itemcount++;
            }
        }
        
        //Authenticate the access
        $secret			        = "3b0fca34-bded-4588-910c-6d6a85b59a42";
        $plaintext 	            = 'E_'.$receipt['receipt_id'] . $customer_name . 'ETSY' . $secret;
        $encrypted_plaintext 	= strtoupper( sha1( $plaintext ) );
        $hash 			        = $encrypted_plaintext;
        
        //Data to BusinessMind
        $data = array('id'              => 'E_'.$receipt['receipt_id'],
                      'customer_name'   => $customer_name,
                      'web_store'       => 'ETSY',
                      'hash'            => $hash,
                      'item_skus'       => $ordersku);  
        
        //Send the Orders to BusinessMind POS
        $url = 'http://marketsquare.ext.bmjapp.com/api/v1/order';
        $content = json_encode($data);
        
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json")); //JSON Request
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //SSL Verification not required in DEV
        curl_exec($curl);
    }
    if($skus) {
        //Update the stock in Magento
        $newQty = '0';
        $msg    = '';
        foreach($skus as $sku) {
            $sql            = "SELECT entity_id FROM " . _getTableName('catalog_product_entity') . " WHERE sku LIKE ?";
            $productId      = $connection->fetchOne($sql, array($sku.'%'));
                 
            $sql            = "UPDATE " . _getTableName('cataloginventory_stock_item') . " csi,
                               " . _getTableName('cataloginventory_stock_status') . " css
                               SET
                               csi.qty = ?,
                               csi.is_in_stock = ?,
                               css.qty = ?,
                               css.stock_status = ?
                               WHERE
                               csi.product_id = ?
                               AND csi.product_id = css.product_id";
            $isInStock      = $newQty > 0 ? 1 : 0;
            $stockStatus    = $newQty > 0 ? 1 : 0;
            $connection->query($sql, array($newQty, $isInStock, $newQty, $stockStatus, $productId));
            
            $msg .= $sku."\n";
        }
        
        $to      = 'mak@vtldesign.com,makkalanban@gmail.com,kelly.a.ohalloran@gmail.com,kelly@vtldesign.com';
        $subject = 'Order Update from ETSY @ '.date('Y-m-d H:i:s');
        $message = "Item with SKU's #\n";
        $message .= $msg;
        $message .= "\nwas purchased from Etsy";
        
        $headers = 'From: info@madebyvital.com' . "\r\n" .
            'X-Mailer: PHP/' . phpversion();
        
        mail($to, $subject, $message, $headers);
    }
} catch (OAuthException $e) {
    echo '<pre>';
    print_r($e);
    print_r($oauth->getLastResponse(), true);
    print_r($oauth->getLastResponseInfo(), true);
    echo '</pre>';
    exit;
}