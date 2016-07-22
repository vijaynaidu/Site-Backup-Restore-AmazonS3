<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 30-8-2015 IST
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
$isStartRestoreProcess = (!empty($armts['start']))?$armts['start']:false;
if($isStartRestoreProcess != true){
    //Checking for starting a restoration process.
    echo 'Required valid parameter to start restore process. Please check the documentation.'."\n"; exit;
}

if(empty($armts['config'])){
    echo 'No parameter received for config. '."\n"; exit;
}
include_once 'base_fns.php';

$arrBaseConfig = readConfig($armts['config']);

include_once 'VjFunctions.php';
include_once 'VjRestore.php';

$isRestoreRunningAlready = isRestoreRunningAlready();
if($isRestoreRunningAlready){
    echoConsole("Restoration process is running already. SO, please wait. If it really odd. Inform to technical team.", true);
}
else{
    lockRestoreProcess();// Starting Restoration process.
}

register_shutdown_function('removeRestoreProcessLock');

$arrConfig = array(
    'aws_key'=>AWS_KEY,
    'aws_secret'=>AWS_SECRET,
);
$VjAmazonS3 = new VjAmazonS3($arrConfig);// Setting Amazon AWS config.
$VjAmazonS3->setBucket(BUCKET_NAME);// Validate bucket name.
echoConsole('Amazon Backup path: '.AMAZON_BACKUP_PATH);
$amazonFiles = $VjAmazonS3->getFilesList(AMAZON_BACKUP_PATH);
if(!empty($amazonFiles['all'])){
    $fileBackups = $amazonFiles['files'];
    $sqlBackups = $amazonFiles['sql'];
    $fileBackups = getSorted($fileBackups, 'desc');
    $sqlBackups = getSorted($sqlBackups, 'desc');
    $noOfRecordsToDisplay = getConsoleInput("No of backup records to display? (all|number)");
    if(!empty($noOfRecordsToDisplay) && ($noOfRecordsToDisplay=='all' || (is_int(($noOfRecordsToDisplay-0)) && ($noOfRecordsToDisplay-0)!=0))) {
        $nrc = ($noOfRecordsToDisplay=='all')?0:$noOfRecordsToDisplay;
    }
    else{
        echoConsole('Invalid input for total number of records to display: '.$noOfRecordsToDisplay, true);
    }

    if(!empty($fileBackups)){
        $isRestoreFiles = strtolower(getConsoleInput("Did you want to select files restore from backups? (Y|N)"));
        if($isRestoreFiles=='y' || $isRestoreFiles=='n'){
            if($isRestoreFiles=='y'){
                //Show available file backup options.
                showArrayOptionsInConsole($fileBackups, $nrc);
                $optionFilesToBackup = getConsoleInput("Enter selected option to restore files from archive: ");
                if(isset($fileBackups[$optionFilesToBackup])){
                    $selectedFileForBackup = $fileBackups[$optionFilesToBackup];
                }
                else{
                    echoConsole('Invalid option selected: '.$optionFilesToBackup, true);
                }
            }
        }
        else{
            echoConsole('Invalid input received: '.$isRestoreFiles, true);
        }
    }
    else if(!empty($sqlBackups)){
        $resp = strtolower(getConsoleInput("No file backups found. Did you want to proceed to database restoration process? (Y|N)"));
        if($resp!='y'){
            echoConsole('Aborted restoration process. ', true);
        }
    }
    else{
        echoConsole('No previous backup files found for bucket: '.BUCKET_NAME, true);
    }

    if(!empty($sqlBackups)){
        $isRestoreDb = strtolower(getConsoleInput("Did you want to select database restore from backups? (Y|N)"));
        if($isRestoreDb=='y' || $isRestoreDb=='n'){
            if($isRestoreDb=='y'){
                //Show available database backup options.
                showArrayOptionsInConsole($sqlBackups, $nrc);
                $optionSqlToBackup = getConsoleInput("Enter selected option to restore database from archive: ");
                if(isset($sqlBackups[$optionSqlToBackup])){
                    $selectedSqlForBackup = $sqlBackups[$optionSqlToBackup];
                }
                else{
                    echoConsole('Invalid option selected: '.$optionSqlToBackup, true);
                }
            }
        }
        else{
            echoConsole('Invalid input received: '.$isRestoreDb, true);
        }
    }
    else if(!empty($selectedFileForBackup)){
        $response = strtolower(getConsoleInput("No database backups found. Did you want to restore files alone? (Y|N)"));
        if($response=='y' || $response=='n'){
            if($response=='n'){
                echoConsole('Aborted restoration process.', true);
            }
        }
        else{
            echoConsole('Invalid input received: '.$response, true);
        }
    }
    else{
        echoConsole('No backups found aborted restoration process.', true);
    }

    //START PROCESS HERE.
    if(empty($selectedFileForBackup) && empty($selectedSqlForBackup)){
        echoConsole('Aborted restoration process.', true);
    }

    $arrRestoreConfig = array();
    if(!empty($selectedFileForBackup)) {
        if (!empty($arrRestorationConfig['path_to_restoration'])) {
            $cnf = strtolower(getConsoleInput("Is it correct path you wanted to restore files? \"".$arrRestorationConfig['path_to_restoration']."\" (Y|N)"));
            if($cnf=='y'){
                //Continue with same path.
                $arrRestoreConfig['path_to_restoration'] = $arrRestorationConfig['path_to_restoration'];
            }
        }
        if(empty($arrRestoreConfig['path_to_restoration'])){
            $arrRestoreConfig['path_to_restoration'] = getConsoleInput("Enter absolute path of files to be restored: ");
        }

        if(!file_exists($arrRestoreConfig['path_to_restoration']) || !is_dir($arrRestoreConfig['path_to_restoration'])){
            echoConsole('Entered path not exist or is not a directory. ', true);
        }
    }

    if(!empty($selectedSqlForBackup)){
        $foundConfig = false;
        if(!empty($arrRestorationConfig['host']) && !empty($arrRestorationConfig['port']) && !empty($arrRestorationConfig['username']) && !empty($arrRestorationConfig['password']) && !empty($arrRestorationConfig['database'])){
            $arrCf = array();
            $arrCf[] = "Host: ".$arrRestorationConfig['host'];
            $arrCf[] = "Port: ".$arrRestorationConfig['port'];
            $arrCf[] = "Username: ".$arrRestorationConfig['username'];
            $arrCf[] = "Password: ".$arrRestorationConfig['password'];
            $arrCf[] = "Database: ".$arrRestorationConfig['database'];
            $amess = implode("\n", $arrCf);
            echoConsole($amess);
            $cnf = strtolower(getConsoleInput("Is the above details correct for restoring database? (Y|N): "));
            if($cnf=='y'){
                //Continue with same config.
                $foundConfig = true;
                $arrRestoreConfig['host'] = $arrRestorationConfig['host'];
                $arrRestoreConfig['port'] = $arrRestorationConfig['port'];
                $arrRestoreConfig['username'] = $arrRestorationConfig['username'];
                $arrRestoreConfig['password'] = $arrRestorationConfig['password'];
                $arrRestoreConfig['database'] = $arrRestorationConfig['database'];
            }
        }

        if(!$foundConfig){
            $arrRestoreConfig['host'] = getConsoleInput("Enter database hostname: ");
            $arrRestoreConfig['port'] = getConsoleInput("Enter database port: ");
            $arrRestoreConfig['username'] = getConsoleInput("Enter database username: ");
            $arrRestoreConfig['password'] = getConsoleInput("Enter database password: ");
            $arrRestoreConfig['database'] = getConsoleInput("Enter database name: ");
        }

        $dbconn = new mysqli($arrRestoreConfig['host'], $arrRestoreConfig['username'], $arrRestoreConfig['password'], $arrRestoreConfig['database'], $arrRestoreConfig['port']);
        if ($dbconn->connect_error) {
            echoConsole('Connect Error (' . $dbconn->connect_errno . ') '. $dbconn->connect_error, true);
        }
        else{
            echoConsole("MYSQL Database credentials successful!.");
        }
    }

    $VjRestore = new VjRestore($arrRestoreConfig);

    if(!empty($selectedFileForBackup)){
        //Files restoration process.
        $downloadToLocalFileArchive = RESTORES_ABS_PATH.DIRECTORY_SEPARATOR.$selectedFileForBackup['file_name'];
        if(file_exists($downloadToLocalFileArchive)){
            echoConsole("Removing existing backup file: ".$downloadToLocalFileArchive);
            unlink($downloadToLocalFileArchive);
        }

        echoConsole("Downloading from amazon for ".$selectedFileForBackup['key'].".");
        $isDownloaded = $VjAmazonS3->downloadFile($downloadToLocalFileArchive, $selectedFileForBackup['key']);
        if(file_exists($downloadToLocalFileArchive)){
            $unZipCommand = "cd ".$arrRestoreConfig['path_to_restoration']." && unzip -o ".$downloadToLocalFileArchive;
            $isExecuted = $VjRestore->executeCommand($unZipCommand);
            if($isExecuted){
                echoConsole('Executed unzip command. ');
            }
            else{
                echoConsole('Unable to execute unzip command. ', true);
            }
            if(unlink($downloadToLocalFileArchive)){
                echoConsole('Removed download files backup archive. ');
            }
            else{
                echoConsole('Unable to remove download files backup archive. ');
            }

            echoConsole('Files process completed. ');
        }
        else{
            echoConsole('Unable to download files backup archive. ', true);
        }
    }

    if(!empty($selectedSqlForBackup)){
        //Database restoration process.
        $downloadToLocalSqlArchive = RESTORES_ABS_PATH.DIRECTORY_SEPARATOR.$selectedSqlForBackup['file_name'];
        if(file_exists($downloadToLocalSqlArchive)){
            echoConsole("Removing existing backup file: ".$downloadToLocalSqlArchive);
            unlink($downloadToLocalSqlArchive);
        }
        
        echoConsole("Downloading from amazon for ".$selectedSqlForBackup['key'].".");
        $isDownloaded = $VjAmazonS3->downloadFile($downloadToLocalSqlArchive, $selectedSqlForBackup['key']);
        if(file_exists($downloadToLocalSqlArchive)){
            $unZipCommand = "cd ".RESTORES_ABS_PATH." && unzip -o ".$downloadToLocalSqlArchive;
            $isExecuted = $VjRestore->executeCommand($unZipCommand);
            if($isExecuted){
                echoConsole('Executed unzip command. ');
            }
            else{
                echoConsole('Unable to execute unzip command. ', true);
            }
            if(unlink($downloadToLocalSqlArchive)){
                echoConsole('Removed download sql archive. ');
            }
            else{
                echoConsole('Unable to remove downloaded sql archive. ');
            }

            $sqlFileForImport = RESTORES_ABS_PATH.DIRECTORY_SEPARATOR.pathinfo($downloadToLocalSqlArchive, PATHINFO_FILENAME);
            if(file_exists($sqlFileForImport)){
                $sqlImportCommand = "mysql -P ".$arrRestoreConfig['port']." -h ".$arrRestoreConfig['host']." -u ".$arrRestoreConfig['username']." -p".$arrRestoreConfig['password']." ".$arrRestoreConfig['database']." < ".$sqlFileForImport."";

                $isExecuted = $VjRestore->executeCommand($sqlImportCommand);
                if($isExecuted){
                    echoConsole('Executed mysql import command. ');
                }
                else{
                    echoConsole('Unable to execute mysql import command. ', true);
                }
                if(unlink($sqlFileForImport)){
                    echoConsole('Removed imported sql file. ');
                }
                else{
                    echoConsole('Unable to remove imported sql file. ');
                }

                echoConsole('Database process completed. ');
            }
            else{
                echoConsole("Sql file doesn't exist for importing to database. ", true);
            }
        }
        else{
            echoConsole('Unable to download sql backup archive. ', true);
        }
    }

    echoConsole('Restoration process completed. ', true);

}
else{
    echoConsole('No previous backup files found for bucket: '.BUCKET_NAME, true);
}

?>