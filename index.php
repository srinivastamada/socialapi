<?php
/* ### Srinivas Tamada ### */
/* ### https://www.9lessons.info ### */
require 'config.php';
require 'Slim/Slim.php';

\Slim\Slim::registerAutoloader();
$app = new \Slim\Slim();

$app->post('/signup','signup'); /* User Signup  */

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
?>
