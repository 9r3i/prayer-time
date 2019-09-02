<?php
/* load helper */
require_once('adzanHelper.php');

/* initialize helper */
$helper=new adzanHelper;
if($helper->error){
  exit("Error: {$helper->error}\r\n");
}

/* get wilayah by index --> 194 = bekasi (kota) */
$wil=$helper->getWilayah(194);
if(!$wil||$helper->error){
  exit("Error: Failed to get wilayah data.\r\n");
}

/* setup latitude, longitude and timezone */
$neoadzan=new NeoAdzan();
$neoadzan->setLatLng($wil->latitude,$wil->longitude);
$neoadzan->setTimeZone($wil->timezone);

/* generate daily prayer time by date */
$r=$neoadzan->getDaily(date('Y'),date('n'),date('j'));
//$r=$neoadzan->getMonthly(date('Y'),date('n'));

/* print the result */
print_r($r);

/* searching test for bekasi */
$search=$helper->search('bekasi');
if(!$search||$helper->error){
  exit("Error: Failed to search wilayah.\r\n");
}
print_r($search);


