<?php
return [
'name'=>env('APP_NAME','Stock Barang'),'env'=>env('APP_ENV','production'),'debug'=>(bool)env('APP_DEBUG',false),'url'=>env('APP_URL','http://localhost'),'timezone'=>'Asia/Jakarta','locale'=>'id','fallback_locale'=>'en','faker_locale'=>'id_ID','key'=>env('APP_KEY'),'cipher'=>'AES-256-CBC','force_https'=>(bool)env('FORCE_HTTPS',false),
'previous_keys'=>array_filter(explode(',',env('APP_PREVIOUS_KEYS',''))),'maintenance'=>['driver'=>env('APP_MAINTENANCE_DRIVER','file'),'store'=>env('APP_MAINTENANCE_STORE','database')]
];
