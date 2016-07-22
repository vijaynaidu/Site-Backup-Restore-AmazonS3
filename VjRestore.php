<?php
/**
 * http://www.cyberzo.com/
 * Developed By: Vijay Naidu
 * Email: vijay@cyberzo.com
 * Date: 30-8-2015 IST
 */

class VjRestore {
    public function __construct(){
        //
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