<?php

/**
 * Network visualiser controller.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage controllers
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
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network_visualiser\Network_Visualiser as Net_Vis;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network visualiser controller.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Network_Visualiser extends ClearOS_Controller
{
    /**
     * Network Visualiser summary view.
     *
     * @return view
     */

    function index()
    {
        clearos_profile(__METHOD__, __LINE__);

        // Load dependencies
        //------------------

        $this->load->library('network_visualiser/Network_Visualiser');
        $this->lang->load('network_visualiser');

        // Add setting link to breadcrumb trail
        $breadcrumb_links = array(
            'settings' => array('url' => '/app/network_visualiser/settings', 'tag' => lang('base_settings'))
        );

        // Load views
        //-----------

        $data['graph_options'] = $this->network_visualiser->get_graph_options();

        $log_files = array();
        // Array for log files is lame....but it fixes a race
        // condition forking off too many nv_scan processes
        // for scans that can use the same log file
        foreach($data['graph_options'] as $id => $meta) {
            if (in_array($meta['log_file'], $log_files)) {
                $data['graph_options'][$id]['filename'] = $log_file;
                continue;
            }
            // OK..we need to start a scan for these parameters
            $log_file = $this->network_visualiser->start_scan(
                $meta['interface'],
                $meta['interval'],
                $meta['filters']
            );
            $data['graph_options'][$id]['filename'] = $log_file;
            array_push($log_files, $meta['log_file']);
        }

        $this->page->view_form(
            'network_visualiser/report',
            $data,
            lang('network_visualiser_app_name'),
            array('breadcrumb_links' => $breadcrumb_links)
        );
    }
}
