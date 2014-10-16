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

echo row_open();
echo column_open(6);
echo chart_container(lang('network_visualiser_top_users'), 'nv-top-ten', array('id' => 'nv-top-ten-container', 'chart-size' => 'medium', 'loading' => TRUE));
echo column_close();
echo row_close();

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('network_source'),
    lang('network_source_port'),
    lang('network_protocol'),
    lang('network_destination'),
    lang('network_destination_port'),
    ($display == 'totalbps' ? lang('network_bandwidth') : lang('network_visualiser_total_transfer'))
);

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

//echo form_open('network_visualiser_report');
//echo form_header(lang('network_visualiser_report_graphical'));

/*
if ($report_type == Network_Visualiser::REPORT_SIMPLE) {
    echo summary_table(
        lang('network_visualiser_traffic_summary'),
        $anchors,
        $headers,
        NULL,
        array(
            'id' => 'report',
            'no_action' => TRUE,
            'sorting-type' => array(
                NULL,
                'int',
                NULL,
                'title-numeric'
            )
        )
    );
} else if ($report_type == Network_Visualiser::REPORT_DETAILED) {
*/
    echo summary_table(
        lang('network_visualiser_traffic_summary'),
        $anchors,
        $headers,
        NULL,
        array(
            'id' => 'report',
            'no_action' => TRUE,
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
//} else if ($report_type == Network_Visualiser::REPORT_GRAPHICAL) {
//}

//echo form_footer();
//echo form_close();
echo "<input id='report_display' type='hidden' value='$display'>";
echo "<input id='report_type' type='hidden' value='$report_type'>";
