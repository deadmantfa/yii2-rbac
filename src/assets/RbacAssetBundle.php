<?php

declare(strict_types=1);

namespace deadmantfa\yii2\rbac\assets;

use yii\web\AssetBundle;

class RbacAssetBundle extends AssetBundle
{
    public $sourcePath = '@vendor/deadmantfa/yii2-rbac/src/assets';

    public $css = [
        'css/jstree.min.css',
        'css/custom.css',
    ];

    public $js = [
        'js/jstree.min.js',
        'js/jstree-double-panel.js',
        'js/rbac.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
