<?php

/** 
BASED ON A SCRIPT FROM http://www.shozab.com/php-serialization-vs-json-encoding-for-an-array/
Removed HTML, added cli colouring and 150k suite

running with php 5.6 docker
$  docker run -it --rm --name=json-vs-serialize -v "/home/kuba/www/tests":/usr/src/myapp -w /usr/src/myapp php:5.6.30-alpine php json-vs-serialize.php

running with php7 installed
$  php json-vs-serialize.php

add a tmp directory inside or change the location
*/
define('GREEN', "\033[0;32m");
define('WHITE', "\033[0m");
define('BLUE', "\033[0;34m");
define('RED', "\033[0;31m");

ini_set('max_execution_time', 0);
ini_set('memory_limit', '2G');
 
function start_timer()
{
    global $time_start;
    return $time_start = microtime(true);
}
 
function end_timer()
{
    global $time_start;
    $totaltime = microtime(true) - $time_start;
    echo 'Process completed in ' . $totaltime*1000 . ' ms'."\n";
    return $totaltime;
}  
 
function get_random_string($valid_chars, $length)
{
    $random_string = "";
    $num_valid_chars = strlen($valid_chars);
    for ($i = 0; $i < $length; $i++){
        $random_pick = mt_rand(1, $num_valid_chars);
        $random_char = $valid_chars[$random_pick-1];
        $random_string .= $random_char;
    }
    return $random_string;
}
 
function save_csv($data)
{
    $csvstring = implode(array_keys($data[0]),',')."\n";;
     
    foreach($data as $v)
    {
        $csvstring .= implode($v,',')."\n";
    }  
     
    file_put_contents('test_'.time().'.csv',$csvstring);
}
 
function runtest($datasize)
{
    $stats_row = array();
    echo BLUE."Making Test Data of size $datasize".WHITE."\n";
    $array = array();
    for($i=0; $i<$datasize; $i++)
    {
        $array[] = array('id'=>$i,
            'text'=>get_random_string('abcdefghi',16)
        );
    }
     
    $stats_row['datasize'] = $datasize;
     
    start_timer();
    echo GREEN.' Encoding in Json'.WHITE."\n";
    $jsonencodeddata = json_encode($array);
    $stats_row['encode_json'] = end_timer();
             
    $f = 'tmp/'.$datasize.'_json.dat';
    file_put_contents($f,$jsonencodeddata);
    $stats_row['json_size(MB)'] = filesize($f)/1048576;
     
    start_timer();
    echo GREEN.' Decoding from Json'.WHITE."\n";
    $jsondecodeddata = json_decode($jsonencodeddata);
    $stats_row['decode_json'] = end_timer();
     
    start_timer();
    echo GREEN.' Serialization of data'.WHITE."\n";
    $serializeddata = serialize($array);
    $stats_row['serialize'] = end_timer();
     
    $f = 'tmp/'.$datasize.'_serialize.dat';
    file_put_contents($f,$serializeddata);
    $stats_row['serialize_size(MB)'] = filesize($f)/1048576;
     
    start_timer();
    echo GREEN.' Unserialization of data'.WHITE."\n";
    $unserializeddata = unserialize($serializeddata);
    $stats_row['unserialize'] = end_timer();
     
    return $stats_row;
}
 
$stats = array();
 
$files = glob('tmp/*'); // get all file names  
foreach($files as $file){ // iterate files
  if(is_file($file))
    unlink($file); // delete file
}
 
for($i=1000; $i<50000; $i+=1000)
{
    $row = runtest($i);
	echo "\n".RED."json encoded: ".WHITE.round($row['json_size(MB)'],2)." ".RED."serialized: ".WHITE.round($row['serialize_size(MB)'], 2)." ".RED."ratio: ".WHITE.round(($row['serialize_size(MB)'] / $row['json_size(MB)'])*100, 2)."%\n";
    echo "\n\n".GREEN."----------------------------------------".WHITE."\n";
}

$row = runtest(150000);
echo "\n".RED."json encoded: ".WHITE.round($row['json_size(MB)'],2)." ".RED."serialized: ".WHITE.round($row['serialize_size(MB)'],2)." ".RED."ratio: ".WHITE.round(($row['serialize_size(MB)'] / $row['json_size(MB)'])*100, 2)."%\n";
echo "\n\n".GREEN."----------------------------------------".WHITE."\n";
 
