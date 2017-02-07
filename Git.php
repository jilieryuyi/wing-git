<?php namespace Wing\Git;
use Wing\Exception\Wexception;
use Wing\FileSystem\WDir;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 16/11/10
 * Time: 13:16
 */
class Git{

    private $repository;
    private $git_command_path;

    //这部分用于代码分析统计
    /**
     * @var array 支持的文件后缀
     */
    private $support_file_ext = [
        "php","","html","htm","js","less","sass"
    ];

    /**
     * @var array 排除的文件路径
     */
    private $exclude_path = [];

    /**
     * @var array 排除的文件名
     */
    private $exclude_filename = [];

    /**
     * @var array 排除的文件
     */
    private $exclude_file = [];

    /**
     * 构造函数
     *
     * @param string $repository git仓库目录路径，使用绝对路径
     * @param string $git_command_path git命令路径，如果git不能直接识别，请使用绝对路径
     */
    public function __construct(
        $repository,
        $git_command_path = "git"
    )
    {
        $repository             = str_replace("\\","/",$repository);
        $repository             = rtrim( $repository, "/" );
        $this->repository       = $repository;
        $this->git_command_path = $git_command_path;

        $this->checkGitCommand();
    }

    /**
     * @添加支持的文件后缀
     *
     * @param string|array $ext
     */
    public function addSupportFileExtension( $ext ){
        if( is_array($ext) )
            $this->support_file_ext = array_merge( $this->support_file_ext, $ext );
        else
            $this->support_file_ext[] = $ext;
    }

    /**
     * @添加排除目录
     *
     * @param string|array $path
     */
    public function addExcludePath($path){
        if(is_array($path))
            $this->exclude_path = array_merge( $this->exclude_path, $path );
        else
            $this->exclude_path[] = $path;
    }

    /**
     * @添加排除的文件名，可以包含扩展，也可以不含扩展
     */
    public function addExcludeFileName($file_name){
        if( is_array( $file_name ) )
        {
            $this->exclude_filename = array_merge( $this->exclude_filename, $file_name );
        }
        else
        {
            $this->exclude_filename[] = $file_name;
        }
    }


    /**
     * @添加排除的文件
     */
    public function addExcludeFile($file){
        if( is_array($file) ){
            $this->exclude_file = array_merge( $this->exclude_file, $file );
        }
        else{
            $this->exclude_file[] = $file;
        }
    }



    public function setRepository( $repository ){
        $repository             = str_replace("\\","/",$repository);
        $repository             = rtrim( $repository, "/" );
        $this->repository       = $repository;
        return $this;
    }

    public function setGitCommandPath( $git_command_path ){
        $this->git_command_path = $git_command_path;
        return $this;
    }

    public function getRepository( ){
        return $this->repository;
    }

    public function getGitCommandPath( ){
        return $this->git_command_path;
    }

    /**
     * @检验git命令是否可用
     */
    private function checkGitCommand(){
        $res = $this->runCommand( $this->git_command_path );
        if( strpos( $res, "command not found" ) !== false )
        {
            echo "git command not fund";
            exit;
        }
    }

    /**
     * @执行命令
     *
     * @param string $command 命令
     * @return string 命令输出结果
     */
    private function runCommand( $command ){
        //echo "run : ", $command, "\r\n";
        return (new Command( $command) )->run();
    }

    /**
     * @获取所有分支
     *
     * @return array
     */
    public function getBranches(){

        $command = "cd ".$this->repository."&&".$this->git_command_path.' branch -a';
        $result  = $this->runCommand( $command );
        $arr     = explode("\n",$result);

        if( !$arr || count($arr)<=0) {
            return [];
        }

        $branches = [];

        foreach ( $arr as $item )
        {
            $item = trim( $item );

            if( !$item ) {
                continue;
            }

            if($item[0] == "*") {
                //$this->current_branch =
                $branches[] = trim(ltrim($item,"*"));//$this->current_branch;
            }
            else{
                if( strpos($item,"/") === false )
                    $branches[] = $item;
                else
                {
                    $temp = explode("/",$item);
                    $branches[] = array_pop( $temp );
                }
            }
        }

        $branches = array_unique( $branches );
        foreach ( $branches as $key=>$branch ){
            if( $branch == "" )
                unset($branches[$key]);
        }
        return $branches;
    }

    /**
     * @获取当前分支，返回空字符串获取失败
     *
     * @return string
     */
    public function getCurrentBranche(){

        $command = "cd ".$this->repository."&&".$this->git_command_path.' branch -a';
        $result  = $this->runCommand( $command );
        $arr     = explode("\n",$result);

        if( !$arr || count($arr)<=0) {
            return "";
        }


        foreach ( $arr as $item )
        {
            $item = trim( $item );

            if( !$item ) {
                continue;
            }

            if($item[0] == "*") {
                return trim(ltrim($item,"*"));
            }
        }

        return "";
    }


    /**
     * @获取git仓库url路径
     *
     * @return array
     *
     * @demo
     * [
     *      "origin"=>"http://www.github.com/123.git",
     *      "oschina"=>"http://www.github.com/456.git",
     * ]
     */
    public function getGitUrl(){

        $command = "cd ".$this->current_path->get()."&&".$this->git_command_path.' remote -v';
        $result  = $this->runCommand( $command );
        $arr     = explode("\n",$result);

        $urls = [];
        foreach ( $arr as $k=>$v){
            if(!$v)continue;
            $v = preg_replace("/\s+/"," ",$v);
            $t = explode(" ", $v);
            if(count($t)<2)continue;
            $urls[$t[0]] = $t[1];
        }

        return $urls;
    }

    /**
     * @判断当前目录是否是一个git仓库
     * @判断方式，从当前目录开始找，如果不是就找父目录，直到根目录都不是git仓库，才判定不是仓库
     *
     * @return bool 返回true说明当前路径是一个git仓库
     */
    public function isRepo(){

        if( is_dir( $this->repository."/.git") )
            return true;

        $path  = str_replace("\\","/",$this->repository);
        $spath = $path[0];
        $path  = trim($path,"/");

        $paths = explode("/",$path);

        if( $spath == "/" )
            $temp  = "/".$paths[0];
        else
            $temp  = $paths[0];

        for ( $i = 1; $i < count($paths); $i++ ){
            if(is_dir($temp."/.git")){
                return true;
            }
            $temp = $temp."/".$paths[$i];
        }

        if( is_dir($temp."/.git") ){
            return true;
        }

        return false;
    }

    /**
     * @是否存在某分支
     *
     * @param string $branch_name
     * @return bool
     */
    public function hasBranch( $branch_name ){
        foreach ( $this->getBranches() as $branch )
        {
            if( strtolower( trim($branch_name) ) == strtolower($branch) )
                return true;
        }
        return false;
    }


    /**
     * @切换分支，如果不存在会创建新的分支
     *
     * @param string $branch_name
     * @return self
     */
    public function checkOut( $branch_name ){
        $branch_name = trim($branch_name);
        if( !$branch_name )
            return $this;
        //git checkout $branch_name
        if( !$this->hasBranch( $branch_name ))
            $command = "cd ".$this->repository."&&".$this->git_command_path.' checkout -b '.$branch_name;
        else
            $command = "cd ".$this->repository."&&".$this->git_command_path.' checkout '.$branch_name;
        $this->runCommand( $command );
        return $this;
    }

    /**
     * @显示帮助信息
     */
    public function help(){
        $this->runCommand( $this->git_command_path." help" );
        return $this;
    }

    /**
     * 添加文件到仓库
     *
     * @return self
     */
    public function add( $file = "." ){
        $command = "cd ".$this->repository."&&".$this->git_command_path.' add '.$file;
        $this->runCommand( $command );
        return $this;
    }


    /**
     * @return self
     */
    public function commit( $commit = "update" ){

        $commit  = trim(escapeshellarg($commit),"\"");
        $commit  = trim($commit,"\'");
        $commit  = '"'.date("Y-m-d H:i:s")." ".$commit.'"';
        $command = "cd ".$this->repository."&&".$this->git_command_path.' commit -m '.$commit;

        $this->runCommand( $command );

        return $this;
    }


    /**
     * git push
     *
     * @return self
     */
    public function push(){
        $urls = $this->getGitUrl();
        $keys = array_keys( $urls );
        $current_branch = $this->getCurrentBranche();
        foreach ( $keys as $key) {
            $command = "cd " . $this->repository . "&&" . 'git push '.$key.' ' . $current_branch;
            $this->runCommand($command);
        }
        return $this;
    }

    /**
     * git pull
     *
     * @return self
     */
    public function pull(){
        $urls = $this->getGitUrl();
        $keys = array_keys( $urls );
        foreach ( $keys as $key) {
            $command = "cd " . $this->repository . "&&" . 'git pull '.$key.' ' . $this->current_branch;
            $this->runCommand($command);
        }
        return $this;
    }

    /**
     * git init
     *
     * @return self
     */
    public function init(){
        $command = $command = "cd " . $this->repository . "&&" . $this->git_command_path .' init';//&&'.$this->git_command_path.' add -A&&'.$this->git_command_path.' commit -m "first commit"&&git remote add origin '.$url;
        $this->runCommand($command);
        return $this;
    }

    private function helperScandir(){
        $path[] = $this->repository.'/*';
        $files = [];
        while(count($path) != 0)
        {
            $v = array_shift($path);
            foreach(glob($v) as $item)
            {

                $is_match = false;
                foreach ( $this->exclude_path as $c) {
                    $c     = str_replace("/", "\/", $c);
                    $c     = str_replace("*", ".*", $c);
                    $is_match = preg_match("/$c/", $item);
                    if( $is_match )
                    {
                        break;
                    }
                }

                if($is_match) continue;

                if (is_dir($item))
                {
                    $path[] = $item . '/*';
                }
                elseif (is_file($item))
                {
                    $info = pathinfo( $item );

                    $is_pass = false;
                    foreach ( $this->exclude_filename as $ex_file_name){
                        if( $ex_file_name ==  $info["basename"] || $ex_file_name == $info["filename"] ){
                            $is_pass = true;
                            break;
                        }
                    }

                    foreach ( $this->exclude_file as $ex_file ) {
                        $ex_file = str_replace("\\","/",$ex_file );
                        if( $ex_file == str_replace("\\","/", $item ) )
                        {
                            $is_pass = true;
                            break;
                        }
                    }

                    if( $is_pass )
                    {
                        continue;
                    }

                    $ext  = "";
                    if( isset($info["extension"]) )
                        $ext = $info["extension"];
                    if( in_array($ext,$this->support_file_ext) )
                    {
                        $files[] = $item;
                    }
                }
            }
        }
        return $files;
    }

    /**
     * @代码统计分析
     *
     * @return array
     *
     * @字段解释 xiaoan和Not Committed Yet为作者（Not Committed Yet为未提交的代码的意思）
     * time_statistics字段为时间统计
     * year包含的是年份的统计，代表该年份作者的代码行数
     *     year字段下面的是 年份=>代码行数
     *
     * month包含的是月份的统计，代表该月份作者的代码行数
     *      month字段下面的是 月份=>代码行数
     *
     * day是具体某一天的代码统计，代表该天的作者的代码行数
     *    day下面的字段是 日期=>代码行数
     *
     * @demo
     * array(2) {
        ["xiaoan"] => array(2) {
            ["all_lines"] => int(520)
            ["time_statistics"] => array(3) {
                            ["year"]=> array(1) {
                                    [2017] => int(520)
                            }
                            ["month"] => array(1) {
                                ["2017-02"] => int(520)
                            }
                            ["day"] => array(1) {
                                ["2017-02-07"] => int(520)
                            }
            }
        }
        ["Not Committed Yet"] => array(2) {
            ["all_lines"] => int(72)
            ["time_statistics"] => array(3) {
                ["year"]=>
                    array(1) {
                        [2017]=>int(72)
                    }
                ["month"]=>
                    array(1) {
                        ["2017-02"]=>int(72)
                    }
                ["day"]=>
                    array(1) {
                        ["2017-02-07"]=>int(72)
                    }
            }
        }
    }
    */
    public function analysis(){
        //git blame filename
        $result = [];
        $files  = $this->helperScandir();

        foreach ( $files as $file ){
            $res = $this->runCommand( "cd ".$this->repository."&&".$this->git_command_path." blame ".$file);

            $lines = explode("\n",$res);

            foreach ( $lines as $line )
            {
                preg_match("/\([\s\S].+?\)/",$line,$match);

                if( !isset($match[0]) )
                {
                    continue;
                }

                $lm = rtrim($match[0],")");
                $lm = ltrim($lm,"(");

                preg_match("/[\d]{4}\-[\d]{2}\-[\d]{2}\s[\d]{2}\:[\d]{2}\:[\d]{2}/",$lm,$lmatch);

                //echo $lm,"\r\n";

                $temp   = explode($lmatch[0],$lm);
                $author = trim($temp[0]);

               // echo $author,"\r\n";
               // echo $lmatch[0],"\r\n";

                if( !isset( $result[$author] ) )
                {
                    $result[$author]["all_lines"] = 0;
                    $result[$author]["time_statistics"] = [];
                }

                //总行数
                $result[$author]["all_lines"]++;

                $time  = strtotime($lmatch[0]);
                $year  = date("Y",$time);
                $month = date("Y-m",$time);
                $day   = date("Y-m-d",$time);


                if( !isset($result[$author]["time_statistics"]["year"][$year]) )
                    $result[$author]["time_statistics"]["year"][$year] = 0;

                if( !isset($result[$author]["time_statistics"]["month"][$month]) )
                    $result[$author]["time_statistics"]["month"][$month] = 0;

                if( !isset($result[$author]["time_statistics"]["day"][$day]) )
                    $result[$author]["time_statistics"]["day"][$day] = 0;

                $result[$author]["time_statistics"]["year"][$year]++;
                $result[$author]["time_statistics"]["month"][$month]++;
                $result[$author]["time_statistics"]["day"][$day]++;
            }
        }

        return $result;
    }

}