<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 28-8-2015 IST
 */

require 'config.php';
require 'AmazonWebService/aws-autoloader.php';
require 'VjAmazonS3.php';

$logFileName = dirname(__FILE__).DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.date('Y-m-d_H-i-s').".log";

function echoConsole($mess = '', $isExit = false){
    $val = '';
    if(!empty($mess)) {
        $val = $mess . "\n";
    }
    backupLog($mess);

    echo $val;
    if($isExit){
        exit;
    }
}

function lockBackupProcess(){
    //backupProcess.lock
    fopen("backupProcess.lock", "w") or die("Unable to create lock file \"backupProcess.lock\" !");
    if(file_exists('backupProcess.lock')){
        echoConsole("Created backup lock file.");
    }
    else{
        echoConsole("Unable to create lock file.", true);
    }
}

function isBackupRunningAlready(){
    $alreadyStarted = true;
    if(!file_exists('backupProcess.lock')){
        $alreadyStarted = false;
    }

    return $alreadyStarted;
}

function removeBackupProcessLock(){
    if(file_exists('backupProcess.lock')){
        unlink('backupProcess.lock');
    }
}

function lockRestoreProcess(){
    //restoreProcess.lock
    fopen("restoreProcess.lock", "w") or die("Unable to create lock file \"restoreProcess.lock\" !");
    if(file_exists('restoreProcess.lock')){
        echoConsole("Created Restore lock file.");
    }
    else{
        echoConsole("Unable to create lock file.", true);
    }
}

function isRestoreRunningAlready(){
    $alreadyStarted = true;
    if(!file_exists('restoreProcess.lock')){
        $alreadyStarted = false;
    }

    return $alreadyStarted;
}

function removeRestoreProcessLock(){
    if(file_exists('restoreProcess.lock')){
        unlink('restoreProcess.lock');
    }
}

function backupLog($mess=""){
    global $logFileName;
    if(!empty($mess)){
        $logFile = fopen($logFileName, "a+") or die("Unable to create log file.");

        if(file_exists($logFileName)){
            $lMess = date('Y-m-d H:i:s')."=> Mess: ".$mess." \n";
            fwrite($logFile, $lMess);
        }

        fclose($logFile);
    }
}

function getConsoleInput($mess = ""){
    $value = "";
    if(!empty($mess)){
        echo $mess;
        backupLog($mess);
        $inputValue = fopen ("php://stdin","r");
        $value = trim(fgets($inputValue));
        if($value=='' || $value==' '){
            $value = getConsoleInput($mess);
        }
        backupLog($value);
        echo "\n";
    }

    return trim($value);
}

function getSorted($items = array(), $sortDirection='desc'){
    $fiItems = $items;

    if(!empty($items)){
        $sortKey = 'timestamp';
        $sortArr = array();
        foreach($items as $key => $item){
            $sortArr[$key] = $item[$sortKey];
        }

        $srtDirection = SORT_ASC;
        if($sortDirection == 'desc'){
            $srtDirection = SORT_DESC;
        }
        array_multisort($sortArr, $srtDirection, $fiItems);
    }

    return $fiItems;
}

function showArrayOptionsInConsole($arr = array(), $nos=0){
    if(!empty($arr)){
        $ini = 0;
        foreach($arr as $ar){
            $mess = $ini.". Backup taken on ".$ar['date_time']." of size ".$ar['readable_size'];
            echoConsole($mess);
            $ini++;
            if($nos==$ini){
                break;
            }
        }
    }
}


?>