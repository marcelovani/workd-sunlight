<?php
/**
 * Created by PhpStorm.
 * User: marcelovani
 * Date: 27/12/16
 * Time: 16:47
 */

/////////////////////////////////////////////////////////////////////////////
define ('HOURS_DAY', 24);
define ('DAYS_YEAR', 365);
define ('SECONDS_HOUR', 60 * 60);
define ('ZENITH', 90+(50/60));
define ('SECONDS_DAY', HOURS_DAY * SECONDS_HOUR);


if (!empty($_GET['longitude']) || !empty($_GET['latitude'])) {
  $longitude = $_GET['longitude'];
  $latitude = $_GET['latitude'];
  $gmtOffset = 0; //@todo add day light saving info
  $data = processCoordinates($longitude, $latitude, $gmtOffset, 'uk-london');
  saveDailyData($data);
  saveCitiesSummary($data);
} else {
  $cityData = readCsv('data/world_cities.csv');
  processWorldCities($cityData);
}


/////////////////////////////////////////////////////////////////////////////

/**
 * Process each of the items contained in world_cities.csv
 * Creates daily data and summary.
 *
 * @param $cityData
 */
function processWorldCities($cityData) {
  $gmtOffset = 0; //@todo add day light saving info
  foreach ($cityData as $item) {
    $city = $item['country'] . '-' . $item['city'];
    $data = processCoordinates($item['longitude'], $item['latitude'], $gmtOffset, $city);
    saveDailyData($data);
    saveCitiesSummary($data);
  }
}

/**
 * Read world_cities.csv
 *
 * @param $file
 * @return array
 */
function readCsv($file) {
  $cityData = [];
  $row = 0;
  if (($handle = fopen($file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
      // Ignore comments.
      if (substr($data[0], 0, 1) == '#') continue;

      $row++;

      // Skip header.
      if ($row == 1) continue;

      $cityData[$row]['country'] = $data[6];
      $cityData[$row]['city'] = $data[1];
      $cityData[$row]['latitude'] = $data[2];
      $cityData[$row]['longitude'] = $data[3];
    }
    fclose($handle);
  }
  return $cityData;
}

/**
 * Save the 365 days of the year for a given city.
 *
 * @param $data
 */
function saveDailyData($data) {
  // Disable this for now, the daily data can be calculated on the fly with an ajax callback.
  return;
  $filename = 'data/cities/' . $data['filename'] . '.csv';
  $header = 'date,sunrise,sunset,daylight,darkhours';
  file_put_contents($filename, $header . PHP_EOL);
  file_put_contents($filename, implode(PHP_EOL, $data['rows']), FILE_APPEND);
}

/**
 * Save summary only, including coordinates and totals for the year.
 *
 * @param $data
 */
function saveCitiesSummary($data) {
  static $doOnce;
  $filename = 'data/world_cities_lights.csv';

  if (empty($doOnce)) {
    $comments = '# Added two lines' . PHP_EOL . '# To match the row number of world_cities.csv';
    $header = 'filename,lat,lng,daylight,perc_day,darkness,per_dark,longest_day,longest_dark';
    file_put_contents($filename, $comments . PHP_EOL . $header . PHP_EOL);
    $doOnce = true;
  }

  $row = sprintf('%s,%s,%s,%s,%s%%,%s,%s%%,%s,%s',
    $data['filename'],
    $data['latitude'],
    $data['longitude'],
    $data['yearHoursDayLight'],
    $data['percDayLight'],
    $data['yearHoursDarkness'],
    $data['percDarkkness'],
    $data['longestDayLight'],
    $data['longestDarkness']
  );
  file_put_contents($filename, $row . PHP_EOL, FILE_APPEND);
}

/**
 * Calculates the timestamp for 1/Jan.
 */
function getStartingDate() {
  $year = date('Y', time());
  $startDate = mktime(0, 0, 1, 1, 1, $year);

  return $startDate;
}

function processCoordinates($longitude, $latitude, $gmtOffset, $city) {
  $timestamp = getStartingDate();
  $debug = !empty($_GET['debug']) ? true : false;

  $data = [];

  $data['filename'] = normalizeFilename($city);

  $data['latitude'] = $latitude;
  $data['longitude'] = $longitude;

  $data['yearHoursDayLight'] = 0;
  $data['yearHoursDarkness'] = 0;

  $data['longestDayLight'] = 0;
  $data['longestDarkness'] = 0;

  $data['percDayLight'] = 0;
  $data['percDarkkness'] = 0;

  for ($d = 0; $d <= DAYS_YEAR; $d++) {

    $sunrise = date_sunrise($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, ZENITH, $gmtOffset);
    $sunset = date_sunset($timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, ZENITH, $gmtOffset);

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
    $timestamp = $timestamp + SECONDS_DAY;
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

/**
 * Remove anything which isn't a word, whitespace, number
 * or any of the following caracters -_~,;[]().
 * @param $filename
 * @return string
 */
function normalizeFilename($filename) {
  $filename = preg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
  $filename = preg_replace("([\.]{2,})", '', $filename);
  $filename = str_replace(' ', '_', $filename);

  return $filename;
}
