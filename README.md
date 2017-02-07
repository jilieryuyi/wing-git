###step 1
    composer install
###step 2
    修改 tests/index.php /Users/yuyi/Web/activity为自己的仓库路径
###step 3
    php tests/index.php
    
###Demo
    include __DIR__."/../vendor/autoload.php";
    $git = new \Wing\Git\Git( "/Users/yuyi/Web/activity" );
    $git->addExcludePath([
        "vendor/*"
    ]);
    $git->addExcludeFileName([
        "composer"
    ]);
    var_dump( $git->analysis() );