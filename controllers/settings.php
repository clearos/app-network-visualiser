<?php

/**
 * Network visualiser settings controller.
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

use \clearos\apps\network_visualiser\Network_Visualiser as Network_Visualiser;
use \Exception as Exception;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network visualiser settings controller.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Settings extends ClearOS_Controller
{
    /**
     * Network Visualiser settings default controller
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

        // Set validation rules
        //---------------------
         
        $this->form_validation->set_policy('interval', 'network_visualiser/Network_Visualiser', 'validate_interval', FALSE);
        $this->form_validation->set_policy('display', 'network_visualiser/Network_Visualiser', 'validate_display', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if (($this->input->post('submit') && $form_ok)) {
            try {
                $this->network_visualiser->set_interval($this->input->post('interval'));
                $this->network_visualiser->set_display($this->input->post('display'));
                $this->page->set_status_updated();

                redirect('/network_visualiser');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['interval'] = $this->network_visualiser->get_interval();
            $data['display'] = $this->network_visualiser->get_display();
            $data['interval_options'] = $this->network_visualiser->get_interval_options();
            $data['display_options'] = $this->network_visualiser->get_display_options();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('network_visualiser/settings', $data, lang('network_visualiser_app_name'));
    }
}
