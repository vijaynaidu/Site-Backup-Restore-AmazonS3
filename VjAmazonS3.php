<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 28-8-2015 IST
 */

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Common\Exception\MultipartUploadException;
use Aws\S3\Model\MultipartUpload\UploadBuilder;

class VjAmazonS3 {
    private $awsKey = '';
    private $awsSecret = '';
    private $bucketName = '';
    private $client = '';

    public function __construct($arrConfig = array()){
        if(!empty($arrConfig['aws_key']) && !empty($arrConfig['aws_secret'])){
            $this->awsKey = $arrConfig['aws_key'];
        }
        if(!empty($arrConfig['aws_secret'])){
            $this->awsSecret = $arrConfig['aws_secret'];
        }

        $this->connect();
    }

    private function connect(){
        try {
            $client = S3Client::factory(array(
                'key' => $this->awsKey,
                'secret' => $this->awsSecret,
            ));
            $this->client = $client;
        }
        catch (S3Exception $e){
            echoConsole('Invalid aws credentials', true);
        }
    }

    public function setBucket($bucketName = ''){
        $bucketNameSet = false;

        if(!empty($bucketName)){
            $buckets = $this->client->listBuckets();
            if(!empty($buckets['Buckets'])){
                $bucketsList = $buckets['Buckets'];
                if(!empty($bucketsList)){
                    foreach($bucketsList as $bucket){
                        if($bucket['Name'] == $bucketName){
                            $this->bucketName = $bucketName;
                            $bucketNameSet = true;
                            break;
                        }
                    }
                }
            }
        }

        if(!$bucketNameSet){
            echoConsole('Invalid aws bucket name: '.$bucketName, true);
        }
    }

    public function upload($localFile = '', $remotePath = '', $isRemoveAfterUpload = false){
        $isFileUploaded = false;

        if(!empty($localFile) && file_exists($localFile)){
            if(is_file($localFile)){
                try {
                    $resource = fopen($localFile, 'r');
                    $this->client->upload($this->bucketName, $remotePath, $resource);
                    echoConsole("Backup done: $remotePath ");
                    if($isRemoveAfterUpload == true) {
                        unlink($localFile);
                        if (!file_exists($localFile)) {
                            echoConsole("Removed local made backup file.");
                        } else {
                            echoConsole("Unable to remove local made backup file.");
                        }
                    }
                } catch (S3Exception $e) {
                    echoConsole("There was an error uploading the file.", true);
                }
            }
            else{
                echoConsole("$localFile is not a file to be uploaded. ", true);
            }
        }
        else{
            echoConsole($localFile." doesn't exist.", true);
        }

        return $isFileUploaded;
    }

    public function getFilesList($path=''){
        $arrFiles = array(
            'all'=>array(),
            'files'=>array(),
            'sql'=>array(),
        );

        $iterator = $this->client->getIterator('ListObjects', array(
            'Bucket' => $this->bucketName,
            'Prefix' => $path,
        ));

        if(!empty($iterator)) {
            foreach ($iterator as $object) {
                if(!empty($object['Key'])) {
                    $objKey = $object['Key'];// Eg. VJBK_IO_FILES_____WordpressTest_____2015-08-30_09-01-17_____.zip
                    $arrObjKey = explode('_____', $objKey);
                    if(count($arrObjKey)==4) {
                        $arrObjKey['0'] = str_ireplace(AMAZON_BACKUP_PATH, '', $arrObjKey['0']);
                        if($arrObjKey['0']=='VJBK_IO_FILES'|| $arrObjKey['0']=='VJBK_IO_SQL'){
                            $ftype = 'files_backup';
                            if($arrObjKey['0']=='VJBK_IO_SQL'){
                                $ftype = 'sql_backup';
                            }

                            if(!empty($arrObjKey['2'])){
                                $arrDateTime = explode('_', $arrObjKey['2']);
                                $date = $arrDateTime['0'];
                                $arrTime = explode('-', $arrDateTime['1']);
                                $time = $arrTime['0'].":".$arrTime['1'].":".$arrTime['2'];
                                $dateTime = $date." ".$time;
                                $sizeMb = (($object['Size']/1000)/1000);
                                $aFile = array(
                                    'key' => $objKey,
                                    'file_name' => pathinfo($objKey, PATHINFO_BASENAME),
                                    'last_modified' => $object['LastModified'],
                                    'file_size' => $object['Size'],
                                    'readable_size' => number_format($sizeMb, 2, '.', '')." MB",
                                    'file_type' => $ftype,
                                    'date_time' => $dateTime,
                                    'timestamp' => strtotime($dateTime),
                                );
                                $arrFiles['all'][] = $aFile;
                                if($ftype=='files_backup') {
                                    $arrFiles['files'][] = $aFile;
                                }
                                else {
                                    $arrFiles['sql'][] = $aFile;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $arrFiles;
    }

    public function downloadFile($localPath="", $key=""){
        $isFileDownloaded = false;
        if(!empty($localPath) && !empty($key)){
            $result = $this->client->getObject(array(
                'Bucket' => $this->bucketName,
                'Key'    => $key,
                'SaveAs' => $localPath,
            ));
            $uri = $result['Body']->getUri();
            if(!empty($uri)){
                echoConsole("File downloaded from amazon saved locally as ".$uri);
                $isFileDownloaded = true;
            }
        }

        return $isFileDownloaded;
    }
} 