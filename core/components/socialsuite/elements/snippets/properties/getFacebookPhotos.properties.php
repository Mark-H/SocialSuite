<?php

return array(
    'user' => '',
    'albums' => '',

    'perAlbum' => false,
    'albumTpl' => 'facebook/photos/albumtpl',
    'albumSeparator' => '<br />',
    'outerTpl' => 'facebook/photos/outertpl',
    'photoTpl' => 'facebook/photos/phototpl',
    'photoSeparator' => "\n",
    'offset' => 0,
    'limit' => 20,


    'cacheOutput' => true,
    'cacheExpires' => 172800, // Default to 2 days
    'cacheExpiresPhotos' => 345600, // Default to 4 days, even though it'll be refreshed when we refresh the albums
    'cacheExpiresPhotosVariation' => 3600,

    'showAvailableData' => false,
    'toPlaceholders' => false,
    'toPlaceholdersPrefix' => 'fb',

    'tpl' => ''
);
