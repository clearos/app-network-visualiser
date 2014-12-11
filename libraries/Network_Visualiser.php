<?php

/**
 * Network visualiser class.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\network_visualiser;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('network_visualiser');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

// Classes
//--------

use \clearos\apps\base\Configuration_File as Configuration_File;
use \clearos\apps\base\File as File;
use \clearos\apps\base\Shell as Shell;
use \clearos\apps\network\Iface_Manager as Iface_Manager;
use \clearos\apps\web_proxy\Squid as Squid;

clearos_load_library('base/Configuration_File');
clearos_load_library('base/File');
clearos_load_library('base/Shell');
clearos_load_library('network/Iface_Manager');
clearos_load_library('web_proxy/Squid');

// Exceptions
//-----------

use \clearos\apps\base\Engine_Exception as Engine_Exception;
use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Engine_Exception');
clearos_load_library('base/Validation_Exception');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Network visualiser class.
 *
 * @category   apps
 * @package    network-visualiser
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/network_visualiser/
 */

class Network_Visualiser
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const CMD_JNETTOP = '/usr/bin/jnettop';
    const CMD_NV_SCAN = '/usr/sbin/nv_scan';
    const CMD_PS = '/bin/ps';
    const FILE_CONFIG = '/etc/clearos/network_visualiser.conf';
    const FILE_PREFIX = 'nv_';
    const FILE_LOG = 'nv_logfile';
    const REPORT_SIMPLE = 0;
    const REPORT_DETAILED = 1;
    const REPORT_GRAPHICAL = 2;

    protected $config = NULL;
    protected $is_loaded = FALSE;
    protected $byte_fields = array();

    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Network_Visualiser constructor.
     */

    function __construct()
    {
        $this->byte_fields = array('totalbps', 'totalbytes', 'srcbps', 'dstbps', 'srcbytes', 'dstbytes');
    }

    /** Send a plain text message.
     *
     * @return void
     *
     */

    function get_fields()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (! $this->is_loaded)
            $this->_load_config();

        try {
            $fields = array();

            if (!$this->is_loaded)
                $this->_load_config();

            $values = $this->config['fields'];
            $fields = explode(',', $values);
            return $fields;
        } catch (Exception $e) {
            // Return default entry
            return array('srcname');
        }
    }

    /**
     * Get the interface.
     *
     * @return string
     */

    function get_interface()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        return $this->config['interface'];
    }

    /**
     * Get the interval.
     *
     * @return int
     */

    function get_interval()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        return $this->config['interval'];
    }

    /**
     * Get the display.
     *
     * @return String
     */

    function get_display()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        return $this->config['display'];
    }

    /**
     * Get the report type.
     *
     * @return String
     */

    function get_report_type()
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        return $this->config['report'];
    }

    /**
     * Get maximum limits for interface.
     *
     * @param string  $interface  a valid NIC interface
     *
     * @return array
     */

    function get_max_limits($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!$this->is_loaded)
            $this->_load_config();

        $limits = array();
        $param = $this->config[$interface . '-max-limits'];
        if (preg_match("/(\d+)\|(\d+)/", $param, $match))
            $limits = array('upload' => $match[1], 'download' => $match[2]);
        return $limits;
    }

    /**
     * Starts a scan.
     *
     * @param string  $interface  a valid NIC interface
     * @param int     $interval   interval, in seconds
     * @param array   $filters    array of filters
     * @param boolean $force      force start of new scan (not recommended)
     *
     * @return string filename where out output data is stored
     * @throws Validation_Exception, Engine_Exception
     */

    function start_scan($interface, $interval, $filters, $force = FALSE)
    {
        clearos_profile(__METHOD__, __LINE__, "TODO start_scan");

        $log_file = $this->get_log_file($interface, $interval, $filters);

        $shell = new Shell();
        $args = "-i$interface -t$interval -f$log_file";
        foreach ($filters as $filter) 
            $args.= " -x$filter";

        $options = array('background' => TRUE);
        $retval = $shell->execute(self::CMD_NV_SCAN, $args, TRUE, $options);

        if ($retval != 0) {
            $errstr = $shell->get_last_output_line();
            throw new Engine_Exception($errstr, CLEAROS_ERROR);
        }
        return $log_file;
    }

    /**
     * Get log file name.
     *
     * @param string  $interface   a valid NIC interface
     * @param int     $interval    interval, in seconds
     * @param array   $filters     array of filters
     *
     * @return string log file where out output data is stored
     * @throws Validation_Exception, Engine_Exception
     */

    function get_log_file($interface, $interval, $filters)
    {
        clearos_profile(__METHOD__, __LINE__);

        return self::FILE_PREFIX . md5($interface . $interval . serialize($filters));
    }

    /**
     * Set the interval.
     *
     * @param int $interval interval
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_interval($interval)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interval($interval));

        $this->_set_parameter('interval', $interval);
    }

    /**
     * Set the inteface.
     *
     * @param string $interface interface
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_interface($interface)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_interface($interface));

        $this->_set_parameter('interface', $interface);
    }

    /**
     * Set the display.
     *
     * @param string $display display
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_display($display)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_display($display));

        $this->_set_parameter('display', $display);
    }

    /**
     * Set the report type.
     *
     * @param int $report report type
     *
     * @return void
     * @throws Validation_Exception
     */

    function set_report_type($report)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_report_type($report));

        $this->_set_parameter('report', $report);
    }

    /**
     * Returns the interval options.
     *
     * @return array
     */

    function get_interval_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            5 => 5 . ' ' . strtolower(lang('base_seconds')),
            10 => 10 . ' ' . strtolower(lang('base_seconds')),
            15 => 15 . ' ' . strtolower(lang('base_seconds')),
            30 => 30 . ' ' . strtolower(lang('base_seconds')),
            60 => 1 . ' ' . strtolower(lang('base_minute')),
            300 => 5 . ' ' . strtolower(lang('base_minutes')),
            600 => 10 . ' ' . strtolower(lang('base_minutes'))
        );
        return $options;
    }

    /**
     * Resets scan to start again.
     *
     * @return array
     */

    function reset_scan($ids)
    {
        clearos_profile(__METHOD__, __LINE__);
        $graph_options = $this->get_graph_options();
        $log_files = array();
        foreach ($ids as $id) {
            $meta = $graph_options[$id];
            if (in_array($meta['log_file'], $log_files)) {
                continue;
            }

            $log_file = $this->start_scan(
                $meta['interface'],
                $meta['interval'],
                $meta['filters']
            );
            array_push($log_files, $meta['log_file']);
        }
    }

    /**
     * Returns the graph options.
     *
     * @return array
     */

    function get_graph_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array();

        // Grab all WAN and LAN interfaces

        $iface_manager = new Iface_Manager();

        $network_interfaces = $iface_manager->get_interface_details();

        foreach ($network_interfaces as $interface => $details) {
            if ($details['role'] == Iface_Manager::EXTERNAL_ROLE) {

                $limits = $this->get_max_limits($interface); 

                if (!empty($limits)) {
                    $options[$interface . '-usage-up'] = array (
                        'interface' => $interface,
                        'interval' => $this->get_interval(),
                        'type' => 'usage',
                        'chart' => 'pie',
                        'direction' => 'up',
                        'filters' => NULL,
                        'log_file' => $this->get_log_file($interface, $this->get_interval(), NULL),
                        'options' => array('hole' => TRUE, 'legend' => FALSE, 'max' => (int)$limits['upload']),
                        'title' => $interface . ' - Total Upload Usage'
                    );
                    $options[$interface . '-usage-dn'] = array (
                        'interface' => $interface,
                        'interval' => $this->get_interval(),
                        'type' => 'usage',
                        'chart' => 'pie',
                        'direction' => 'dn',
                        'filters' => NULL,
                        'log_file' => $this->get_log_file($interface, $this->get_interval(), NULL),
                        'options' => array('hole' => TRUE, 'legend' => FALSE, 'max' => (int)$limits['download']),
                        'title' => $interface . ' - Total Download Usage'
                    );
                }

                $options[$interface . '-dn'] = array (
                    'interface' => $interface,
                    'interval' => $this->get_interval(),
                    'type' => 'tracking',
                    'chart' => 'pie',
                    'direction' => 'dn',
                    'filters' => NULL,
                    'log_file' => $this->get_log_file($interface, $this->get_interval(), NULL),
                    'title' => $interface . ' - WAN Download'
                );
                // Upload can share the same log file as download...just parse the fields
                // according to direction
                $options[$interface . '-up'] = array (
                    'interface' => $interface,
                    'interval' => $this->get_interval(),
                    'type' => 'tracking',
                    'chart' => 'pie',
                    'direction' => 'up',
                    'filters' => NULL,
                    'log_file' => $this->get_log_file($interface, $this->get_interval(), NULL),
                    'title' => $interface . ' - WAN Upload'
                );


                // Check for proxy/filter
                if (clearos_library_installed('content_filter/Dans_Guardian')) {
                    clearos_load_library('content_filter/Dans_Guardian');
                    clearos_load_language('content_filter');
                    $dg = new Dans_Guardian();
                    $options[$interface . '-filter'] = array(
                        'interface' => $interface,
                        'interval' => $this->get_interval(),
                        'filters' => NULL,
                        'log_file' => $this->get_log_file($interface, $this->get_interval(), NULL),
                        'chart' => 'pie',
                        'title' => lang('content_filter_content_filter')
                    );
                } else if (clearos_library_installed('web_proxy/Squid')) {
                    clearos_load_library('web_proxy/Squid');
                    clearos_load_language('web_proxy');
                    $squid = new Squid();
                    $options[$interface . '-proxy'] = array(
                        'interface' => $interface,
                        'interval' => $this->get_interval(),
                        'filters' => NULL,
                        'log_file' => $this->get_log_file($interface, $this->get_interval(), NULL),
                        'chart' => 'pie',
                        'title' => lang('web_proxy_web_proxy')
                    );
                }
            } else {
                $options[$interface] = array (
                    'interface' => $interface,
                    'interval' => $this->get_get_interval(),
                    'chart' => 'pie',
                    'title' => 'LAN Traffic'
                );
            }
        }

        
        return $options;
    }

    /**
     * Returns the report options.
     *
     * @return array
     */

    function get_report_type_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            self::REPORT_SIMPLE => lang('network_visualiser_report_simple'),
            self::REPORT_DETAILED => lang('network_visualiser_report_detailed'),
            self::REPORT_GRAPHICAL => lang('network_visualiser_report_graphical')
        );
        return $options;
    }

    /**
     * Returns the display options.
     *
     * @return array
     */

    function get_interface_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();

        $network_interface = $iface_manager->get_interface_details();

        foreach ($network_interface as $interface => $details)
            $options[$interface] = $interface;

        return $options;
    }

    /**
     * Returns the interval options.
     *
     * @return array
     */

    function get_display_options()
    {
        clearos_profile(__METHOD__, __LINE__);

        $options = array(
            'totalbps' => lang('network_bandwidth'),
            'totalbytes' => lang('network_visualiser_total_transfer')
        );

        return $options;
    }

    /**
     * Returns the network visualiser data.
     * @param array $log_files log files to pull data from
     *
     * @return array
     */

    function get_data($log_files)
    {
        clearos_profile(__METHOD__, __LINE__);

        $mydata = array();

        // Fields are always the same...pull out of loop
        $fields = $this->get_fields();
        foreach ($log_files as $filename) {

            // First check in progress file
            $file = new File(CLEAROS_TEMP_DIR . "/" . $filename . "_in_progress");

            if (!$file->exists()) {
                $file = new File(CLEAROS_TEMP_DIR . "/" . $filename);
                if (!$file->exists()) {
                    $mydata[$filename] = array(
                        'code' => 1,
                        'errmsg' => lang('base_nothing_to_report')
                    );
                    continue;
                }
            }

            $timestamp = time();

            if (($handle = fopen(CLEAROS_TEMP_DIR . "/" . basename($file->get_filename()), "r")) !== FALSE) {
                $line = 0;
                $log_file_content = array();
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (preg_match('/^Could not.*$/', $data[0])) {
                        continue;
                    } else if (preg_match('/^interval.*/', $data[0])) {
                        $interval = preg_replace('/interval=/', '', $data[0]);
                        continue;
                    } else if (preg_match('/^stop.*/', $data[0])) {
                        $stop = preg_replace('/stop=/', '', $data[0]);
                        continue;
                    }
                    $index = 0;
                    foreach ($fields as $field) {
                        if (in_array($field, $this->byte_fields))
                            $typed_data = (int) $data[$index] * 8;
                        else
                            $typed_data = $data[$index];

                        $log_file_content[$line][$field] = $typed_data;
                        $index++;
                    }
                    $line++;
                }
                fclose($handle);
            }

            $mydata[$filename] = array(
                'timestamp' => $timestamp,
                'stop' => $stop,
                'next_update' => ($stop - $timestamp) / $interval * 100,
                'code' => (empty($log_file_content) ? 1 : 0),
                'errmsg' => (empty($log_file_content) ? lang('base_nothing_to_report') : ''),
                'data' => $log_file_content
            );
        }
            
        return $mydata;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Loads configuration files.
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _load_config()
    {
        clearos_profile(__METHOD__, __LINE__);

        $configfile = new Configuration_File(self::FILE_CONFIG);

        $this->config = $configfile->load();

        $this->is_loaded = TRUE;
    }

    /**
     * Generic set routine.
     *
     * @param string $key   key name
     * @param string $value value for the key
     *
     * @return  void
     * @throws Engine_Exception
     */

    function _set_parameter($key, $value)
    {
        clearos_profile(__METHOD__, __LINE__);

        $file = new File(self::FILE_CONFIG, TRUE);
        $match = $file->replace_lines("/^$key\s*=\s*/", "$key=$value\n");

        if (!$match)
            $file->add_lines("$key=$value\n");

        $this->is_loaded = FALSE;
    }

    ///////////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N   R O U T I N E S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Validation routine for interval.
     *
     * @param string $interval interval
     *
     * @return mixed void if interval is valid, errmsg otherwise
     */

    public function validate_interval($interval)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (is_nan($interval) || $interval < 5 ||  $interval > 3600)
            return lang('network_visualiser_interval_is_invalid');
    }

    /**
     * Validation routine for interface.
     *
     * @param string $iface interface
     *
     * @return mixed void if interface is valid, errmsg otherwise
     */

    public function validate_interface($iface)
    {
        clearos_profile(__METHOD__, __LINE__);

        $iface_manager = new Iface_Manager();

        $ifaces = $iface_manager->get_interfaces();

        if (! in_array($iface, $ifaces))
            return lang('network_network_interface_invalid');
    }

    /**
     * Validation routine for display.
     *
     * @param int $display display
     *
     * @return mixed void if display is valid, errmsg otherwise
     */

    public function validate_display($display)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (FALSE)
            return lang('network_visualiser_display_invalid');
    }

    /**
     * Validation routine for report.
     *
     * @param int $report report
     *
     * @return mixed void if report is valid, errmsg otherwise
     */

    public function validate_report_type($report)
    {
        clearos_profile(__METHOD__, __LINE__);

        if ($report != self::REPORT_SIMPLE && $report != self::REPORT_DETAILED && $report != self::REPORT_GRAPHICAL)
            return lang('network_visualiser_report_invalid');
    }
}
