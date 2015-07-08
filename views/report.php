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
 * @link       http://www.clearfoundation.com/docs/developer/apps/flexshare/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network_visualiser\Network_Visualiser as Network_Visualiser;

$this->lang->load('base');
$this->lang->load('network');
$this->lang->load('network_visualiser');

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

if ($report_type == Network_Visualiser::REPORT_DETAILED)
    $anchors = array(
        anchor_custom('/app/network_visualiser/simple', lang('base_back'))
    );
else
    $anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

if ($report_type == Network_Visualiser::REPORT_DETAILED) {
    $headers = array(
        lang('network_source'),
        lang('network_source_port'),
        lang('network_protocol'),
        lang('network_destination'),
        lang('network_destination_port'),
        ($display == 'totalbps' ? lang('network_bandwidth') : lang('network_visualiser_total_transfer'))
    );
} else {
    $headers = array(
        lang('network_source'),
        lang('network_source_port'),
        lang('network_destination'),
        ($display == 'totalbps' ? lang('network_bandwidth') : lang('network_visualiser_total_transfer'))
    );
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open('network_visualiser_report');
;

if ($report_type == Network_Visualiser::REPORT_SIMPLE) {
    echo summary_table(
        lang('network_visualiser_traffic_summary'),
        $anchors,
        $headers,
        NULL,
        array(
            'id' => 'report',
            'no_action' => TRUE,
            'empty_table_message' => loading('normal', lang('base_loading')),
            'sorting-type' => array(
                NULL,
                'int',
                NULL,
                'title-numeric'
            )
        )
    );
} else if ($report_type == Network_Visualiser::REPORT_DETAILED) {
    echo summary_table(
        lang('network_visualiser_traffic_summary'),
        $anchors,
        $headers,
        NULL,
        array(
            'id' => 'report',
            'no_action' => TRUE,
            'empty_table_message' => loading('normal', lang('base_loading')),
            'sorting-type' => array(
                NULL,
                'int',
                NULL,
                NULL,
                'int',
                'title-numeric'
            )
        )
    );
} else if ($report_type == Network_Visualiser::REPORT_GRAPHICAL) {
    echo box_open(
        lang('network_visualiser_top_users') . ' - ' . 
        ($display == 'totalbps' ? lang('network_visualiser_bandwidth') : lang('network_visualiser_total_transfer'))
    );
    echo box_content("<div id='clear-chart' style='height:450px; width:100%;'></div>");
    echo box_footer('report-footer', '', array('loading' => TRUE));
    echo box_close();
}

echo form_close();
echo "<input id='report_display' type='hidden' value='$display'>";
echo "<input id='report_type' type='hidden' value='$report_type'>";
