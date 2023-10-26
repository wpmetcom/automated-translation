<?php

$arr = [
'cs',
'nl',
'fa',
'ko',
];

foreach ($arr as $key => $to) {

    $dir = __DIR__."/translated/metform-$to";
    if(!mkdir($dir)){
        mkdir($dir);
    }
    exec(__DIR__."/vendor/bin/potrans google metform.pot $dir --credentials=credentials.json --from=en --to=$to");
    // vendor/bin/potrans google getgenie-en.po translated --credentials=credentials.json --from=en --to=bn

}