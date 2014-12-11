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
var graph_options = JSON.stringify('');
var lang_warning = '<?php echo lang('base_warning'); ?>';
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
    get_mapped_devices();
    get_data();
    get_graph_options();
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

function get_data() {
    // Get distinct list of filenames from chart containers
    var log_files = [];
    $('.theme-chart-container').each(function (index) {
        if ($.inArray($(this).data('filename'), log_files) === -1) 
            log_files.push($(this).data('filename'));
    });
    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&log_files=' + JSON.stringify(log_files),
        url: '/app/network_visualiser/ajax/get_data',
        success: function(json) {
            var reset_ids = [];
            // Pie Graphs
            $('.nv-chart').each(function() {
                graphd = json[$(this).data('filename')];
                if (graphd.code != 0 && graphd.timestamp <= graphd.stop) {
                    clearos_set_progress_bar('progress-' + this.id, graphd.next_update); 
                } else if (graphd.code == 0) {
                    clearos_set_progress_bar('progress-' + this.id, 0); 
                    update_graph(this.id, graphd.data);
                    reset_ids.push(this.id);
                } else {
                    reset_ids.push(this.id);
                }
            }); 
            setTimeout('get_data()', 900);
            if (reset_ids.length > 0)
                reset_scan(reset_ids);
        },
        error: function(xhr, text, err) {
        }
    });
}

function get_graph_options() {
    $.ajax({
        type: 'GET',
        dataType: 'json',
        url: '/app/network_visualiser/ajax/get_graph_options',
        success: function(json) {
            if (json.code != 0) {
                clearos_dialog_box('unable_to_fetch_graph_options', lang_warning, json.errmsg);
                return;
            }
            graph_options = json.options;
        },
        error: function(xhr, text, err) {
            clearos_dialog_box('unable_to_fetch_graph_options', lang_warning, err);
        }
    });
}

function reset_scan(reset_ids) {
    $.ajax({
        type: 'POST',
        dataType: 'json',
        data: 'ci_csrf_token=' + $.cookie('ci_csrf_token') + '&ids=' + JSON.stringify(reset_ids),
        url: '/app/network_visualiser/ajax/reset_scan',
        success: function(json) {
            if (json.code != 0) {
                alert(json.errmsg);
                return;
            }
            $.each(reset_ids, function(index, id) {
                clearos_set_progress_bar('progress-' + id, 100); 
            });
        },
        error: function(xhr, text, err) {
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

function update_graph(id, chart_data, display, options) {
    var datapoints = new Array();

console.log(graph_options);
    if ($('#' + id + '-play i').hasClass('fa-pause')) {
        // Don't update graph...paused
        return;
    }

    display = 'totalbps';

    if (graph_options[id].type == 'usage')
        display = 'totalbps';

    // Loop through all data and group by IP.
    for (var index = 0 ; index < chart_data.length; index++) {
    //    if (index == 0)
     //       console.log(chart_data[index]);
        if (graph_options[id].type == 'usage') {
            if (index == 0) {
                datapoints['used'] = 0;
                datapoints['avail'] = 0;
            }
            if (graph_options[id].direction == 'dn') {
                if (chart_data[index].dstbps != undefined && !isNaN(chart_data[index].dstbps))
                    datapoints['used'] += chart_data[index].dstbps;
            } else {
                if (chart_data[index].srcbps != undefined && !isNaN(chart_data[index].srcbps))
                    datapoints['used'] += parseInt(chart_data[index].srcbps);
            }
            continue;
        }
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

    console.log( datapoints['used']);
    var data = new Array();
    if (graph_options[id].type == 'usage') {
        data[0] = ['Used', datapoints['used']]; 
        data[1] = ['Avail', parseInt(graph_options[id].options['max'] * 1000) - datapoints['used']]; 
    } else {

        counter = 0;
        for (entry in datapoints) {
            data[counter] = [entry,datapoints[entry]]; 
            if (counter >= 9)
                break;
            counter++;
        }
    }

    var options = new Object;
    if (graph_options[id].type == 'usage')
        options = { 
            pie: {
                inner_radius: 0.5
            },
            series: {
                color: {
                    0: '#c2510f', // Used
                    1: '#608921'// Avail
                }
            }
        };
    clearos_pie_chart(id, data, options);
    clearos_loaded(id + '-container');
}

<?php
// vim: ts=4 syntax=javascript
?>
