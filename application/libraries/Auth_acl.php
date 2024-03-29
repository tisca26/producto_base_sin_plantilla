<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Auth_acl
{

    /**
     * Constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->config->load('auth_acl_config');
    }

    /**
     * Function to user authentication
     *
     * @access public
     * @param  string , string
     * @return bool, If loggin is successful return TRUE otherwise return FALSE
     */
    function loginsf($nick, $password)
    {
        log_message('INFO', '------- entramos a login sf de aut -------');
        $data = FALSE;

        //Verify super user data
        $su = $this->CI->config->item('su_name');
        $suid = $this->CI->config->item('su_id');

        //if token parameter hasn't default value
        $pwd = $this->CI->config->item('su_password');

        if (strcmp($nick, $su) == 0 and strcmp($password, $pwd) == 0) {
            $this->CI->session->set_userdata(array('logged_in' => true, 'user' => $su, 'id' => $suid, 'username' => $su));
            $this->logged_date();
            return TRUE;
        }

        //Verify common user data
        $query = $this->CI->db->where(array('NICKNAME' => $nick, 'ENABLE' => TRUE))->get('users');
        if ($query->num_rows() > 0) {
            $dbpassword = $query->row()->PASSWORD;
            //$password = sha1($password);
            //log_message('info', $password . ' - ' . $dbpassword);
            //if (strcmp($dbpassword, $password) == 0) {
            if (password_verify($password, $dbpassword)) {
                $fullname = $query->row()->NOMBRE . ' ' . $query->row()->APELLIDO;
                $this->CI->session->set_userdata(array('logged_in' => true, 'user' => $nick, 'id' => $query->row()->ID, 'username' => $fullname));
                $data = TRUE;
            }
        }
        if ($data) {
            $this->logged_date();
        }
        return $data;
    }

    /**
     * set in one session variable the current user logged
     *
     * @access public
     */
    function logged_date()
    {
        $this->CI->load->helper('date');
        $datestring = "%d / %m / %Y - %h:%i %a";
        $time = time();
        $date = mdate($datestring, $time);
        $this->CI->session->set_userdata('logged_date', $date);
    }

    /**
     * Logout current session
     *
     * @access public
     * @param
     * @return
     */
    function logout()
    {
        $this->CI->session->sess_destroy();
    }

    /**
     * Get acl of current user
     *
     * @access public
     * @param  string , string
     * @return array
     */
    function get_acl($userid)
    {
        $data = array();

        $sql = 'SELECT r.RESOURCE, acl.* FROM usersgroups ug inner join accesscontrollist acl on ug.GROUPID = acl.TARGETID and acl.TYPEID = 1 inner join resources r on r.ID = acl.RESOURCEID inner join groups g on g.ID = ug.GROUPID where ug.USERID = ? and g.ENABLE = 1 UNION SELECT r.RESOURCE, acl.* from accesscontrollist acl inner join resources r on r.ID = acl.RESOURCEID inner join users u on u.ID = acl.TARGETID where acl.TARGETID = ? and acl.TYPEID = 2 and u.ENABLE = 1';

        $query = $this->CI->db->query($sql, array($userid, $userid));
        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                $data[] = $row;
            }
        }

        return $data;
    }

    /**
     * Generate random password
     *
     * @access public
     * @return string, random password
     */
    function generate_password()
    {
        $str = ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890;
        $cad = "";
        for ($i = 0; $i < 12; $i++) {
            $cad .= substr($str, rand(0, 62), 1);
        }
        return $cad;
    }

}

?>