<?php

/**
 * Nework visualiser controller.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage views
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('network');
$this->lang->load('network_visualiser');

echo row_open();

foreach ($graph_options['pie'] as $id => $meta) {
    echo column_open(6);
    echo chart_container(
        $meta['title'] . "<div class='pull-right'>" .
        "<a href='#' class='nv-play-pause' id='pie-$id-play'><i class='fa fa-play'></i></a>" .
        "</div>",
        'pie-' . $id,
        array('id' => 'pie-' . $id . '-container', 'class' => 'nv-pie-chart', 'chart-size' => 'medium', 'loading' => TRUE)
    );
    echo column_close();
}

echo row_close();
