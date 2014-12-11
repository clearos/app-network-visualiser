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

foreach ($graph_options as $id => $meta) {
    echo column_open(6);
    $footer = progress_bar('progress-' . $id, array('height' => 1, 'no_animation' => TRUE));
    echo chart_container(
        $meta['title'] . "<div class='pull-right'>" .
        "<a href='#' class='nv-play-pause' id='$id-play'><i class='fa fa-play'></i></a>" .
        "</div>",
        $id,
        array(
            'id' => $id . '-container',
            'class' => 'nv-chart',
            'chart-size' => 'medium',
            'loading' => TRUE,
            'footer' => $footer,
            'data' => array('filename' => $meta['filename'])
        )
    );
    echo column_close();
}

echo row_close();
