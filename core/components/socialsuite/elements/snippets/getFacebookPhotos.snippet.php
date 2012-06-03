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

/* We'll need the FQL class */
require_once $extraPath . 'model/fql/fql.class.php';

/* This flag indicates if we can fetch the output from cache.
 * When we retrieve possibly new information via facebook this is set to FALSE.
 */
$refreshProcessedCache = false;

/* Find and cache user info */
if (empty($scriptProperties['user'])) return '[getFacebookPhotos] Error: no user defined.';
$user = (is_int($scriptProperties['user'])) ? (int)$scriptProperties['user'] : false;
if (!$user) {
    $userInfo = $modx->cacheManager->get('facebook/' . strtolower($scriptProperties['user']), $socialsuite->cacheOptions);
    if (!$userInfo || !is_array($userInfo)) {
        $refreshProcessedCache = true;
        $url = "https://graph.facebook.com/{$scriptProperties['user']}";
        $raw = $socialsuite->simpleCurlRequest($url);
        $raw = $modx->fromJSON($raw);
        if (is_array($raw) && !isset($raw['error']) && isset($raw['id'])) {
            $userInfo = $raw;
            $modx->cacheManager->set('facebook/' . strtolower($scriptProperties['user']), $userInfo, 0, $socialsuite->cacheOptions);
        } else {
            $modx->log(modX::LOG_LEVEL_ERROR, '[SocialSuite/getFacebookPhotos] Could not retrieve info about user ' . $scriptProperties['user'] . ' | Information: ' . print_r($raw, true));
            return '[SocialSuite/getFacebookPhotos] Error retrieving information about the user.';
        }
    }

    if (!$userInfo || !is_array($userInfo)) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[SocialSuite/getFacebookPhotos] No info avaiable for user ' . $scriptProperties['user']);
        return '[SocialSuite/getFacebookPhotos] User information not found.';
    }

    $user = (isset($userInfo['id'])) ? $userInfo['id'] : null;
}
if (!$user) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[SocialSuite/getFacebookPhotos] No info avaiable for user ' . $scriptProperties['user']);
    return '[SocialSuite/getFacebookPhotos] User information not found.';
}


/* Get information on the albums. Either from cache, or from Facebook */
$albumsCacheKey = 'facebook/' . strtolower($user) . '/albums';
$albums = $modx->cacheManager->get($albumsCacheKey, $socialsuite->cacheOptions);
$albumPhotos = array();

if (!$albums || empty($albums)) {
    $refreshProcessedCache = true;

    $fql = new FQL();
    $fql->newQuery('album', array(), array('owner' => $user), 'albums');
    $fql->newQuery('photo', array(), array('owner' => $user, 'aid:IN' => 'SELECT aid FROM #albums'), 'photos');
    $query = $fql->getFQL();

    $url = "https://graph.facebook.com/fql?q={$query}";
    $raw = $socialsuite->simpleCurlRequest($url);
    $raw = $modx->fromJSON($raw);
    if (!empty($raw) && is_array($raw)) {
        if (isset($raw['error'])) {
            $modx->log(modX::LOG_LEVEL_ERROR,'[SocialSuite/getFacebookPhotos] Error requesting data from Facebook: ' . $raw['error']['message'] . ' for query '.$query);
            return 'Error retrieving data.';
        }

        $data = $raw['data'];
        $albums = array(
            'nametoid' => array(),
            'ids' => array(),
        );
        foreach ($data as $dataset) {
            if ($dataset['name'] == 'albums') {
                foreach ($dataset['fql_result_set'] as $album) {
                    $aid = (string)$album['object_id'];
                    $albums['nametoid'][urlencode($album['name'])] = $aid;
                    $albums['albumids'][] = $aid;
                    $albums['data'][$aid] = $album;
                    $albumPhotos[$aid] = array();
                }
            }

            if ($dataset['name'] == 'photos') {
                foreach ($dataset['fql_result_set'] as $photo) {
                    $aoid = (string)$photo['album_object_id'];
                    if (!isset($albumPhotos[$aoid])) {
                        $albumPhotos[$aoid] = array();
                    }
                    $albumPhotos[$aoid][$photo['position']] = $photo;
                }
            }
        }

        if (!empty($albums)) {
            $modx->cacheManager->set("facebook/{$user}/albums", $albums, $scriptProperties['cacheExpires'], $socialsuite->cacheOptions);
        }
        if (!empty($albumPhotos)) {
            foreach ($albumPhotos as $album => $photos) {
                $albumKey = "facebook/{$user}/albums/{$album}";
                $modx->cacheManager->set($albumKey, $photos, $scriptProperties['cacheExpiresPhotos'], $socialsuite->cacheOptions);
            }
        }
    }
}

if (!$albums || empty($albums)) return 'Sorry, no data available.';

$showAlbums = $modx->getOption('albums', $scriptProperties, '');
$showAlbums = (!is_array($showAlbums) && !empty($showAlbums)) ? explode(',', trim($showAlbums)) : $showAlbums;
if (empty($showAlbums) || (count($showAlbums) < 1)) {
    /* Default to all albums. */
    $showAlbums = $albums['albumids'];
}

/* If albums are INT, assume it's the ID and do nothing. Else try to get the albums' ID. */
foreach ($showAlbums as $key => $album) {
    if (!is_numeric($album)) {
        if (isset($albums['nametoid'][urlencode(trim($album))])) {
            $showAlbums[$key] = $albums['nametoid'][urlencode(trim($album))];
        } else {
            $modx->log(modX::LOG_LEVEL_ERROR, '[getFacebookPhotos] Album ' . $album . ' is not a valid ID or album name.');
        }
    }
}

if (empty($showAlbums) || (count($showAlbums) < 1)) return '[getFacebookPhotos] No albums requested or valid.';

/* Create a map with all album info and their photo info. */
$returnMap = array();
$allPhotos = array();
foreach ($showAlbums as $album) {
    if (isset($albumPhotos[$album])) {
        $individualAlbumData = $albumPhotos[$album];
    } else {
        $individualAlbumCacheKey = "facebook/{$user}/albums/{$album}";
        $individualAlbumData = $modx->cacheManager->get($individualAlbumCacheKey, $socialsuite->cacheOptions);
        if (!$individualAlbumData || !is_array($individualAlbumData)) {
            $refreshProcessedCache = true;

            /* Cache does not exist. Let's fetch the info from Facebook! */
            $fql = new FQL();
            $fql->newQuery('photo',array(), array('owner' => $user, 'album_object_id' => $album));
            $url = "https://graph.facebook.com/fql?q=" . urlencode($fql->getFQL());
            $rawdata = $socialsuite->simpleCurlRequest($url);
            $rawdata = $modx->fromJSON($rawdata);
            if ($rawdata && is_array($rawdata) && !isset($rawdata['error'])) {
                $cacheTime = $scriptProperties['cacheExpiresPhotos'] + rand(-$scriptProperties['cacheExpiresPhotosVariation'], $scriptProperties['cacheExpiresPhotosVariation']);
                $modx->cacheManager->set($individualAlbumCacheKey, $rawdata['data'], $cacheTime, $socialsuite->cacheOptions);
                $individualAlbumData = $rawdata['data'];
            }
        }
    }
    if (is_array($individualAlbumData)) {
        $returnMap[$album] = array(
            'photos' => $individualAlbumData,
            'meta' => $albums['data'][$album],
        );
        $allPhotos = array_merge($allPhotos, $individualAlbumData);
    }
}

$outputCacheKey = "_processed/facebook/photos/".md5(serialize($scriptProperties));
if (!$refreshProcessedCache && intval($scriptProperties['cacheOutput'])) {
    $output = $modx->cacheManager->get($outputCacheKey, $socialsuite->cacheOptions);
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
                'created' => strtotime($photo['created']),
                'modified' => strtotime($photo['modified']),
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
            'created' => strtotime($photo['created']),
            'modified' => strtotime($photo['modified']),
        ));
        $ta[] = $socialsuite->getChunk($scriptProperties['photoTpl'], $tpa);
    }
    $ta = implode($scriptProperties['photoSeparator'], $ta);
    $output = $socialsuite->getChunk($scriptProperties['outerTpl'], array('photos' => $ta));
}

if (intval($scriptProperties['cacheOutput'])) {
    $modx->cacheManager->set($outputCacheKey, $output, 0, $socialsuite->cacheOptions);
}

return $output;
