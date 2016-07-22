<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 28-8-2015 IST
 */

//BACKUP CONFIG
$ABS_PATH_OF_FILES_TO_BE_BACKUP = $arrBaseConfig['BACKUP_CONFIG']['ABS_PATH_OF_FILES_TO_BE_BACKUP'];
$BACKUP_FILE_NAME = $arrBaseConfig['BACKUP_CONFIG']['BACKUP_FILE_NAME'];//File name length should be 10 or lesser.

//AMAZON CONFIG
$BUCKET_NAME = $arrBaseConfig['AMAZON_CONFIG']['BUCKET_NAME'];//Amazon bucket name of where backups should be stored.
$AWS_KEY = $arrBaseConfig['AMAZON_CONFIG']['AWS_KEY'];//Amazon key for access.
$AWS_SECRET = $arrBaseConfig['AMAZON_CONFIG']['AWS_SECRET'];//Amazon Secret key for auth.
//$AMAZON_BACKUP_PATH='';
$AMAZON_BACKUP_PATH = $arrBaseConfig['AMAZON_CONFIG']['AMAZON_BACKUP_PATH'];// If it is root folder, then leave empty. Else Eg. newfolder/ (With trailing slash). NOTE! don't have spaces in folder name.

//DATABASE CONFIG
$isDoDatabaseBackup = true;
$DATABASE_NAME = $arrBaseConfig['BACKUP_CONFIG']['DATABASE_NAME'];
$DATABASE_USER = $arrBaseConfig['BACKUP_CONFIG']['DATABASE_USER'];
$DATABASE_PASSWORD = $arrBaseConfig['BACKUP_CONFIG']['DATABASE_PASSWORD'];
$DATABASE_HOST = $arrBaseConfig['BACKUP_CONFIG']['DATABASE_HOST'];
$DATABASE_PORT = $arrBaseConfig['BACKUP_CONFIG']['DATABASE_PORT'];

//Restore Configuration
$arrRestorationConfig = array(
    'path_to_restoration'=>$arrBaseConfig['RESTORATION_CONFIG']['FILES_ABS_PATH'],
    'host'=>$arrBaseConfig['RESTORATION_CONFIG']['DATABASE_HOST'],
    'port'=>$arrBaseConfig['RESTORATION_CONFIG']['DATABASE_PORT'],
    'username'=>$arrBaseConfig['RESTORATION_CONFIG']['DATABASE_USER'],
    'password'=>$arrBaseConfig['RESTORATION_CONFIG']['DATABASE_PASSWORD'],
    'database'=>$arrBaseConfig['RESTORATION_CONFIG']['DATABASE_NAME'],
);

### NOTHING BELOW IS EDITABLE ###

$backupsPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'backups';
$restoresPath = dirname(__FILE__).DIRECTORY_SEPARATOR.'restores';
if(!file_exists($backupsPath)){
    mkdir($backupsPath);
}

if(!file_exists($restoresPath)){
    mkdir($restoresPath);
}

$autoBackupRootFolder = dirname(__FILE__);

if(checkForSameFolder($ABS_PATH_OF_FILES_TO_BE_BACKUP, $autoBackupRootFolder)){
    /*
     * Auto backup source code folder can't be inside file backup folder.
     * */
    echo "Error!. Unable to do backup operation from same folder. Change WP Support IO Backup ignition files to somewhere else. \n";
    exit;
}
else{
    echo "Eligible path for file backup process. \n";
}

define('ABS_PATH_OF_FILES_TO_BE_BACKUP', $ABS_PATH_OF_FILES_TO_BE_BACKUP);

define('AWS_KEY', $AWS_KEY);
define('AWS_SECRET', $AWS_SECRET);
define('BUCKET_NAME', $BUCKET_NAME);
define('BACKUPS_ABS_PATH', $backupsPath);
define('RESTORES_ABS_PATH', $restoresPath);
define('BACKUP_FILE_NAME', $BACKUP_FILE_NAME);
define('AMAZON_BACKUP_PATH', $AMAZON_BACKUP_PATH);

define('DATABASE_HOST', $DATABASE_HOST);
define('DATABASE_PORT', $DATABASE_PORT);
define('DATABASE_USER', $DATABASE_USER);
define('DATABASE_PASSWORD', $DATABASE_PASSWORD);
define('DATABASE_NAME', $DATABASE_NAME);

$excludeFilePathsForBackup = array();//With this exclude files, directories for backup. For excluding a complete directory recursively enter with relative path only!!! Eg. wp-admin/**\\*

$tmForm = date('Y-m-d_H-i-s');
$backupFile = BACKUPS_ABS_PATH.DIRECTORY_SEPARATOR.'VJBK_IO_FILES_____'.BACKUP_FILE_NAME.'_____'.$tmForm.'_____.zip';
$backupSql = BACKUPS_ABS_PATH.DIRECTORY_SEPARATOR.'VJBK_IO_SQL_____'.BACKUP_FILE_NAME.'_____'.$tmForm.'_____.sql';
$backupSqlZip = BACKUPS_ABS_PATH.DIRECTORY_SEPARATOR.'VJBK_IO_SQL_____'.BACKUP_FILE_NAME.'_____'.$tmForm.'_____.sql.zip';

function checkForSameFolder($ABS_PATH_OF_FILES_TO_BE_BACKUP='', $autoBackupRootFolder=''){
    $samePath = true;
    $ABS_PATH_OF_FILES_TO_BE_BACKUP = str_ireplace(DIRECTORY_SEPARATOR, '/', $ABS_PATH_OF_FILES_TO_BE_BACKUP);
    $autoBackupRootFolder = str_ireplace(DIRECTORY_SEPARATOR, '/', $autoBackupRootFolder);
    if(!empty($ABS_PATH_OF_FILES_TO_BE_BACKUP) && !empty($autoBackupRootFolder)) {
        $autoBackupRootFolderTS = str_ireplace(DIRECTORY_SEPARATOR, '/', $autoBackupRootFolder.DIRECTORY_SEPARATOR);
        $fLen = (strlen(str_ireplace($ABS_PATH_OF_FILES_TO_BE_BACKUP, '', $autoBackupRootFolder)));
        $fLenTS = (strlen(str_ireplace($ABS_PATH_OF_FILES_TO_BE_BACKUP, '', $autoBackupRootFolderTS)));
        if (($fLen >= 0 && $fLen != strlen($autoBackupRootFolder)) || ($fLenTS >= 0 && $fLenTS != strlen($autoBackupRootFolderTS))) {
            $samePath = true;
        } else {
            $samePath = false;
        }
    }

    return $samePath;
}

?>