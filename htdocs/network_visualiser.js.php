<?php

/**
 * Javascript helper for Nework Visualiser.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage javascript
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

clearos_load_language('base');
clearos_load_language('network_visualiser');

header('Content-Type: application/x-javascript');

?>

var display = 'totalbps';
var report_simple = 0;
var report_detailed = 1;
var report_graphical = 2;
var mapping = JSON.stringify('');
var lang_bits = '<?php echo lang('base_bits'); ?>';
var lang_kilobits = '<?php echo lang('base_kilobits'); ?>';
var lang_megabits = '<?php echo lang('base_megabits'); ?>';
var lang_gigabits = '<?php echo lang('base_gigabits'); ?>';
var lang_kilobytes = '<?php echo lang('base_kilobytes'); ?>';
var lang_kilobytes_per_second = '<?php echo lang('base_kilobytes_per_second'); ?>';
var lang_next_update = '<?php echo lang('base_next_update'); ?>';

$(document).ready(function() {
    var options = new Object();
    options.id = 'next-update';
    options.no_animation = true;
    if ($('#ns_settings_form').length == 0)
        clearos_add_sidebar_pair(lang_next_update, clearos_progress_bar(100, options));
    get_mapped_devices();
    get_traffic_data();
    $('.nv-play-pause').on('click', function (e) {
        e.preventDefault();
        if ($('#' + this.id + ' i').hasClass('fa-pause')) {
            $('#' + this.id + ' i').removeClass('fa-pause');
            $('#' + this.id + ' i').addClass('fa-play');
        } else {
            $('#' + this.id + ' i').removeClass('fa-play');
            $('#' + this.id + ' i').addClass('fa-pause');
        }
    });
});

function get_mapped_devices() {
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/network_map/mapped/get_mapped_devices',
        success: function(json) {
            if (json.error_code == 0)
                mapping = json.map;
        }
    });
}

function get_traffic_data() {
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/network_visualiser/ajax/get_traffic_data',
        success: function(json) {
            if (json.code != 0) {
                setTimeout('get_traffic_data()', 900);
                return;
            }
            if (json.timestamp > json.stop) {
                pie_graph_data(json);
                clearos_set_progress_bar('next-update', 100); 
                reset_scan();
            } else {
                clearos_set_progress_bar('next-update', json.next_update); 
            }
            setTimeout('get_traffic_data()', 900);
            return;
/*
            table_report.fnClearTable();
            for (var index = 0 ; index < json.data.length; index++) {
                if (display == 'totalbps') {
                    if (isNaN(json.data[index].totalbps) || json.data[index].totalbps == 0)
                            continue;
                    field = '<span title="' + json.data[index].totalbps + '"></span>' + format_number(json.data[index].totalbps);
                } else {
                    if (isNaN(json.data[index].totalbytes) || json.data[index].totalbytes == 0)
                        continue;
                    field = '<span title="' + json.data[index].totalbytes + '"></span>' + format_number(json.data[index].totalbytes);
                }
                if ($('#report_type').val() == report_simple) {
    	    		table_report.fnAddData([
    	    		    json.data[index].src,
    	    		    json.data[index].srcport,
    	    		    json.data[index].dst,
    	    		    field
    	    		]);
    	    	} else if ($('#report_type').val() == report_detailed) {
    	    		table_report.fnAddData([
    			        json.data[index].src,
    			        json.data[index].srcport,
    	                json.data[index].proto,
    	    		    json.data[index].dst,
    	                json.data[index].dstport,
    	    		    field
    	    		]);
                }
            }

            table_report.fnAdjustColumnSizing();
*/
        },
        error: function(xhr, text, err) {
        }
    });
}

function reset_scan() {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token'),
        url: '/app/network_visualiser/ajax/reset_scan',
        success: function(json) {
            if (json.code != 0) {
                alert(json.errmsg);
                return;
            }
        },
        error: function(xhr, text, err) {
            // Don't display any errors if ajax request was aborted due to page redirect/reload
            if (xhr['abort'] == undefined)
                alert(xhr.responseText.toString());
        }
    });
}

function format_number (bytes) {
    bits = bytes * 8;

    if (display == 'totalbytes') {
        var sizes = [
            lang_bits,
            lang_kilobits,
            lang_megabits,
            lang_gigabits
        ];
    } else {
        var sizes = [
            lang_bits_per_second,
            lang_kilobits_per_second,
            lang_megabits_per_second,
            lang_gigabits_per_second
        ];
    }
    var i = parseInt(Math.floor(Math.log(bits) / Math.log(1024)));
    return ((i == 0)? (bits / Math.pow(1024, i)) : (bits / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
};

function pie_graph_data(json) {
    var datapoints = new Array();
    $('.nv-pie-chart').each(function() {
        if ($('#' + this.id + '-play i').hasClass('fa-pause')) {
            // Don't update graph...paused
            return;
        }
        chart_data = json.data[this.id];
        for (var index = 0 ; index < chart_data.length; index++) {
            if (display == 'totalbps')
                total = chart_data[index].totalbps; 
            else
                total = chart_data[index].totalbytes; 
            if (total == undefined || isNaN(total) || total == 0)
                continue;
            if (!(chart_data[index].src in mapping)) {
                if (datapoints[chart_data[index].src] == undefined)
                    datapoints[chart_data[index].src] = parseInt(total);
                else 
                    datapoints[chart_data[index].src] += parseInt(total);
            } else {
                if (datapoints[mapping[chart_data[index].src].nickname] == undefined)
                    datapoints[mapping[chart_data[index].src].nickname] = parseInt(total);
                else 
                    datapoints[mapping[chart_data[index].src].nickname] += parseInt(total);
            }

        }
        var data = new Array();
        counter = 0;
        for (entry in datapoints) {
            data[counter] = [entry,datapoints[entry]]; 
            if (counter >= 9)
                break;
            counter++;
        }

        var options = new Object;
        clearos_pie_chart(this.id, data, options);
        clearos_loaded(this.id + '-container');
    });
/*

    counter = 0;
    for (entry in datapoints) {
        data[counter] = [entry,datapoints[entry]]; 
        if (counter >= 9)
            break;
        counter++;
    }

    var options = new Object;
    clearos_pie_chart('nv-top-ten', data, options);
    clearos_loaded('nv-top-ten-container');
*/
}

<?php
// vim: ts=4 syntax=javascript
?>
