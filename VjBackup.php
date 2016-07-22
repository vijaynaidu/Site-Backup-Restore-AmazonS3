<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 28-8-2015 IST
 */

class VjBackup{
    private $absolutePathOfWpFiles = "";
    private $backupFilePath = "";
    private $backupSqlFilePath = "";
    private $backupSqlZipPath = "";
    private $excludeFilePathsForBackup = array();
    private $isDoDatabaseBackup = false;
    private $databaseConfig = array(
        'host'=>'',
        'port'=>'',
        'username'=>'',
        'password'=>'',
        'database'=>'',
    );

    public function __construct($arrConfig = array()){
        if(!empty($arrConfig['files_to_make_backup'])) {
            $this->absolutePathOfWpFiles = $arrConfig['files_to_make_backup'];
        }
        else{
            echo "Undefined path of files to be backup. \n"; exit;
        }

        if(!empty($arrConfig['backup_file'])) {
            $this->backupFilePath = $arrConfig['backup_file'];
        }
        else{
            echo "Undefined path of backup file to be stored temp. \n"; exit;
        }

        if(!empty($arrConfig['exclude_file_paths_for_backup'])) {
            $this->excludeFilePathsForBackup = $arrConfig['exclude_file_paths_for_backup'];
        }

        if(isset($arrConfig['is_database_backup'])){
            if($arrConfig['is_database_backup'] == true){
                $this->isDoDatabaseBackup = true;
                if(!empty($arrConfig['database_host']) && !empty($arrConfig['database_port']) && !empty($arrConfig['database_user']) && !empty($arrConfig['database_password']) && !empty($arrConfig['database_name']) && !empty($arrConfig['backup_sql_path']) && !empty($arrConfig['backup_sql_zip_path'])){
                    $this->databaseConfig = array(
                        'host'=>$arrConfig['database_host'],
                        'port'=>$arrConfig['database_port'],
                        'username'=>$arrConfig['database_user'],
                        'password'=>$arrConfig['database_password'],
                        'database'=>$arrConfig['database_name'],
                    );

                    $this->backupSqlFilePath = $arrConfig['backup_sql_path'];
                    $this->backupSqlZipPath = $arrConfig['backup_sql_zip_path'];
                }
                else{
                    $this->echoConsole("Database backup requested. But empty or invalid parameters!. Turn off backup database if you not required.", true);
                }
            }
        }
    }

    public function backup(){
        $arr = array(
            'status'=>false,
            'backup_file_path'=>'',
            'backup_file_name'=>'',
            'backup_file_size'=>'',
            'backup_sql_path'=>'',
            'backup_sql_name'=>'',
            'backup_sql_size'=>'',
        );
        $this->echoConsole('Backup process started. ');
        $backupDone = $this->_doBackup();
        if($backupDone['status']){
            $arr = array(
                'status'=>true,
                'backup_file_path'=>$this->backupFilePath,
                'backup_file_name'=>pathinfo($this->backupFilePath, PATHINFO_BASENAME),
                'backup_file_size'=>filesize ($this->backupFilePath),
            );
            if(!empty($backupDone['db_backup'])){
                if(file_exists($backupDone['db_backup'])) {
                    $arr['backup_sql_path'] = $backupDone['db_backup'];
                    $arr['backup_sql_name'] = pathinfo($backupDone['db_backup'], PATHINFO_BASENAME);
                    $arr['backup_sql_size'] = filesize($backupDone['db_backup']);
                }
            }
        }

        return $arr;
    }

    public function echoConsole($mess = '', $isExit = false){
        echoConsole($mess, $isExit);
    }

    private function _doBackup(){
        $details = array(
            'status'=> false,
            'db_backup'=> '',
            'file_backup'=> '',
        );

        if(file_exists($this->backupFilePath)) {
            unlink($this->backupFilePath);
        }

        $this->echoConsole('Doing backup started.');

        if($this->isDoDatabaseBackup){
            //Database backup need to be done.
            $databaseBackupCmd = "mysqldump -P ".$this->databaseConfig['port']." -h ".$this->databaseConfig['host']." -u ".$this->databaseConfig['username']." -p".$this->databaseConfig['password']." --routines ".$this->databaseConfig['database']." > ".$this->backupSqlFilePath;
            $this->echoConsole('Executing command '.$databaseBackupCmd);

            if($this->executeCommand($databaseBackupCmd)){
                if(file_exists($this->backupSqlFilePath)){
                    //$sqlZipCommand = "zip ".$this->backupSqlZipPath." ".$this->backupSqlFilePath;
                    $sqlZipCommand = "cd ".pathinfo($this->backupSqlFilePath, PATHINFO_DIRNAME)." && ";
                    $sqlZipCommand .= "zip ".$this->backupSqlZipPath." ".pathinfo($this->backupSqlFilePath, PATHINFO_BASENAME);
                    if($this->executeCommand($sqlZipCommand)){
                        if(file_exists($this->backupSqlZipPath)){
                            unlink($this->backupSqlFilePath);
                            $details['status'] = true;
                            $details['db_backup'] = $this->backupSqlZipPath;
                            $this->echoConsole('Generated Database backup zip file. ');
                        }
                        else {
                            $this->echoConsole('Sql zip backup file not exist. '.$this->backupSqlZipPath, true);
                        }
                    }
                    else{
                        $this->echoConsole('Unable to zip sql backup file. '.$this->backupSqlZipPath, true);
                    }
                }
                else{
                    $this->echoConsole('Unable to dump sql backup file. '.$this->backupSqlFilePath, true);
                }
            }
            else{
                $this->echoConsole('Unable to generate sql backup file. Please make sure to enable shell_exec or exec.', true);
            }
        }

        $excludeFilesCommand = '';
        if(!empty($this->excludeFilePathsForBackup)){
            if(is_array($this->excludeFilePathsForBackup)){
                $excludeFilesCommand = " -x ".implode(" ", $this->excludeFilePathsForBackup);
            }
        }

        //$cmd = "zip -r ".$this->backupFilePath." ".$this->absolutePathOfWpFiles.$excludeFilesCommand;//TODO: Full path zip testing..
        $cmd = "cd ".$this->absolutePathOfWpFiles." && zip -r ".$this->backupFilePath." * ".$excludeFilesCommand;
        $this->echoConsole('Executing command '.$cmd);

        if($this->executeCommand($cmd)){
            if(file_exists($this->backupFilePath)) {
                $this->echoConsole('Backup generated.');
                $details['status'] = true;
                $details['file_backup'] = $this->backupFilePath;
            }
            else{
                $this->echoConsole('Unable to generate backup file. Command executed \n');
                $this->echoConsole($cmd, true);
            }
        }
        else{
            $this->echoConsole('Unable to generate backup file. Please make sure to enable shell_exec or exec.', true);
        }

        return $details;
    }

    public function executeCommand($command = ""){
        $isExecuted = false;
        if(!empty($command)){
            if(function_exists('shell_exec')) {
                shell_exec($command);
                $isExecuted = true;
            }
            else if(function_exists('exec')){
                exec($command);
                $isExecuted = true;
            }
        }

        return $isExecuted;
    }
}



?>