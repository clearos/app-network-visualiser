<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network_visualiser';
$app['version'] = '1.6.5';
$app['release'] = '1';
$app['vendor'] = 'ClearFoundation';
$app['packager'] = 'ClearFoundation';
$app['license'] = 'GPLv3';
$app['license_core'] = 'LGPLv3';
$app['summary'] = lang('network_visualiser_app_description');
$app['description'] = lang('network_visualiser_app_description');

/////////////////////////////////////////////////////////////////////////////
// App name and categories
/////////////////////////////////////////////////////////////////////////////

$app['name'] = lang('network_visualiser_app_name');
$app['category'] = lang('base_category_reports');
$app['subcategory'] = lang('base_category_network');

/////////////////////////////////////////////////////////////////////////////
// Packaging
/////////////////////////////////////////////////////////////////////////////

$app['requires'] = array(
    'app-network',
);

$app['core_requires'] = array(
    'app-network-core >= 1:1.4.3',
    'jnettop'
);

$app['core_file_manifest'] = array(
    'nework_visualiser.conf' => array(
        'target' => '/etc/clearos/network_visualiser.conf',
        'mode' => '0755',
        'config' => TRUE,
        'config_params' => 'noreplace'
    ),
    'nv_scan' => array(
        'target' => '/usr/sbin/nv_scan',
        'mode' => '0755',
        'owner' => 'root',
        'group' => 'root'
    ),
);

$app['delete_dependency'] = array(
    'app-network-visualiser-core',
    'jnettop'
);
