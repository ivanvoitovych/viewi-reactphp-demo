<?php

use Viewi\AppConfig;

$d = DIRECTORY_SEPARATOR;
$viewiAppPath = __DIR__ . $d;
$componentsPath =  $viewiAppPath . 'Components';
$buildPath = $viewiAppPath . 'build';
$jsPath = $viewiAppPath . 'js';
$assetsSourcePath = $viewiAppPath . 'assets';
$publicPath = __DIR__ . $d . '..' . $d . 'public'; // '/home/ivan/viewi-docs/configs/../public';
$assetsPublicUrl = '';

return (new AppConfig('react'))
    ->buildTo($buildPath)
    ->buildFrom($componentsPath)
    ->withJsEntry($jsPath)
    ->putAssetsTo($publicPath)
    ->assetsPublicUrl($assetsPublicUrl)
    ->withAssets($assetsSourcePath)
    ->combine(false)
    ->minify(false)
    ->developmentMode(true)
    ->buildJsSourceCode()
    ->watchWithNPM(true);
