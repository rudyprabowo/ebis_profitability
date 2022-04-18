<?php
require __DIR__ . '/function.php';

if(!is_cli()){
  echo "Invalid request.".PHP_EOL;
}else{
  // echo "CLI request.";
  // var_dump($argc);
  // var_dump($argv);
  if($argc!==6){
    echo "Invalid request.".PHP_EOL;
  }else{
    $act = $argv[1];
    $key = $argv[2];
    $initvector = $argv[3];
    $cipher = $argv[4];
    $file = $argv[5];

    if(!file_exists($file) && !file_exists(__DIR__."/".$file)){
      echo "Can not open file ".$file.".".PHP_EOL;
    }else if(strtolower($act)==="encrypt"){
      $content = "";
      if (file_exists($file)) {
        $content = file_get_contents($file);
      }else if(file_exists(__DIR__."/".$file)){
        $content = file_get_contents(__DIR__."/".$file);
      }
      // echo $content.PHP_EOL;
      // $ivlen = openssl_cipher_iv_length($cipher);
      // $iv = openssl_random_pseudo_bytes($ivlen);
      $crypt = openssl_encryption($content,$key,0,$initvector,$cipher);
      var_dump($crypt);
    }else if(strtolower($act)==="decrypt"){
      $content = "";
      if (file_exists($file)) {
        $content = file_get_contents($file);
      }else if(file_exists(__DIR__."/".$file)){
        $content = file_get_contents(__DIR__."/".$file);
      }
      // echo $content.PHP_EOL;
      // $ivlen = openssl_cipher_iv_length($cipher);
      // $iv = openssl_random_pseudo_bytes($ivlen);
      $crypt = openssl_decryption($content,$key,0,$initvector,$cipher);
      var_dump($crypt);
    }else{
      echo "Invalid request.".PHP_EOL;
    }
  }
}