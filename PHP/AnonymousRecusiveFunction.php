<?php
/**
 * Lucas Maliszewski
 * Fun Project to be implemented
 */

/**
 * Contains the test file type can change to anything below.
 * @var string
 */
$ft = 'txt';

/**
 * Contains an array of file types by categories.
 * @var multiType(string)
 */
$fileTypes = [
    'pdf' =>  ['pdf'],
    'doc' =>  ['docx', 'doc'],
    'xcel' => ['xls', 'xlsx', 'csv'],
    'img' =>  ['jpg', 'gif', 'png', 'bmp'],
    'zip' =>  ['zip'],
    'txt' =>  ['txt', 'log']
];

/**
 * An anon recursive function
 *
 * @param $fileTypes
 * @param $ft
 * @return bool|int|string
 */
$findMe = function($fileTypes, $ft) use ( &$findMe ){
    foreach($fileTypes as $key => $value){
        $found = false;
        if(is_array($value)){
            $found = $findMe($value, $ft);
        }

        if($found === true){
            return $key;
        }

        if($ft === $value){
            echo 'Found it!';
            return true;
        }
    }
};
echo $findMe($fileTypes, $ft);