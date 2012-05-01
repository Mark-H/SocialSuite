<?php
/**
 * SocialSuite - getFacebookProfile
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
 * @var array $scriptProperties
 **/

/* Get the default properties. These are stored in a separate file to ease up adding them to the snippets. */
$extraPath = $modx->getOption('socialsuite.core_path', null, $modx->getOption('core_path') . 'components/socialsuite/');
$defaults = include $extraPath . 'elements/snippets/properties/getFacebookProfile.properties.php';
$scriptProperties = array_merge($defaults, $scriptProperties);

/* @var SocialSuite $socialsuite */
$socialsuite = $modx->getService('socialsuite','SocialSuite', $extraPath . 'model/');
if (!$socialsuite) return '[getFacebookProfile] Error instantiating SocialSuite class.';

if (empty($scriptProperties['user'])) return '[getFacebookProfile] Error: no user defined.';

$data = array();
$cached = false;
$cacheKey = "socialsuite/facebook/{$scriptProperties['user']}/profile";
$cache = intval($scriptProperties['cache']) && ($scriptProperties['cacheExpires'] > 0);
if ($cache) {
    $data = $modx->cacheManager->get($cacheKey);
    if (!empty($data)) $cached = true;
}

/* If we can't retrieve it from cache, retrieve it from Facebook. */
if (!$cached) {
    $url = "https://graph.facebook.com/{$scriptProperties['user']}";
    $data = $socialsuite->simpleCurlRequest($url);
    if (!empty($data)) $data = $modx->fromJSON($data);
}

if (!$data || empty($data)) return '[getFacebookProfile] Sorry, something went wrong requesting the data.';


$output = '';

if (intval($scriptProperties['toPlaceholders'])) {
    $prefix = $scriptProperties['toPlaceholdersPrefix'];
    $modx->toPlaceholders($data, $prefix);
} else {
    $outputKey = md5(serialize($scriptProperties));
    if ($cached && isset($data[$outputKey])) {
        $output .= $data[$outputKey];
    } else {
        $output .= $socialsuite->getChunk($scriptProperties['tpl'], $data);
        if ($cache && !empty($output)) {
            $data[$outputKey] = $output;
        }
        if ($cached && $cache) {
            $cached = false;
        }
    }
}

if (intval($scriptProperties['showAvailableData'])) {
    $output .= 'Showing all available data for Facebook user ' . $scriptProperties['user'] . ': <br />';
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            foreach ($value as $secondKey => $secondValue) {
                $output .= '<strong>' . $key . '.' . $secondKey . '</strong> = ' . $secondValue . '<br />';
            }
        } else {
            $output .= '<strong>' . $key . '</strong> = ' . $value . '<br />';
        }
        $output .= '';
    }
}

if (!$cached && $cache) {
    $modx->cacheManager->set($cacheKey, $data, $scriptProperties['cacheExpires']);
}

return $output;
