<?php

/////////////////////////////////////////////////////////////////////////////
// General information
/////////////////////////////////////////////////////////////////////////////

$app['basename'] = 'network_visualiser';
$app['version'] = '2.1.7';
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
$app['subcategory'] = lang('base_subcategory_performance_and_resources');

/////////////////////////////////////////////////////////////////////////////
// Controllers
/////////////////////////////////////////////////////////////////////////////

$app['controllers']['network_visualizer']['title'] = $app['name'];
$app['controllers']['settings']['title'] = lang('base_settings');
$app['controllers']['report']['title'] = lang('base_report');

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
    'jnettop.conf' => array(
        'target' => '/etc/jnettop.conf',
        'mode' => '0755',
        'config' => TRUE,
        'config_params' => 'noreplace',
    ),
);

$app['delete_dependency'] = array(
    'app-network-visualiser-core',
    'jnettop'
);
