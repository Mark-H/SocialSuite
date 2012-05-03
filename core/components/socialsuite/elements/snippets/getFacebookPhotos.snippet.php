<?php
/**
 * SocialSuite - getFacebookPhotos
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
$defaults = include $extraPath . 'elements/snippets/properties/getFacebookPhotos.properties.php';
$scriptProperties = array_merge($defaults, $scriptProperties);

/* @var SocialSuite $socialsuite */
$socialsuite = $modx->getService('socialsuite','SocialSuite', $extraPath . 'model/');
if (!$socialsuite) return '[getFacebookPhotos] Error instantiating SocialSuite class.';

if (empty($scriptProperties['user'])) return '[getFacebookPhotos] Error: no user defined.';

/* This flag indicates if we can fetch the output from cache.
 * When we retrieve possibly new information via facebook this is set to FALSE.
 */
$fullyCached = true;

/* Get information on the albums. Either from cache, or from Facebook */
$albumsCacheKey = 'socialsuite/facebook/' . $scriptProperties['user'] . '/albums';
$albumsData = $modx->cacheManager->get($albumsCacheKey);

if (!$albumsData || empty($albumsData)) {
    $fullyCached = false;
    $albumsData = array();
    $url = "https://graph.facebook.com/{$scriptProperties['user']}/albums";
    $rawdata = $socialsuite->simpleCurlRequest($url);
    if (!empty($rawdata)) {
        $rawdata = $modx->fromJSON($rawdata);
        if (is_array($rawdata) && !isset($rawdata['error']) && !empty($rawdata['data'])) {
            $albumsData['albums'] = array();
            $albumsData['albumids'] = array();
            foreach ($rawdata['data'] as $ab) {
                $albumsData['albums'][urlencode($ab['name'])] = $ab['id'];
                $albumsData['albumids'][] = $ab['id'];
                $albumsData[$ab['id']] = $ab;
            }
            $modx->cacheManager->set($albumsCacheKey, $albumsData, $scriptProperties['cacheExpiresAlbums']);
        }
    }
}
if (!$albumsData || empty($albumsData)) return '[getFacebookPhotos] Sorry, something went wrong retrieving album data.';

$albums = $modx->getOption('albums', $scriptProperties, '');
$albums = (!is_array($albums) && !empty($albums)) ? explode(',', trim($albums)) : $albums;
if (empty($albums) || (count($albums) < 1)) {
    /* Default to all albums. */
    $albums = $albumsData['albumids'];
}

/* If albums are INT, assume it's the ID and do nothing. Else try to get the albums' ID. */
foreach ($albums as $key => $album) {
    if (!is_numeric($album)) {
        if (isset($albumsData['albums'][urlencode(trim($album))])) {
            $albums[$key] = $albumsData['albums'][urlencode(trim($album))];
        } else {
            $modx->log(modX::LOG_LEVEL_ERROR, '[getFacebookPhotos] Album ' . $album . ' is not a valid ID or album name.');
        }
    }
}

if (empty($albums) || (count($albums) < 1)) return '[getFacebookPhotos] No albums requested or valid.';

/* Create a map with all album info and their photo info. */
$returnMap = array();
$allPhotos = array();
foreach ($albums as $album) {
    $individualAlbumCacheKey = "socialsuite/facebook/{$scriptProperties['user']}/albums/{$album}";
    $individualAlbumData = $modx->cacheManager->get($individualAlbumCacheKey);
    if (!$individualAlbumData || !is_array($individualAlbumData)) {
        /* Cache does not exist. Let's fetch the info from Facebook! */
        $fullyCached = false;
        $ta =
        $url = "https://graph.facebook.com/{$album}/photos";
        $rawdata = $socialsuite->simpleCurlRequest($url);
        $rawdata = $modx->fromJSON($rawdata);
        if ($rawdata && is_array($rawdata) && !isset($rawdata['error'])) {
            /* To prevent one request from fetching ALL photos, we throw in some random variation
             * into the caching time. This means that the individual albums will be retrieved
             * during different requests after the first load, to prevent time outs and other
             * nasty stuff.
             */
            $cacheTime = $scriptProperties['cacheExpiresPhotos'] + rand(-$scriptProperties['cacheExpiresPhotosVariation'], $scriptProperties['cacheExpiresPhotosVariation']);
            $modx->cacheManager->set($individualAlbumCacheKey, $rawdata['data'], $cacheTime);
            $individualAlbumData = $rawdata['data'];
        }
    }
    if (is_array($individualAlbumData)) {
        $returnMap[$album] = array(
            'photos' => $individualAlbumData,
            'meta' => $albumsData[$album],
        );
        $allPhotos = array_merge($allPhotos, $individualAlbumData);
    }
}

$outputCacheKey = "socialsuite/_processed/facebook/photos/".md5(serialize($scriptProperties));
if ($fullyCached && intval($scriptProperties['cacheOutput'])) {
    $output = $modx->cacheManager->get($outputCacheKey);
    if (!empty($output)) {
        return $output;
    }
}

$output = array();
if (intval($scriptProperties['perAlbum'])) {
    foreach ($returnMap as $albumId => $albumData) {
        $ta = $albumData['meta'];
        $ta['photos'] = array();
        foreach ($albumData['photos'] as $photo) {
            $tpa = array_merge($photo,array(
                'image_source' => $photo['source'],
                'image_thumb' => $photo['picture'],
                'original_height' => $photo['height'],
                'original_width' => $photo['width'],
                'link' => $photo['link'],
                'created' => strtotime($photo['created_time']),
                'updated' => strtotime($photo['updated_time']),
            ));
            $ta['photos'][] = $socialsuite->getChunk($scriptProperties['photoTpl'], $tpa);
        }
        $ta['photos'] = implode($scriptProperties['photoSeparator'], $ta['photos']);
        $output[] = $socialsuite->getChunk($scriptProperties['albumTpl'], $ta);
    }
    $output = implode($scriptProperties['albumSeparator'], $output);
} else {
    $i = 0;
    /* Pagination */
    $offset = (int)$scriptProperties['offset'];
    $limit = (int)$scriptProperties['limit'];
    $total = count($allPhotos);
    $totalVar = $modx->getOption('totalVar', $scriptProperties, 'total');
    $modx->setPlaceholder($totalVar,$total);

    /* Get only the required photos */
    $allPhotos = array_slice($allPhotos, $offset, $limit);
    $ta = array();
    /* Loop! */
    foreach ($allPhotos as $photo) {
        $tpa = array_merge($photo,array(
            'image_source' => $photo['source'],
            'image_thumb' => $photo['picture'],
            'original_height' => $photo['height'],
            'original_width' => $photo['width'],
            'link' => $photo['link'],
            'created' => strtotime($photo['created_time']),
            'updated' => strtotime($photo['updated_time']),
        ));
        $ta[] = $socialsuite->getChunk($scriptProperties['photoTpl'], $tpa);
    }
    $ta = implode($scriptProperties['photoSeparator'], $ta);
    $output = $socialsuite->getChunk($scriptProperties['outerTpl'], array('photos' => $ta));
}

if (intval($scriptProperties['cacheOutput'])) {
    $modx->cacheManager->set($outputCacheKey, $output, 0);
}

return $output;
