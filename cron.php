<?php
/**
 * Created by PhpStorm.
 * User: marcelovani
 * Date: 27/12/16
 * Time: 16:47
 */
define ('HOURS_DAY', 24);
define ('DAYS_YEAR', 365);
define ('SECONDS_HOUR', 60 * 60);
define ('ZENITH', 90+(50/60));
define ('SECONDS_DAY', HOURS_DAY * SECONDS_HOUR);

$longitude = $_GET['longitude'];
$latitude = $_GET['latitude'];
$gmtOffset = 0; //@todo add day light saving info

$year = date('Y', time());
$startDate = mktime(0, 0, 1, 1, 1, $year);
$data = process($startDate, $longitude, $latitude, $gmtOffset, 'uk-london');

saveDailyData($data);
saveCitiesSummary($data);

function saveDailyData($data) {
  $header = 'date, sunrise, sunset, daylight, darkhours';
  echo '<pre>' . $header;
  print_r($data['rows']);
}

function saveCitiesSummary($data) {
  $header = 'lat, lng, daylight, perc_day, darkness, per_dark, longest_day, longest_dark';
  echo '<pre>' . $header . '<br>';
  echo sprintf('%s,%s,%s,%s%%,%s,%s%%,%s,%s',
    $data['latitude'],
    $data['longitude'],
    $data['yearHoursDayLight'],
    $data['percDayLight'],
    $data['yearHoursDarkness'],
    $data['percDarkkness'],
    $data['longestDayLight'],
    $data['longestDarkness']
  );
}

function process($startDate, $longitude, $latitude, $gmtOffset, $city) {
  $time = $startDate;
  $debug = $_GET['debug'];

  $data = [];

  $data['latitude'] = $latitude;
  $data['longitude'] = $longitude;

  $data['yearHoursDayLight'] = 0;
  $data['yearHoursDarkness'] = 0;

  $data['longestDayLight'] = 0;
  $data['longestDarkness'] = 0;

  $data['percDayLight'] = 0;
  $data['percDarkkness'] = 0;

  for ($d = 0; $d < DAYS_YEAR; $d++) {

    $sunrise = date_sunrise($time, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, ZENITH, $gmtOffset);
    $sunset = date_sunset($time, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, ZENITH, $gmtOffset);

    // Compare longest day.
    $dayHoursLight = getDaylightHours($sunrise, $sunset);
    if ($dayHoursLight > $data['longestDayLight']) {
      $data['longestDayLight'] = $dayHoursLight;
    }

    // Compare longest night.
    $dayHoursDarkness = round(HOURS_DAY - $dayHoursLight);
    if ($dayHoursDarkness > $data['longestDarkness']) {
      $data['longestDarkness'] = $dayHoursDarkness;
    }

    // Store yearly data.
    $data['yearHoursDayLight'] = $data['yearHoursDayLight'] + $dayHoursLight * SECONDS_HOUR;
    $data['yearHoursDarkness'] = $data['yearHoursDarkness'] + SECONDS_DAY - $dayHoursLight * SECONDS_HOUR;

    $row = sprintf('%s,%s,%s,%s,%s',
      date('d/m', $sunrise),
      date('H:m:i', $sunrise),
      date('H:m:i', $sunset),
      $dayHoursLight,
      $dayHoursDarkness
    );
    $data['rows'][] = $row;
    if ($debug) echo $row . '<br/>';

    // Advance one day.
    $time = $time + SECONDS_DAY;
  }
  $data['percDayLight'] = round($data['yearHoursDayLight'] * 100 / ($data['yearHoursDayLight'] + $data['yearHoursDarkness']));
  $data['percDarkkness'] = round($data['yearHoursDarkness'] * 100 / ($data['yearHoursDayLight'] + $data['yearHoursDarkness']));

  return $data;
}

/**
 * Calculate day light hours.
 *
 * @param $sunrise
 * @param $sunset
 * @return float
 */
function getDaylightHours($sunrise, $sunset) {
  // Calculate difference of hours
  $date1 = date_create();
  date_timestamp_set($date1, $sunrise);

  $date2 = date_create();
  date_timestamp_set($date2, $sunset);

  $diff = $date2->diff($date1);

  $dayHoursLight = $diff->h;

  return round($dayHoursLight + ($diff->days * HOURS_DAY));
}
