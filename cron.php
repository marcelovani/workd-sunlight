<?php
/**
 * Created by PhpStorm.
 * User: marcelovani
 * Date: 27/12/16
 * Time: 16:47
 */

echo '<pre>';

$longitude = $_GET['longitude'];
$latitude = $_GET['latitude'];
$gmtOffset = 0; //@todo add day light saving info
$zenith = 90+(50/60);

$year = date('Y', time());
$startDate = mktime(0, 0, 1, 1, 1, $year);

$days = 365;
$secondsDay = 24 * 60 * 60;
$time = $startDate;
$hoursLight = 0;
$hoursDarkness = 0;
for ($d = 0; $d < $days; $d++) {

  $sunrise = date_sunrise($time, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $gmtOffset);
  $sunset = date_sunset($time, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $gmtOffset);

  $light = $sunset - $sunrise;
  $darkness = $secondsDay - $light;

  $hoursLight = $hoursLight + $light;
  $hoursDarkness = $hoursDarkness + $darkness;

  echo 'sunrise ' .
    date('Y-M-d H:m:i', $sunrise) .
    ' sunset ' . date('H:m:i', $sunset) .
    ' light ' . date('m:i', $light) .
    ' darkness ' . date('m:i', $darkness) .
    '<br/>';

  $time = $time + $secondsDay;
}

$sum = $hoursLight + $hoursDarkness;
$percLight = round($hoursLight * 100 / $sum);
$percDark = round($hoursDarkness * 100 / $sum);

echo
  'total light    ' . str_repeat("*", $percLight) . $hoursLight . ' ' . $percLight . '% ' .
  '<br/>' .
  'total darkness ' . str_repeat(")", $percDark) . $hoursDarkness . ' ' . $percDark . '% ' .
  '<br/>';
