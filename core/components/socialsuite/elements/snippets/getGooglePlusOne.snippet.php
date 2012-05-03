<?php
/**
 * SocialSuite - getGooglePlusOne
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
$defaults = include $extraPath . 'elements/snippets/properties/getGooglePlusOne.properties.php';
$scriptProperties = array_merge($defaults, $scriptProperties);

/* @var SocialSuite $socialsuite */
$socialsuite = $modx->getService('socialsuite','SocialSuite', $extraPath . 'model/');
if (!$socialsuite) return '[getGooglePlusOne] Error instantiating SocialSuite class.';

if (empty($scriptProperties['url'])) return '[getGooglePlusOne] Error: no url defined.';

$data = array();
$cached = false;
$cacheKey = 'socialsuite/google/plusone/' . md5($scriptProperties['url']);
$cache = intval($scriptProperties['cache']) && ($scriptProperties['cacheExpires'] > 0);
if ($cache) {
    $data = $modx->cacheManager->get($cacheKey, $socialsuite->cacheOptions);
    if (!empty($data)) $cached = true;
}

/* If we can't retrieve it from cache, retrieve it from the unofficial +1 API. */
if (!$cached) {
    $targeturl = 'https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ';
    $url = $scriptProperties['url'];
    $post =  '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]';

    $data = $socialsuite->simpleCurlRequest($targeturl, $post);

    if (!empty($data)) $data = $modx->fromJSON($data);
}

if (!$data || empty($data)) return '[getGooglePlusOne] Sorry, something went wrong requesting the data.';

if (isset($data['error'])) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[getGooglePlusOne] An error occured fetching Google +1 count: '.$data['error']['message']);
    return 0;
}

$output = (int)$data[0]['result']['metadata']['globalCounts']['count'];

if (!$cached && $cache) {
    $modx->cacheManager->set($cacheKey, $data, $scriptProperties['cacheExpires'], $socialsuite->cacheOptions);
}

return $output;
