<?php



/**

 * Path to the root of your magento installation.

 * include traing slash.

 */


$root = '../';



/**

 * Username that has the rights to import products.

 */

$username = '';



/**

 * Password

 */

$password = '';


include_once "dataflow_config.php";




/**

 * DO NOT EDIT BELOW THIS LINE

 */

//getting Magento

require_once $root . 'app/Mage.php';

ob_implicit_flush();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);



//starting the import



Mage::getSingleton("admin/session", array("name"=>"adminhtml"));

$session = Mage::getSingleton("admin/session");



try

{

    $session->login($username, $password);

} catch(Exception $e) {

    echo 'Message: ' .$e->getMessage();

}



if(! $session->isLoggedIn())

{

    Mage::log((memory_get_usage()) . " - " . "Could not log in with '$username'", null, $logFileName);

    exit;

}


$sessionId = $session->getEncryptedSessionId();

$formKey = Mage::getSingleton('core/session')->getFormKey();



echo json_encode(array('sessionId' => $sessionId, 'formKey' => $formKey));