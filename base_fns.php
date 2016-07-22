<?php

function readConfig($file=""){
    $arrConfig = array();
    if(!empty($file)){
        $fl = "config".DIRECTORY_SEPARATOR.$file.".ini";
        if(!empty($fl) && file_exists($fl)) {
            $arrConfig = parse_ini_file("config" . DIRECTORY_SEPARATOR . $file . ".ini", true);
        }
        else{
            echo "Unable to load config ".$file."\n"; exit;
        }
    }

    ifEmptyExit($arrConfig['BACKUP_CONFIG']['ABS_PATH_OF_FILES_TO_BE_BACKUP'], 'ABS_PATH_OF_FILES_TO_BE_BACKUP');
    ifEmptyExit($arrConfig['BACKUP_CONFIG']['BACKUP_FILE_NAME'], 'BACKUP_FILE_NAME');
    if(!empty($arrConfig['BACKUP_CONFIG']['IS_DO_DATABASE_BACKUP'])) {
        ifEmptyExit($arrConfig['BACKUP_CONFIG']['DATABASE_HOST'], 'BACKUP_CONFIG-> DATABASE_HOST');
        ifEmptyExit($arrConfig['BACKUP_CONFIG']['DATABASE_PORT'], 'BACKUP_CONFIG-> DATABASE_PORT');
        ifEmptyExit($arrConfig['BACKUP_CONFIG']['DATABASE_USER'], 'BACKUP_CONFIG-> DATABASE_USER');
        ifEmptyExit($arrConfig['BACKUP_CONFIG']['DATABASE_PASSWORD'], 'BACKUP_CONFIG-> DATABASE_PASSWORD');
        ifEmptyExit($arrConfig['BACKUP_CONFIG']['DATABASE_NAME'], 'BACKUP_CONFIG-> DATABASE_NAME');
    }

    ifEmptyExit($arrConfig['AMAZON_CONFIG']['BUCKET_NAME'], 'AMAZON_CONFIG-> BUCKET_NAME');
    ifEmptyExit($arrConfig['AMAZON_CONFIG']['AWS_KEY'], 'AMAZON_CONFIG-> AWS_KEY');
    ifEmptyExit($arrConfig['AMAZON_CONFIG']['AWS_SECRET'], 'AMAZON_CONFIG-> AWS_SECRET');

    return $arrConfig;
}

function ifEmptyExit($val="", $for=""){
    if(empty($val)){
        echo "Required config parameters empty for \"".$for."\" ! \n";
        exit;
    }
}

?>