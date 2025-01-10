<?php

namespace Ja_D0\treegrid;

use yii\web\AssetBundle;

/**
 *
 * @author Leandro Gehlen <leandrogehlen@gmail.com>
 */
class TreeGridAsset extends AssetBundle {

    public $sourcePath = __DIR__ . '/assets';

    public $js = [
        'js/jquery.treegrid.min.js',
    ];

    public $css = [
        'css/jquery.treegrid.min.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset'
    ];

}