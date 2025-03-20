<?php
$url = 'http://blitz.cs.niu.edu/PurchaseOrder/';
$data = array(
	'order' => 'xyz-987654321-ba', 
	'associate' => 'RE-676732',
	'custid' => '21', 
	'amount' => '7654.32');
		
$options = array(
    'http' => array(
        'header' => array('Content-type: application/json', 'Accept: application/json'),
        'method'  => 'POST',
        'content' => json_encode($data)
    )
);

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo($result);
?>
