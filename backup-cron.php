<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 28-8-2015 IST
 */
/*
 * Script should run only in console. Can also be set as cron.
 * */
set_time_limit(0);
ini_set('max_execution_time', 0);
function make_array($argv,$argc){
    $ini = 1;
    $final_args = array();
    while($ini<$argc){
        $spli = explode(':', $argv[$ini]);
        $final_args[$spli['0']] = $spli['1'];
        $ini++;
    }
    return $final_args;
}
if(( php_sapi_name() != 'cli' )){
    echo 'Script can be executed only from console.'."\n"; exit;
}

$armts = make_array($argv, $argc);//Making array of passed parameters.
$isStartBackupProcess = (!empty($armts['start']))?$armts['start']:false;
if($isStartBackupProcess != true){
    //Checking for starting a backup process.
    echo 'Required valid parameter to start backup process. Please check the documentation.'."\n";exit;
}

if(empty($armts['config'])){
    echo 'No parameter received for config. '."\n"; exit;
}

include_once 'base_fns.php';
$arrBaseConfig = readConfig($armts['config']);

include_once 'VjFunctions.php';
include_once 'VjBackup.php';
$isBackupRunningAlready = isBackupRunningAlready();
if($isBackupRunningAlready){
    echoConsole("Backup process is running already. SO, please wait. If it really odd. Inform to technical team.", true);
}
else{
    lockBackupProcess();// Starting backup process.
}

register_shutdown_function('removeBackupProcessLock');

$databaseBackupCmd = '';
if($isDoDatabaseBackup == true){
    $dbconn = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME, DATABASE_PORT);
    if ($dbconn->connect_error) {
        die('Connect Error (' . $dbconn->connect_errno . ') '
            . $dbconn->connect_error);
    }
    else{
        echo "MYSQL Database credentials successful!. \n";
    }
}

$arrConfig = array(
    'aws_key'=>AWS_KEY,
    'aws_secret'=>AWS_SECRET,
);
$VjAmazonS3 = new VjAmazonS3($arrConfig);// Setting Amazon AWS config.
$VjAmazonS3->setBucket(BUCKET_NAME);// Validate bucket name.


$filesToBeBackup = ABS_PATH_OF_FILES_TO_BE_BACKUP;
$arrBackupConfig = array(
    'backup_file'=>$backupFile,
    'files_to_make_backup'=>$filesToBeBackup,
    'exclude_file_paths_for_backup'=>$excludeFilePathsForBackup,
    'is_database_backup'=>$isDoDatabaseBackup,
    'database_host'=>DATABASE_HOST,
    'database_port'=>DATABASE_PORT,
    'database_user'=>DATABASE_USER,
    'database_password'=>DATABASE_PASSWORD,
    'database_name'=>DATABASE_NAME,
    'backup_sql_path'=>$backupSql,
    'backup_sql_zip_path'=>$backupSqlZip,
);
$VjBackup = new VjBackup($arrBackupConfig);
$backupDetails = $VjBackup->backup();
if($backupDetails['status'] == true){
    echoConsole("Backup done locally. Starting amazon s3 upload process. ");

    if(!empty($backupDetails['backup_sql_path'])){
        $fileMess = "FileName: ".$backupDetails['backup_sql_name']."\n FilePath: ".$backupDetails['backup_sql_path']."\n FileSize: ".$backupDetails['backup_sql_size']."\n";
        echoConsole($fileMess);
        $VjAmazonS3->upload($backupDetails['backup_sql_path'], AMAZON_BACKUP_PATH.$backupDetails['backup_sql_name'], true);// Uploading database backup to amazon.
    }

    $fileMess = "FileName: ".$backupDetails['backup_file_name']."\n FilePath: ".$backupDetails['backup_file_path']."\n FileSize: ".$backupDetails['backup_file_size']."\n";
    echoConsole($fileMess);

    $VjAmazonS3->upload($backupDetails['backup_file_path'], AMAZON_BACKUP_PATH.$backupDetails['backup_file_name'], true);// Uploading to amazon.

    echoConsole("Process completed!!", true);
}
else{
    echoConsole('Unable to do local backup.', true);
}

?>