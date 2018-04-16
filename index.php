<?php
/* ### Srinivas Tamada ### */
/* ### https://www.9lessons.info ### */
require 'config.php';
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->post('/signup','signup'); /* User Signup  */
$app->post('/products','products'); /* User Products  */
$app->post('/orders','orders'); /* User Orders  */
$app->post('/getProduct','getProduct'); /* get proudct  */
$app->post('/createOrder','createOrder'); /* Create Order  */
$app->run();

/************************* USER Social LOGIN & Registration *************************************/

function signup() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $email=$data->email;
    $name=$data->name;
    
    $provider=$data->provider;
    $token=$data->token;
    $provider_pic=$data->provider_pic;
    $provider_id=$data->provider_id;
    
    try {
        
        if($_SERVER['HTTP_ORIGIN'] && $_SERVER['HTTP_ORIGIN'] == "http://www.yourwebsite.com"){
        
        $emain_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
        
        if (strlen(trim($name))>0  && strlen(trim($email))>0 && $emain_check>0 )
        {
            $db = getDB();
            $userData = '';
            $sql = "SELECT uid FROM social_users WHERE  email=:email";
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email,PDO::PARAM_STR);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
            $created=time();
            if($mainCount==0)
            {
                
                /*Inserting user values*/
                $sql1="INSERT INTO social_users(name,email,provider, provider_id, token, provider_pic)VALUES(:name,:email,:provider, :provider_id, :token, :provider_pic)";
                $stmt1 = $db->prepare($sql1);
                $stmt1->bindParam("name", $name,PDO::PARAM_STR);
                $stmt1->bindParam("provider_id", $provider_id,PDO::PARAM_STR);
                $stmt1->bindParam("provider", $provider,PDO::PARAM_STR);
                $stmt1->bindParam("email", $email,PDO::PARAM_STR);
                $stmt1->bindParam("token", $token,PDO::PARAM_STR);
                $stmt1->bindParam("provider_pic", $provider_pic,PDO::PARAM_STR);
                $stmt1->execute();
                
                $userData=internalUserDetails($email);
                
            }
            else{
                $userData=internalUserDetails($email);
            }
            
            $db = null;
            
            
            if($userData){
                $userData = json_encode($userData);
                echo '{"userData": ' .$userData . '}';
            } else {
                echo '{"error":{"text":"Enter valid data"}}';
            }
            
            
        }
        else{
            echo '{"error":{"text":"Enter valid data"}}';
        }
    }
    else{
        echo '{"error":{"text":"No access"}}';
    }
}
catch(PDOException $e) {
    echo '{"error":{"text":'. $e->getMessage() .'}}';
}
}


/* ### internal Username Details ### */
function internalUserDetails($input) {
    
    try {
        $db = getDB();
        $sql = "SELECT uid, name, email FROM social_users WHERE email=:input";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("input", $input,PDO::PARAM_STR);
        $stmt->execute();
        $usernameDetails = $stmt->fetch(PDO::FETCH_OBJ);
        $usernameDetails->token = apiToken($usernameDetails->uid);
        $db = null;
        return $usernameDetails;
        
    } catch(PDOException $e) {
        echo '{"error":{"text":'. $e->getMessage() .'}}';
    }
    
}

function products() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $uid=$data->uid;
    $token=$data->token;
    $system_token = apiToken($uid);
    
    if($token ==  $system_token){
        $db = getDB();
        $sql = "SELECT * FROM products";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchALL(PDO::FETCH_OBJ);
        
        if($products){
            $products = json_encode($products);
            echo '{"products": ' .$products . '}';
        } else {
            echo '{"error":{"text":"No data available"}}';
        }
    }
    else{
        echo '{"error":{"text":"No access"}}';
    }
}

function orders() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $uid=$data->uid;
    $token=$data->token;
    
    $system_token = apiToken($uid);
    if($token ==  $system_token){
        $db = getDB();
        $sql = "SELECT * FROM orders O, products P WHERE O.pid_fk = P.pid AND uid_fk=:uid order by O.oid DESC; ";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("uid", $uid,PDO::PARAM_INT);
        $stmt->execute();
        $orders = $stmt->fetchALL(PDO::FETCH_OBJ);
        
        if($orders){
            $orders = json_encode($orders);
            echo '{"orders": ' .$orders . '}';
        } else {
            echo '{"error":{"text":"No data available"}}';
        }
    }
    else{
        echo '{"error":{"text":"No access"}}';
    }
}

function getProduct() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $uid=$data->uid;
    $token=$data->token;
    $pid=$data->pid;
    
    $system_token = apiToken($uid);
    if($token ==  $system_token){
        $db = getDB();
        $sql = "SELECT * FROM products WHERE pid=:pid";
        $stmt = $db->prepare($sql);
        $stmt->bindParam("pid", $pid,PDO::PARAM_STR);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_OBJ);
        
        if($product){
            $product = json_encode($product);
            echo '{"product": ' .$product . '}';
        } else {
            echo '{"error":{"text":"No data available"}}';
        }
    }
    else{
        echo '{"error":{"text":"No access"}}';
    }
}


function paymentCheck($paymentID)
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM orders WHERE paymentID=:paymentID");
    $stmt->bindParam("paymentID", $paymentID, PDO::PARAM_STR) ;
    $stmt->execute();
    $count = $stmt->rowcount();
    $db=null;
    return $count;
    
}


function updateOrder($pid, $payerID, $paymentID, $token,$uid)
{
    
    if(paymentCheck($paymentID) < 1 && $uid > 0){
        $db = getDB();
        
        $stmt = $db->prepare("INSERT INTO orders(uid_fk, pid_fk, payerID, paymentID, token, created ) VALUES (:uid, :pid,:payerID, :paymentID, :token, :created)");
        $stmt->bindParam("paymentID", $paymentID, PDO::PARAM_STR) ;
        $stmt->bindParam("payerID", $payerID, PDO::PARAM_STR) ;
        $stmt->bindParam("token", $token, PDO::PARAM_STR) ;
        $stmt->bindParam("pid", $pid, PDO::PARAM_INT) ;
        $stmt->bindParam("uid", $uid, PDO::PARAM_INT) ;
        $created = time();
        $stmt->bindParam("created", $created, PDO::PARAM_INT) ;
        
        $stmt->execute();
        $db=null;
        return true;
    }
    else{
        return false;
    }
    
}

function paypalCheck($paymentID, $pid, $payerID, $paymentToken, $uid){
    
    $ch = curl_init();
    $clientId = PayPal_CLIENT_ID;
    $secret = PayPal_SECRET;
    curl_setopt($ch, CURLOPT_URL, PayPal_BASE_URL.'oauth2/token');
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    $result = curl_exec($ch);
    $accessToken = null;
    
    
    if (empty($result)){
        
        return false;
    }
    
    else {
        
        $json = json_decode($result);
        $accessToken = $json->access_token;
        $curl = curl_init(PayPal_BASE_URL.'payments/payment/' . $paymentID);
        curl_setopt($curl, CURLOPT_POST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $accessToken,
        'Accept: application/json',
        'Content-Type: application/xml'
        ));
        $response = curl_exec($curl);
        $result = json_decode($response);
        
        $state = $result->state;
        $total = $result->transactions[0]->amount->total;
        $currency = $result->transactions[0]->amount->currency;
        $subtotal = $result->transactions[0]->amount->details->subtotal;
        $recipient_name = $result->transactions[0]->item_list->shipping_address->recipient_name;
        curl_close($ch);
        curl_close($curl);
        
        $product = getProductData($pid);
        if($state == 'approved' && $currency == $product->currency && $product->price ==  $subtotal){
            
            updateOrder($pid, $payerID, $paymentID, $paymentToken, $uid);
            return true;
            
        }
        else{
            
            return false;
        }
    }
    
}


function createOrder() {
    $request = \Slim\Slim::getInstance()->request();
    $data = json_decode($request->getBody());
    $uid=$data->uid;
    $token=$data->token;
    $pid=$data->pid;
    $payerID=$data->payerID;
    $paymentToken=$data->paymentToken;
    $paymentID=$data->paymentID;
    
    
    $system_token = apiToken($uid);
    if($token ==  $system_token){
        
        if(paypalCheck($paymentID, $pid, $payerID, $paymentToken, $uid)){
            echo '{"status": "true" }';
        } else {
            echo '{"error":{"text":"No data available"}}';
        }
    }
    else{
        echo '{"error":{"text":"No access"}}';
    }
}
?>