<?php
/* @var modX $modx
 * @var array $scriptProperties
 **/

/* Get the default properties. These are stored in a separate file to ease up adding them to the snippets. */
$corePath = $modx->getOption('socialsuite.core_path', null, $modx->getOption('core_path') . 'components/socialsuite/');
include $corePath . 'elements/snippets/properties/FacebookFans.properties.php';
