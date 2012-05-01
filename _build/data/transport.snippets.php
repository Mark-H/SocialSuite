<?php
/**
 * SocialSuite
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
*/

$snips = array(
    'getFacebookPhotos' => 'Gets all photos and albums from photos, templateable of course.',
    'getFacebookProfile' => 'Retrieves public profile data from a Facebook user or page.',
    'getGooglePlusOne' => 'Gets the amount of +1\'s for a specific URL.',
    'getTwitterProfile' => 'Retrieves profile data from a Twitter account, along with the latest status update.',
    'prettyNumbers' => 'Output filter to transform numbers to K, M or B-rounded values.'
);

$snippets = array();
$idx = 0;

foreach ($snips as $sn => $sdesc) {
    $idx++;
    $snippets[$idx] = $modx->newObject('modSnippet');
    $snippets[$idx]->fromArray(array(
       'id' => $idx,
       'name' => $sn,
       'description' => $sdesc . ' (Part of SocialSuite)',
       'snippet' => getSnippetContent($sources['snippets'].$sn.'.snippet.php')
    ));

    $snippetProperties = array();
    $props = include $sources['snippets'].'/properties/'.$sn.'.properties.php';
    foreach ($props as $key => $value) {
        if (is_string($value) || is_int($value)) { $type = 'textfield'; }
        elseif (is_bool($value)) { $type = 'combo-boolean'; }
        else { $type = 'textfield'; }
        $snippetProperties[] = array(
            'name' => $key,
            'desc' => 'socialsuite.prop_desc.'.$key,
            'type' => $type,
            'options' => '',
            'value' => ($value != null) ? $value : '',
            'lexicon' => 'socialsuite:default'
        );
    }
    
    if (count($snippetProperties) > 0)
        $snippets[$idx]->setProperties($snippetProperties);
}

return $snippets;

