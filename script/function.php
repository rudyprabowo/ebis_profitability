<?php
function is_cli()
{
    if (defined('STDIN')) {
        return true;
    }

    if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
        return true;
    }

    return false;
}

function openssl_encryption($plaintext, $key, $options = 0, $initvector, $cipher = "aes-256-cbc")
{
    // var_dump(openssl_get_cipher_methods());
    if ($plaintext === "" || $key === "" || $plaintext === null || $key === null) {
        return false;
    } else if (in_array($cipher, openssl_get_cipher_methods())) {
        // echo $plaintext . PHP_EOL;
        // echo $cipher . PHP_EOL;
        // echo $key . PHP_EOL;
        // echo $options . PHP_EOL;
        // echo $initvector . PHP_EOL;
        $ciphertext = openssl_encrypt($plaintext, $cipher, $key, $options, $initvector,$tag);
        return $ciphertext;
    } else {
        return false;
    }
}

function openssl_decryption($ciphertext, $key, $options = 0, $initvector, $cipher = "aes-256-cbc")
{
    if ($ciphertext === "" || $key === "" || $ciphertext === null || $key === null) {
        return false;
    } else if (in_array($cipher, openssl_get_cipher_methods())) {
        // echo $ciphertext . PHP_EOL;
        // echo $cipher . PHP_EOL;
        // echo $key . PHP_EOL;
        // echo $options . PHP_EOL;
        // echo $initvector . PHP_EOL;
        // echo $tag . PHP_EOL;
        $original_plaintext = openssl_decrypt($ciphertext, $cipher, $key, $options, $initvector);
        return $original_plaintext;
    } else {
        return false;
    }
}
