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

/**
 * @param string $filename The name of the file.
 * @return string The file's content
 * @author splittingred
 */
function getSnippetContent($filename = '') {
    $o = file_get_contents($filename);
    $o = str_replace('<?php','',$o);
    $o = str_replace('?>','',$o);
    $o = trim($o);
    return $o;
}

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tstart = $mtime;
set_time_limit(0);

/*
 * We define the package here, depending on if we're
 * using PX (bespoke code) to run the build, or just the build.
 */
if (defined('PX')) {
    define('PKG_NAME',PX_NAME);
    define('PKG_NAME_LOWER',strtolower(PKG_NAME));
    define('PKG_VERSION',PX_VERSION);
    define('PKG_RELEASE',PX_RELEASE);
    $root = PX_REPOROOT;
} else {
    define('PKG_NAME','SocialSuite');
    define('PKG_NAME_LOWER',strtolower(PKG_NAME));
    define('PKG_VERSION','1.0.0');
    define('PKG_RELEASE','dev12');
    $root = dirname(dirname(__FILE__)).'/';
    require_once $root . 'config.core.php';
    require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
}

$sources = array (
    'root' => $root,
    'build' => $root .'_build/',
    'data' => $root . '_build/data/',
    'source_core' => $root.'core/components/'.PKG_NAME_LOWER,
    'source_assets' => $root.'assets/components/'.PKG_NAME_LOWER,
    'elements' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/',
    'snippets' => $root.'core/components/'.PKG_NAME_LOWER.'/elements/snippets/',
    'lexicon' => $root . 'core/components/'.PKG_NAME_LOWER.'/lexicon/',
    'docs' => $root.'core/components/'.PKG_NAME_LOWER.'/docs/',
    'model' => $root.'core/components/'.PKG_NAME_LOWER.'/model/',
);
unset($root);

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO'); echo 'Packing '.PKG_NAME_LOWER.'-'.PKG_VERSION.'-'.PKG_RELEASE.'<pre>'; flush();

$modx->loadClass('transport.modPackageBuilder','',false, true);
$builder = new modPackageBuilder($modx);
if (defined('PX_TARGETDIRECTORY')) {
    $builder->directory = PX_TARGETDIRECTORY;
} else {
    $builder->directory = dirname(dirname(__FILE__)).'/_packages/';
}
$builder->createPackage(PKG_NAME_LOWER,PKG_VERSION,PKG_RELEASE);
$builder->registerNamespace(PKG_NAME_LOWER,false,true,'{core_path}components/'.PKG_NAME_LOWER.'/');
$modx->getService('lexicon','modLexicon');



/* create category */
/* @var modCategory $category */
$category= $modx->newObject('modCategory');
$category->set('id',1);
$category->set('category',PKG_NAME);
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in category.'); flush();

/* add snippets */
$snippets = include $sources['data'].'transport.snippets.php';
if (is_array($snippets)) {
    $category->addMany($snippets,'Snippets');
} else { $modx->log(modX::LOG_LEVEL_FATAL,'Adding snippets failed.'); }
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in '.count($snippets).' snippets.'); flush();
unset($snippets);

/* create category vehicle */
$attr = array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
        'Snippets' => array(
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'name',
        ),
    )
);
$vehicle = $builder->createVehicle($category,$attr);
$vehicle->resolve('file',array(
    'source' => $sources['source_core'],
    'target' => "return MODX_CORE_PATH . 'components/';",
));
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in resolvers.'); flush();
$builder->putVehicle($vehicle);

/* now pack in the license file, readme and setup options */
$builder->setPackageAttributes(array(
    'license' => (defined('PX_LICENSE')) ? PX_LICENSE : file_get_contents($sources['docs'] . 'license.txt'),
    'readme' =>(defined('PX_SUMMARY')) ? PX_SUMMARY : file_get_contents($sources['docs'] . 'readme.txt'),
    'changelog' => (defined('PX_CHANGELOG')) ? PX_CHANGELOG : file_get_contents($sources['docs'] . 'changelog.txt'),
));
$modx->log(modX::LOG_LEVEL_INFO,'Packaged in package attributes.'); flush();

$modx->log(modX::LOG_LEVEL_INFO,'Packing...'); flush();
$builder->pack();

$mtime = microtime();
$mtime = explode(" ", $mtime);
$mtime = $mtime[1] + $mtime[0];
$tend = $mtime;
$totalTime = ($tend - $tstart);
$totalTime = sprintf("%2.4f s", $totalTime);

$modx->log(modX::LOG_LEVEL_INFO,"\n<br />Package Built.<br />\nExecution time: {$totalTime}\n");

?>
