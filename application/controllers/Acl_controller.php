<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Acl_controller extends CI_Controller
{

    private $read_list = array();
    private $insert_list = array();
    private $update_list = array();
    private $delete_list = array();

    /**
     * Constructor
     *
     * @access public
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     * Define if user logged have permission to current resources
     *
     * @access public
     * @param
     * @return
     */
    function check_access()
    {
        if (!$this->session->userdata('logged_in')) {
            $this->session->set_userdata('intento_url', current_url());
            redirect('admin/');
        } else {
            $this->session->set_userdata('intento_url', '');
            $su = $this->config->item('su_name');
            $suid = $this->config->item('su_id');
            if ($this->session->userdata('user') != $su or $this->session->userdata('id') != $suid) {
                $acl = $this->auth_acl->get_acl($this->session->userdata('id'));
                $route = $this->router->class . '/' . $this->router->method;

                $valid = FALSE;

                //In case of "controller_name/action_name" resource format
                foreach ($acl as $item) {
                    if (strcasecmp(trim($item->RESOURCE), $route) == 0) {
                        if (in_array($this->router->method, $this->read_list))
                            $valid = ($item->R != 0);
                        if (in_array($this->router->method, $this->insert_list))
                            $valid = ($item->I != 0);
                        if (in_array($this->router->method, $this->update_list))
                            $valid = ($item->U != 0);
                        if (in_array($this->router->method, $this->delete_list))
                            $valid = ($item->D != 0);
                        if ($valid)
                            break;
                    }
                }

                if (!$valid) {
                    $route = $this->router->class;

                    //In case of "controller_name" resource format
                    foreach ($acl as $item) {
                        if (strcasecmp(trim($item->RESOURCE), $route) == 0) {
                            if (in_array($this->router->method, $this->read_list))
                                $valid = ($item->R != 0);
                            if (in_array($this->router->method, $this->insert_list))
                                $valid = ($item->I != 0);
                            if (in_array($this->router->method, $this->update_list))
                                $valid = ($item->U != 0);
                            if (in_array($this->router->method, $this->delete_list))
                                $valid = ($item->D != 0);
                            if ($valid)
                                break;
                        }
                    }
                }

                if (!$valid) {
                    redirect('messages/denied');
                }
            }
        }
    }

    /**
     * Set function name list for read propouses
     *
     * @access public
     */
    protected function set_read_list($list)
    {
        $this->read_list = $list;
    }

    /**
     * Set function name list for insert propouses
     *
     * @access public
     */
    protected function set_insert_list($list)
    {
        $this->insert_list = $list;
    }

    /**
     * Set function name list for update propouses
     *
     * @access public
     */
    protected function set_update_list($list)
    {
        $this->update_list = $list;
    }

    /**
     * Set function name list for delete propouses
     *
     * @access public
     */
    protected function set_delete_list($list)
    {
        $this->delete_list = $list;
    }

    public function validaFormatoFecha($date)
    {
        //return preg_match("/[0-9]{2}\/[0-9]{2}\/[0-9]{4}/", $date);
        return preg_match("/[0-9]{4}-[0-9]{2}-[0-9]{2}/", $date);
    }

    /*
     * Ingresa fechas dd/mm/yyyy
     * Regresa fechas yyyy-mm-dd
     */

    public function cambioFormatoFecha($date)
    {
        $dt = DateTime::createFromFormat('d/m/Y', $date);
        return $dt->format('Y-m-d');
    }

    public function trimArreglo($data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = trim($value);
        }
        return $data;
    }

    public function arrayToObject($data)
    {
        $object = new stdClass();
        $object = json_decode(json_encode($data), FALSE);
        return $object;
    }

    public function cambiaFechaMySQLOEN($date)
    {
        $parts = explode('-', $date);
        $new_date = "$parts[2]/$parts[1]/$parts[0]";
        return $new_date;
    }

    /*
     * 
      $startDate = '2015-01-31'; // select date in Y-m-d format
      $nMonths = 3; // choose how many months you want to move ahead
      $final = endCycle($startDate, $nMonths); // output: 2015-04-30
     * 
     */

    public function add_months($months, DateTime $dateObject)
    {
        $next = new DateTime($dateObject->format('Y-m-d'));
        $next->modify('last day of +' . $months . ' month');

        if ($dateObject->format('d') > $next->format('d')) {
            return $dateObject->diff($next);
        } else {
            return new DateInterval('P' . $months . 'M');
        }
    }

    public function endCycle($d1, $months)
    {
        $date = new DateTime($d1);

        // call second function to add the months
        $newDate = $date->add($this->add_months($months, $date));

        // goes back 1 day from date, remove if you want same day of month
        //$newDate->sub(new DateInterval('P1D')); 
        //formats final date to Y-m-d form
        $dateReturned = $newDate->format('Y-m-d');

        return $dateReturned;
        //return $newDate;
    }

    function addMonths($date, $months)
    {
        $years = floor(abs($months / 12));
        $leap = 29 <= $date->format('d');
        $m = 12 * (0 <= $months ? 1 : -1);
        for ($a = 1; $a < $years; ++$a) {
            $date = addMonths($date, $m);
        }
        $months -= ($a - 1) * $m;

        $init = clone $date;
        if (0 != $months) {
            $modifier = $months . ' months';

            $date->modify($modifier);
            if ($date->format('m') % 12 != (12 + $months + $init->format('m')) % 12) {
                $day = $date->format('d');
                $init->modify("-{$day} days");
            }
            $init->modify($modifier);
        }

        $y = $init->format('Y');
        if ($leap && ($y % 4) == 0 && ($y % 100) != 0 && 28 == $init->format('d')) {
            $init->modify('+1 day');
        }
        return $init;
    }

    function addYears($date, $years)
    {
        return addMonths($date, 12 * $years);
    }

}

?>