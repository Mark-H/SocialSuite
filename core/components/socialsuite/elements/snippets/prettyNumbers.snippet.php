<?php
/**
 * SocialSuite - prettyNumbers
 *
 * Copyright 2011 by Mark Hamstra <hello@markhamstra.com>
 *
 * This file is part of SocialSuite.
 *
 * SocialSuite is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * SocialSuite is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * SocialSuite; if not, write to the Free Software Foundation, Inc., 59 Temple Place,
 * Suite 330, Boston, MA 02111-1307 USA
 *
 * @var modX $modx
 * @var string $input
 * @var string $options
 **/

$opts = (isset($options) && !empty($options)) ? explode('&', $options) : array();
$options = array();
foreach ($opts as $opt) {
    $ta = explode('=',$opt);
    $options[$ta[0]] = $ta[1];
}
if (!isset($input) || empty($input) || !is_numeric($input)) return '0';

$input = (float)$input;
$decimals = 0;
$inputSuffix = '';
$dec_point = '.';
$thousands_sep = ',';
if ($input < -10000000000 || $input > 10000000000) {
    $input = $input / 1000000000;
    $decimals = 0;
    $inputSuffix = 'b';
} elseif ($input < -1000000000 || $input > 1000000000) {
    $input = $input / 1000000000;
    $decimals = 1;
    $inputSuffix = 'b';
} elseif ($input < -10000000 || $input > 10000000) {
    $input = $input / 1000000;
    $decimals = 0;
    $inputSuffix = 'm';
} elseif ($input < -1000000 || $input > 1000000) {
    $input = $input / 1000000;
    $decimals = 1;
    $inputSuffix = 'm';
} elseif ($input < -10000 || $input > 10000) {
    $input = $input / 1000;
    $decimals = 0;
    $inputSuffix = 'k';
} elseif ($input < -1000 || $input > 1000) {
    $input = $input / 1000;
    $decimals = 1;
    $inputSuffix = 'k';
}

if (isset($options['case']) && in_array($options['case'], array('u','ucase','upper','strtoupper'))) {
    $inputSuffix = strtoupper($inputSuffix);
}
if (isset($options['decimal'])) {
    $dec_point = $options['decimal'];
}
if (isset($options['thousands'])) {
    $thousands_sep = $options['thousands'];
}

return number_format($input, $decimals, $dec_point, $thousands_sep) . $inputSuffix;
