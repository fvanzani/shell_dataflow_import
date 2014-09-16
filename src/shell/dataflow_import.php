<?php
/*
 * Shell script for launching an advanced dataflow profile
 * Taken from Erwin Otten: http://h-o.nl
 */


/**
 * Path to the root of your magento installation

 */

$root = '../';


/**
 * Url to your magento installation.

 */

$url = '';


/**
 * relative path from the magento root to the login file.
 */

$login = 'shell/dataflow_login.php';


/**
 * name of the logfile, will be places in magentoroot/var/log/

 */

$logFileName = 'dataflow_import.log';


/**
 * how many products will be parsed at each post. Usually 10-50.

 */

$atOnce = 15;

include_once "dataflow_config.php";


/**
 * DO NOT EDIT BELOW THIS LINE

 */

function convert($size)

{

    $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');

    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];

}


set_time_limit(0);

ini_set('memory_limit', '512M');


$profileId = $argv[1];

if (!isset($profileId)) {

    exit ("\nPlease specify a profile id. You can find it in the admin panel->Import/Export->Profiles.\nUsage: \n\t\t php -f $argv[0] PROFILE_ID\n\t example: php -f $argv[0] 7\n");

}

$recordCount = 0;


//getting Magento

require_once $root . 'app/Mage.php';

ob_implicit_flush();

Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);


//starting the import

Mage::log("\n\n", null, $logFileName);

Mage::log(convert(memory_get_usage()) . " - " . "STARTING IMPORT", null, $logFileName);


$profile = Mage::getModel('dataflow/profile');

$userModel = Mage::getModel('admin/user');

$userModel->setUserId(0);

Mage::getSingleton('admin/session')->setUser($userModel);

if ($profileId) {
    Mage::log(convert(memory_get_usage()) . " - " . "Importing profile id: $profileId", null, $logFileName);
    $profile->load($profileId);

    if (!$profile->getId()) {

        Mage::getSingleton('adminhtml/session')->addError('ERROR: Could not load profile');

    }

}


/**
 * get het login information.

 */

exec("php -f {$root}{$login}", $result);


$loginInformation = json_decode($result[0]);


$sessionId = $loginInformation->sessionId;

$formKey = $loginInformation->formKey;


//clean dataflow_batch_import table so it doesn't get amazingly big.

$db = Mage::getSingleton('core/resource')->getConnection('core_write');

$db->query("TRUNCATE TABLE `dataflow_batch_import`");

Mage::log(convert(memory_get_usage()) . " - " . "Table dataflow_batch_import cleaned", null, $logFileName);


//load profile

if ($profileId) {

    $profile->load($profileId);

    if (!$profile->getId()) {

        Mage::getSingleton('adminhtml/session')->addError('ERROR: Could not load profile');

    }

}

Mage::register('current_convert_profile', $profile);


//run the profile

Mage::log(convert(memory_get_usage()) . " - " . "Preparing profile...", null, $logFileName);



$profile->run();



Mage::log(convert(memory_get_usage()) . " - " . "...Done", null, $logFileName);


//get to work

$batchModel = Mage::getSingleton('dataflow/batch');

if ($batchModel->getId()) {

#echo "getId ok\n";

    if ($batchModel->getAdapter()) {

#echo "getAdapter ok\n";

        $batchId = $batchModel->getId();

        Mage::log(convert(memory_get_usage()) . " - " . "Loaded batch id $batchId", null, $logFileName);


        $batchImportModel = $batchModel->getBatchImportModel();

        $importIds = $batchImportModel->getIdCollection();

        $batchModel = Mage::getModel('dataflow/batch')->load($batchId);

        $adapter = Mage::getModel($batchModel->getAdapter());

        $postdata = array();

        $postnum = 0;

        $totalproducts = count($importIds);


        Mage::log(convert(memory_get_usage()) . " - 0/{$totalproducts}", null, $logFileName);


        foreach ($importIds as $importId) {

#echo "importing $importId\n";

            $recordCount++;

            $postdata[] = "rows[]=$importId";


//echo "$importId ";


            if ($recordCount % $atOnce == 0 || $recordCount == $totalproducts) {

                $postnum++;

                $postdata[] = "batch_id=$batchId";

                $postdata[] = "form_key=$formKey";

                $postdatastring = implode('&', $postdata);

                $postdata = array();


//print_r($postdatastring);

//Mage::log(convert(memory_get_usage()) . " - Start cURL request #$postnum", null, $logFileName);


                $ch = curl_init();


                curl_setopt($ch, CURLOPT_URL, $url . "index.php/admin/system_convert_profile/batchRun/?isAjax=true");

                curl_setopt($ch, CURLOPT_TIMEOUT, 100);

                curl_setopt($ch, CURLOPT_COOKIE, "adminhtml=$sessionId");

                curl_setopt($ch, CURLOPT_POST, 1);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $postdatastring);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);


                $buffer = curl_exec($ch);


                if (empty($buffer)) {

                    Mage::log(convert(memory_get_usage()) . " - {$recordCount}/{$totalproducts} - Response is empty - ERROR" . curl_error($ch), null, $logFileName);

                } else {

                    $result = json_decode($buffer);


                    Mage::log(convert(memory_get_usage()) . " - {$recordCount}/{$totalproducts} [$buffer]", null, $logFileName);

                    if (count($result->errors)) {

                        foreach ($result->errors as $error) {

                            Mage::log(convert(memory_get_usage()) . " - ERROR: $error", null, $logFileName);

                        }

                    }

                }


                curl_close($ch);

            }


        }

        foreach ($profile->getExceptions() as $e) {

            Mage::log(convert(memory_get_usage()) . " - " . $e->getMessage(), null, $logFileName);

        }

    }

}

Mage::log(convert(memory_get_usage()) . " - " . "Completed!", null, $logFileName);
?>