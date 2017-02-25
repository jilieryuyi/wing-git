<?php namespace Wing\Git;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 16/11/17
 * Time: 16:51
 */

class Command
{
    private  $command;
    public function __construct( $command )
    {
        $this->command = $command;
    }

    public function run(){
        $handle = popen( $this->command ,"r");

        $result = '';
        while(1){
            $res = fgets($handle, 1024);
            if( $res )
                $result.=$res;
            else break;
        }
        pclose($handle);
        return $result;
    }
}