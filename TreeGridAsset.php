<?php

namespace Ja_D0\treegrid;

use yii\web\AssetBundle;

/**
 *
 * @author Leandro Gehlen <leandrogehlen@gmail.com>
 */
class TreeGridAsset extends AssetBundle {

    public $sourcePath = 'assets';

    public $js = [
        'js/jquery.treegrid.js',
    ];

    public $css = [
        'css/jquery.treegrid.css',
    ];

    public $depends = [
        'yii\web\JqueryAsset'
    ];

}