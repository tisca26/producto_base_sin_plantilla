<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * This library is used for generate dynamic menu trees
 */
class Menu_manager
{

    /**
     * Constructor
     *
     * @access public
     */
    function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Generate menu site
     */
    function generate_menu()
    {
        $tree = array();

        $CI = get_instance();
        $CI->load->model('menu_model');
        $su = $CI->config->item('su_name');
        $suid = $CI->config->item('su_id');

        if ($CI->session->userdata('user') != $su or $CI->session->userdata('id') != $suid) {
            $tree = $CI->menu_model->generateallActiveTreeArrayByUser($CI->session->userdata('id'));
        } else {
            $tree = $CI->menu_model->generateallActiveTreeArray();
        }

        $output = "";

        if (!empty($tree)) {
            $output = '<!-- INICIO GENERACION DE MENUS -->';
            $this->generateMenuByLevel($tree, $output);
            $output .= '<!-- FIN GENERACION DE MENUS -->';
        }
        return $output;
    }

    /**
     * Generate menu by levels
     *
     * @access public
     * @param array , List of menu entries
     * @param string , return html script of menu
     * @param int , depth level in menu tree
     * @param int , return 1 or 0 if exist childs to level returned
     * @param int , level
     */
    function generateMenuByLevel($tree, &$output, $parent = 0, &$flag = 0, &$level = 1)
    {
        if (isset($tree[$parent])) {
            foreach ($tree[$parent] as $row) {
                $output .= "<li>";

                if (isset($tree[$row->id])) { // revisa si tiene hijos, avanza hasta llegar a la hoja
                    $aux = "";
                    $flag1 = 0;
                    $level_aux = $level;
                    $level_aux++;
                    $this->generateMenuByLevel($tree, $aux, $row->id, $flag1, $level_aux);

                    if ($flag1 == 1) {
                        $flag = 1;
                        $icono = $level == 1 ? '<i class=" ' . $row->icon . '"></i>' : '';
                        $arrow = '';
                        if ($flag1 == 1) {
                            $arrow = '<span class="fa arrow"></span>';
                        }
                        $contenido_name = $level == 1 ? '<span class="nav-label">' . $row->name . '</span>' : $row->name;
                        $content = '<a>' . $icono . '</i>' . $contenido_name . $arrow . '</a>';
                        if (!empty($row->page_uri))
                            $content = anchor($row->page_uri, $row->name . $arrow);

                        $output .= $content;
                        $ul_level = $this->ulLevelName($level);
                        $output .= "<ul class='nav nav-" . $ul_level . "-level collapse'>";
                        $output .= $aux;
                        $output .= "</ul>";
                    } elseif (!empty($row->page_uri)) {
                        $arrow = '';
                        if ($flag1 == 1) {
                            $arrow = '<span class="fa arrow"></span>';
                        }
                        $icono = $level == 1 ? '<i class=" ' . $row->icon . '"></i>' : '';
                        $contenido_name = $level == 1 ? '<span class="nav-label">' . $row->name . '</span>' : $row->name;
                        $output .= '<a href="' . base_url() . $row->page_uri . '" >' . $icono . $contenido_name . $arrow . '</a>';
                        $flag = 1;
                        $level--;
                    }
                } elseif (!empty($row->page_uri)) { // genera contenido del <li> si es que tiene enlace (recurso), YA LLEGAMOS A LA HOJA
                    $icono = $level == 1 ? '<i class=" ' . $row->icon . '"></i>' : ''; // se supone que no va a pasar
                    $contenido_name = $level == 1 ? '<span class="nav-label">' . $row->name . '</span>' : $row->name;
                    $output .= '<a href="' . base_url() . $row->page_uri . '">' . $icono . $contenido_name . '</a>';
                    $flag = 1;
                    if ($flag1 == 0) {
                        $level_aux = $level;
                    }
                }
                $output .= "</li>";
            }
        }
    }

    private function ulLevelName($level)
    {
        $value = '';
        $level++;
        switch ($level) {
            case 1:
                break;
            case 2:
                $value = 'second';
                break;
            case 3:
                $value = 'third';
                break;
            case 4:
                $value = 'fourth';
                break;
            case 5:
                $value = 'fifth';
                break;
            case 6:
                $value = 'sixth';
                break;
            default:
                break;
        }
        return $value;
    }

}

?>
