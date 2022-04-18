<?php
use \Zend\Debug\Debug as ZendDebug;

function ini_path()
{
    return APP_PATH."ini".DS;
}

function conf_path()
{
    return APP_PATH."conf".DS;
}

function data_path()
{
    return APP_PATH."data".DS;
}

function upload_path()
{
    return APP_PATH."data".DS."upload".DS;
}

function cache_path()
{
    return APP_PATH."data".DS."cache".DS;
}

function cache_data_path()
{
    return APP_PATH."data".DS."cache".DS."data".DS;
}


function is_cli()
{
    if (defined('STDIN')) {
        return true;
    }

    if (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) {
        return true;
    }

    return false;
}

if (!function_exists('env')) {
    /**
     * @param $key
     * @param null $default
     * @return array|bool|false|mixed|string
     */
    function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        $ret = $value;
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                $ret = true;
                // no break
            case 'false':
            case '(false)':
                $ret = false;
                // no break
            case 'empty':
            case '(empty)':
                $ret = '';
                // no break
            case 'null':
            case '(null)':
                $ret = null;
                // no break
            default:
                $ret = $value;
        }

        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            $ret = substr($value, 1, -1);
        }

        return $ret;
    }
}

function findCSRF($data = [])
{
    $ret = [];
    foreach ($data as $k=>$v) {
        if (strpos(strtolower($k), "csrf-token")>0) {
            $ret = [
                'key' =>$k,
                'lowkey' =>strtolower($k),
                'val'=>$v
            ];
            break;
        }
    }

    return $ret;
}

function checkXSSString($var)
{
    $ret = true;
    if (is_array($var)) {
        foreach ($var as $value) {
            $ret = checkXSSString($value);
            if (!$ret) {
                break;
            }
        }
    } else {
        // $str = ["'();}]",'"();}]','<','>',';%3','"%3',"'%3",'%22%3',"=/",'();}]','()%3b%7D]','();%7D]'];
        $str = ['base64_decode (','base64_decode(','assert (','assert(','Select','SELECT','select','bxss.me','${','if (','if(','gethostbyname','nslookup ','nslookup','echo ','echo','print (', 'print(', ' OR ','<','>',';%3','"%3',"'%3",'%22%3',"=/",'();}]','()%3b%7D]','();%7D]','()%3b}]','}]'];
        foreach ($str as $v) {
            $ret = !strpos($var, $v);
            // zdebug($ret);zdebug($var);
            if (!$ret) {
                break;
            }
        }
    }
    return $ret;
}

function checkXSS($var)
{
    $ret = true;

    $ret = checkXSSString($var);

    return $ret;
}

function css_path()
{
    $path = "css".DS;
    if (env('APPLICATION_ENV', "development")==="production") {
        $path = "dist".DS."css".DS;
    }
    return $path;
}

function js_path()
{
    $path = "js".DS;
    if (env('APPLICATION_ENV', "development")==="production") {
        $path = "dist".DS."js".DS;
    }
    return $path;
}

function css_url()
{
    $path = "css/";
    if (env('APPLICATION_ENV', "development")==="production") {
        $path = "dist/css/";
    }
    return $path;
}

function js_url()
{
    $path = "js/";
    if (env('APPLICATION_ENV', "development")==="production") {
        $path = "dist/js/";
    }
    return $path;
}

function trim_val(&$val)
{
    if (is_string($val)) {
        trim($val);
    } elseif (is_array($val)) {
        foreach ($val as $k=>$v) {
            trim_val($v);
        }
    } else {
        $val;
    }
}

function array_keys_exist(array $array, $keys)
{
    $count = 0;
    if (!is_array($keys)) {
        $keys = func_get_args();
        array_shift($keys);
    }
    foreach ($keys as $key) {
        if (isset($array[$key]) || array_key_exists($key, $array)) {
            $count++;
        }
    }
    return count($keys) === $count;
}

//function defination to convert array to xml
function array_to_xml($array, &$xml_user_info)
{
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (!is_numeric($key)) {
                $subnode = $xml_user_info->addChild("$key");
                array_to_xml($value, $subnode);
            } else {
                $subnode = $xml_user_info->addChild("item$key");
                array_to_xml($value, $subnode);
            }
        } else {
            $xml_user_info->addChild("$key", htmlspecialchars("$value"));
        }
    }
}

function startsWith($haystack, $needle)
{
    return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
}

function endsWith($haystack, $needle)
{
    return substr_compare($haystack, $needle, -strlen($needle)) === 0;
}

function randHexColor()
{
    $rand = str_pad(dechex(rand(0x000000, 0xFFFFFF)), 6, 0, STR_PAD_LEFT);
    return '#' . $rand;
}

function ifNotSet(&$arr, $key, $replace)
{
    if (isset($arr[$key])) {
        return $arr[$key];
    } else {
        $arr[$key] = $replace;
        return $replace;
    }
}

function ifNull($var, $replace)
{
    if ($var === null) {
        return $replace;
    } else {
        return $var;
    }
}

function ifEmpty($var, $replace)
{
    if ($var === "" || empty($var)) {
        return $replace;
    } else {
        return $var;
    }
}

function ifNullEmpty($var, $replace)
{
    if ($var === null || $var === "" || empty($var)) {
        return $replace;
    } else {
        return $var;
    }
}

function isNull($var)
{
    return ($var === null);
}

function isEmpty($var)
{
    return ($var === "" || empty($var));
}

function isNullEmpty($var)
{
    if (is_array($var)) {
        foreach ($var as $v) {
            return isNullEmpty($v);
        }
    } else {
        return ($var === null || $var === "" || empty($var));
    }
}

function csvToArrayWithHeader($file)
{
    $csv = array_map('str_getcsv', file($file));
    array_walk($csv, function (&$a) use ($csv) {
        $a = array_combine($csv[0], $a);
    });
    array_shift($csv); # remove column header
    return $csv;
}

function recur_ksort(&$array)
{
    foreach ($array as &$value) {
        if (is_array($value)) {
            recur_ksort($value);
        }
    }
    return ksort($array);
}

function recur_k1sort(&$array)
{
    foreach ($array as &$value) {
        if (is_array($value)) {
            sort($value);
        }
    }
    return ksort($array);
}

function zdebug($var, $die = false)
{
    ZendDebug::dump($var);
    if ($die) {
        die();
    }
}

function get_current_week_range($format = "Y-m-d")
{
    $monday = strtotime("last monday");
    $monday = date('w', $monday) == date('w') ? $monday + 7 * 86400 : $monday;
    $sunday = strtotime(date("Y-m-d", $monday) . " +6 days");
    $this_week_sd = date($format, $monday);
    $this_week_ed = date($format, $sunday);
    return [
        "monday" => $this_week_sd,
        "sunday" => $this_week_ed,
    ];
}

function openssl_encryption($plaintext, $key, $options = 0, $initvector = null, $cipher = "aes-256-cbc")
{
    // var_dump(openssl_get_cipher_methods());
    if ($plaintext === "" || $key === "" || $plaintext === null || $key === null) {
        return false;
    } elseif (in_array($cipher, openssl_get_cipher_methods())) {
        return openssl_encrypt($plaintext, $cipher, $key, $options, $initvector, $tag);
    } else {
        return false;
    }
}

function openssl_decryption($ciphertext, $key, $options = 0, $initvector = null, $cipher = "aes-256-cbc")
{
    if ($ciphertext === "" || $key === "" || $ciphertext === null || $key === null) {
        return false;
    } elseif (in_array($cipher, openssl_get_cipher_methods())) {
        return openssl_decrypt($ciphertext, $cipher, $key, $options, $initvector);
    } else {
        return false;
    }
}

function decrypt_const()
{
    $content = file_get_contents(APP_PATH . "_" . DS . "constant.php");
    $key = "key";
    $opt = 0;
    $initvector = "1234567890QWERTY";
    $cipher = "aes-256-cbc";
    return openssl_decryption($content, $key, $opt, $initvector, $cipher);
}

function check_json_cache($cache_file)
{
    return file_exists(APP_PATH . "data" . DS . "cache" . DS . "data" . DS . $cache_file . ".json");
}

function load_json_cache($cache_file)
{
    $data = file_get_contents(APP_PATH . "data" . DS . "cache" . DS . "data" . DS . $cache_file . ".json");
    return json_decode($data, true);
}

function save_json_cache($data, $cache_file)
{
    $ret = file_put_contents(APP_PATH . "data" . DS . "cache" . DS . "data" . DS . $cache_file . ".json", json_encode($data));
    if ($ret!==false && !is_cli()) {
        try{
            // chmod(APP_PATH . "data" . DS . "cache" . DS . "data" . DS . $cache_file . ".json", 0750);
        // chown(APP_PATH . "data" . DS . "cache" . DS . "data" . DS . $cache_file . ".json","tmadev");
        // chgrp(APP_PATH . "data" . DS . "cache" . DS . "data" . DS . $cache_file . ".json","www-data");
        }catch(\Exception $e){

        }
    }
    return $ret;
}

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . "/" . $object) == "dir") {
                    rrmdir($dir . "/" . $object);
                } else {
                    unlink($dir . "/" . $object);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

function rmCacheData($key)
{
    $dir = cache_data_path();
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (filetype($dir . $object) === "dir") {
                    // zdebug("[DIR]".$dir . $object);
                    if (strpos($object, "DATACACHE")!==false) {
                        $dir2 = $dir . $object;
                        $objects2 = scandir($dir2);
                        foreach ($objects2 as $object2) {
                            if ($object2 != "." && $object2 != "..") {
                                if (filetype($dir2 ."/". $object2) !== "dir" && strpos($object2, $key)!==false) {
                                    // zdebug($dir2 ."/". $object2);
                                    unlink($dir2 . "/" . $object2);
                                }
                            }
                        }
                    }
                }
            }
        }
        reset($objects);
    }
}

function rmImportantData($data)
{
    if (is_object($data)) {
        $data = (array) $data;
    }

    $out = $data;
    if (!is_array($data)) {
        $out = json_decode($data, true);
        if ($out === false || $out === null) {
            parse_str($data, $out);
        }
    }

    foreach ($GLOBALS['IMPORTANT_DATA'] as $v) {
        if (isset($out[$v])) {
            $out[$v] = "?";
        }
    }

    return $out;
}

function rand_float($st_num = 0, $end_num = 1, $mul = 1000000)
{
    if ($st_num > $end_num) {
        return 0;
    }

    return random_int($st_num * $mul, $end_num * $mul) / $mul;
}

function secondsToTime($inputSeconds, bool $as_string = false)
{
    $secondsInAMinute = 60;
    $secondsInAnHour = 60 * $secondsInAMinute;
    $secondsInADay = 24 * $secondsInAnHour;

    // Extract days
    $days = floor($inputSeconds / $secondsInADay);

    // Extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // Extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // Extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // Format and return
    $timeParts = [];
    $sections = [
        'day' => (int)$days,
        'hour' => (int)$hours,
        'minute' => (int)$minutes,
        'second' => (int)$seconds,
    ];

    if ($as_string) {
        foreach ($sections as $name => $value) {
            if ($value > 0) {
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }

        return implode(', ', $timeParts);
    } else {
        return $sections;
    }
}

function getRemixIcon(){
    $data = file_get_contents( APP_PATH . 'data/app/tags.json');
    // zdebug($data);die();
    $_data =  json_decode($data, true);
    $_icon = [];
    foreach($_data as $k=>$v){
        if(is_array($v)){
            foreach($v as $k2=>$v2){
                $_icon[] = $k2;
            }
        }
    }
    // zdebug($_icon);die();
    return $_icon;
}
function checkUploadedFile($file, $d_file, $rule_type, $rule_size, $rule_ext, $new_name, $upload_dir)
{
    $ret['ret'] = false;
    $ret['msg'] = "invalid";
    $ret['code'] = 0;
    $ret['data'] = null;

    $valid = true;
    if ($rule_type!=="*" && !in_array($file['type'], $rule_type, true)) {
        $ret['ret'] = false;
        $ret['msg'] = "Invalid file type";
        $ret['code'] = 1;
        $valid = false;
    } elseif ($file['size']===0) {
        $ret['ret'] = false;
        $ret['msg'] = "File empty";
        $ret['code'] = 2;
        $valid = false;
    } elseif ($rule_size!==0 && $file['size']<($rule_size[0]??0)) {
        $ret['ret'] = false;
        $ret['msg'] = "File too small";
        $ret['code'] = 2;
        $valid = false;
    } elseif ($rule_size!==0 && $file['size']>($rule_size[1]??0)) {
        $ret['ret'] = false;
        $ret['msg'] = "File too large";
        $ret['code'] = 2;
        $valid = false;
    } elseif ($rule_ext!=="*") {
        $found = false;
        foreach ($rule_ext as $r) {
            if (\_\endsWith($file['name'], ".".$r)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $ret['ret'] = false;
            $ret['msg'] = "Invalid file extension";
            $ret['code'] = 1;
            $valid = false;
        }
    }

    if ($valid) {
        if ($rule_ext!=="*") {
            $d_file->withAllowedExtensions($rule_ext);
        }
        // if ($rule_size!==0) {
        //     $d_file->withMaximumSizeInBytes($rule_size[1]);
        // }

        try {
            $uploadedFile = $d_file->save();
            $ret['ret'] = true;
            $ret['msg'] = "valid";
            $ret['code'] = 0;
            $ret['data'] = [
                'new_name' => $uploadedFile->getFilenameWithExtension(),
                'ext' => $uploadedFile->getExtension(),
                // 'path' => $uploadedFile->getCanonicalPath()
            ];
        } catch (\Exception $e) {
            $ret['ret'] = false;
            $ret['msg'] = $e->getMessage();
            $ret['code'] = 4;
        }
    }

    return $ret;
}
