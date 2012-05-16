<?php
/**
 * SocialSuite - getTwitterShares
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
$defaults = include $extraPath . 'elements/snippets/properties/getTwitterShares.properties.php';
$scriptProperties = array_merge($defaults, $scriptProperties);

/* @var SocialSuite $socialsuite */
$socialsuite = $modx->getService('socialsuite','SocialSuite', $extraPath . 'model/');
if (!$socialsuite) return '[getTwitterShares] Error instantiating SocialSuite class.';

if (empty($scriptProperties['url'])) $scriptProperties['url'] = $modx->makeUrl($modx->resource->id, '', '', 'full');

$data = array();
$cached = false;
$cacheKey = 'twitter/_shares/' . md5(strtolower($scriptProperties['url']));
$cache = intval($scriptProperties['cache']) && ($scriptProperties['cacheExpires'] > 0);
if ($cache) {
    $data = $modx->cacheManager->get($cacheKey, $socialsuite->cacheOptions);
    if (!empty($data)) $cached = true;
}

/* If we can't retrieve it from cache, retrieve it from the unofficial +1 API. */
if (!$cached) {
    $url = 'http://urls.api.twitter.com/1/urls/count.json?url=' . urlencode($scriptProperties['url']);
    $data = $socialsuite->simpleCurlRequest($url);

    if (!empty($data)) $data = $modx->fromJSON($data);
}

if (!$data || empty($data)) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[getTwitterShares] Empty result set requesting data for ' . $scriptProperties['url']);
    return 0;
}

if (isset($data['error'])) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[getTwitterShares] An error occured fetching count: '.$data['error']['message']);
    return 0;
}

if (!$cached && $cache) {
    $modx->cacheManager->set($cacheKey, $data, $scriptProperties['cacheExpires'], $socialsuite->cacheOptions);
}

if (isset($data['count'])) return (string)$data['count'];
return 0;
