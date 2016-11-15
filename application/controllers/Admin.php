<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Admin extends CI_Controller
{

    /**
     * Constructor
     *
     * @access public
     */

    function __construct()
    {
        parent::__construct();
        $this->load->model('catalogos_model');
        $this->load->model('users_model');
        $this->load->model('logs_oe_model');
        $this->load->model('informerezagados_model');
        $this->load->model('informegeneral_model');
    }

    /**
     * Default Action. Show view of authentication form
     *
     * @access public
     */
    function index()
    {
        if (!$this->session->userdata('logged_in')) {
            log_message('INFO', '------- Mandamos a vista de login -------');
            $this->load->view('login_view_sf', '');
        } else {
            redirect(base_url());
        }
    }

    function loginsf()
    {
        $nick = $this->input->post('nick');
        $nick = str_replace("=", "", $nick);

        if ($nick == '' || $nick == null || !is_string($nick)) {
            redirect(base_url());
            return;
        }

        $password = $this->input->post('password');
        $password = str_replace("=", "", $password);
        if ($password == '' || $password == null || !is_string($password)) {
            redirect(base_url());
            return;
        }

        if (!$this->auth_acl->loginsf($nick, $password)) {
            redirect('admin/');
        } else {
            $perfil = $this->catalogos_model->loadUsuarioPerfil($nick);

            if ($perfil->sucursal == 'Multiple') {
                return $this->eligeSucursal($perfil->id, $perfil->nombre, $perfil->apellidos, $nick, 'si');
            }

            if ($perfil->usuario == '' || $perfil->usuario == NULL) {
                redirect(base_url());
                return;
            }

            $datasession = array(
                'ID' => $perfil->ID,
                'usuario' => $perfil->usuario,
                'nombre' => $perfil->nombre,
                'apellidos' => $perfil->apellidos,
                'nombregrupo' => $perfil->nombregrupo,
                'sucursal_id' => $perfil->sucursal_id,
                'sucursal' => $perfil->sucursal,
                'perfil' => $perfil->nombregrupo,
                'rfc' => $perfil->rfc
            );
            // creamos la sesion con dichas variables

            $this->session->set_userdata($datasession);

            $datos_log['accion'] = 'inicio de sesion';
            $datos_log['id_elemento'] = $this->session->userdata('ID');
            $this->logs_oe_model->insertLogs($datos_log);

            $this->generaObjetivosCobranzaMensual();
            $this->_genera_cobranza_anticipada();
            //$this->validaInteresesClientes();

            if ($this->session->userdata('intento_url') != '' || $this->session->userdata('intento_url') != NULL) {
                redirect($this->session->userdata('intento_url'));
            } else {
                redirect(base_url());
            }
        }
    }

    /**
     * User logout
     *
     * @access public
     */
    function logout()
    {
        $datos_log['accion'] = 'cierra de sesion';
        $datos_log['id_elemento'] = $this->session->userdata('ID');
        $this->logs_oe_model->insertLogs($datos_log);

        $this->auth_acl->logout();

        redirect(base_url());
    }

    function loadSucursal()
    {
        $tipo_nomenclador = $this->catalogos_model->loadSucursal();
        $options[null] = '';
        foreach ($tipo_nomenclador as $tipo) {
            $options[$tipo->sucursal_id] = $tipo->sucursal;
        }
        return $options;
    }

    function eligeSucursal($id, $nombre, $apellidos, $rfc, $sf)
    {
        $data['sucursal'] = $this->loadSucursal();
        $data['sucursales'] = $this->catalogos_model->loadSucursalByUser($id);
        $data['nombre'] = $nombre . " " . $apellidos;
        $data['rfc'] = $rfc;
        $data['sf'] = $sf;
        $data['myrfc'] = $this->getMyRfc();
        $this->load->view('select_sucursal', $data);
    }

    function eligeSucursalForm()
    {
        $sucursal_id = $this->input->post('sucursal');
        $rfc = $this->input->post('rfc');
        $sf = $this->input->post('sf');
        $sucursal = $this->loadSucursal();

        if ($sf == 'si') {
            $perfil = $this->catalogos_model->loadUsuarioPerfil($rfc);
        } elseif ($sf == 'no') {
            $perfil = $this->catalogos_model->loadUsuarioPerfilByRfc($rfc);
        }

        if ($perfil->usuario == '' || $perfil->usuario == NULL) {
            redirect(base_url());
            return;
        }

        $datasession_remove = array(
            'usuario' => '',
            'nombre' => '',
            'apellidos' => '',
            'nombregrupo' => '',
            'sucursal_id' => '',
            'sucursal' => '',
            'perfil' => '',
            'rfc' => ''
        );

        $datasession = array(
            'usuario' => $perfil->usuario,
            'nombre' => $perfil->nombre,
            'apellidos' => $perfil->apellidos,
            'nombregrupo' => $perfil->nombregrupo,
            'sucursal_id' => $sucursal_id,
            'sucursal' => $sucursal[$sucursal_id],
            'perfil' => $perfil->nombregrupo,
            'rfc' => $perfil->rfc
        );
        // creamos la sesi�n con dichas variables

        $this->session->unset_userdata($datasession_remove);

        $this->session->set_userdata($datasession);

        redirect(base_url());
    }

    private function validaInteresesClientes()
    {
        $hoy = date('Y-m-d');
        $actualizadoHoy = $this->catalogos_model->verificaValidaClientesIntereses($hoy);
        if (!$actualizadoHoy) {
            $this->catalogos_model->insertValidaClientesIntereses($this->session->userdata('usuario'));
            $fecha_limite_pago = date('Y-m-d', strtotime($hoy . ' - 6 days'));
            $clientes = $this->catalogos_model->getAllClientes();
            foreach ($clientes as $cliente) {
                $aux = $this->catalogos_model->fechaProximoPagoPorCliente($cliente->clientes_id);
                $cliente->fecha_prox_pago = $aux->fecha_pago;
                $cliente->fechas_de_pago_id = $aux->fechas_de_pago_id;
                if (strtotime($cliente->fecha_prox_pago) <= strtotime($fecha_limite_pago)) {
                    if (!$this->catalogos_model->verificaFechaPagoIdEnClientesIntereses($cliente->fechas_de_pago_id)) {
                        $porcentaje = $this->catalogos_model->porcentajeInteresPorCliente($cliente->clientes_id);
                        $data = [
                            'fechas_de_pago_id' => $cliente->fechas_de_pago_id,
                            'porcentaje' => $porcentaje
                        ];
                        $id = $this->catalogos_model->insertClienteInteres($data);
                        $datos_log['accion'] = 'Inserta cliente interes';
                        $datos_log['id_elemento'] = $id;
                        $this->logs_oe_model->insertLogs($datos_log);

                        $nuevo_monto = $porcentaje * $cliente->costo_mensual;
                        $update = [
                            'monto' => $nuevo_monto,
                            'monto_extra' => $porcentaje * $cliente->costo_mensual,
                            'motivo_extra' => 'Intereses por adeudo'
                        ];
                        $this->catalogos_model->updateMontoAPagar($update, $cliente->fechas_de_pago_id);
                        $datos_log['accion'] = 'Actualiza monto de cliente por interes: ' . $nuevo_monto;
                        $datos_log['id_elemento'] = $cliente->fechas_de_pago_id;
                        $this->logs_oe_model->insertLogs($datos_log);
                    }
                }
            }
        }
    }

    private function _genera_cobranza_anticipada()
    {
        $fecha_actual = date('Y-m');
        if (!$this->catalogos_model->existe_cobranza_anticipada($fecha_actual)) {
            $sucursales = $this->catalogos_model->loadSucursal();
            foreach ($sucursales as $sucursal) {
                $monto = $this->informegeneral_model->obtener_cobranza_anticipada_por_mes_y_sucursal($fecha_actual, $sucursal->sucursal_id);
                $data_insert = [
                    'fecha' => $fecha_actual,
                    'monto' => $monto,
                    'fecha_registro' => date('Y-m-d H:i:s'),
                    'sucursal_id' => $sucursal->sucursal_id
                ];
                $this->catalogos_model->insert_cobranza_anticipada($data_insert);
            }
        }
    }

    private function generaObjetivosCobranzaMensual()
    {
        $fecha_actual = date('Y-m');
        if (!$this->catalogos_model->existeObjetivoMensual($fecha_actual)) {
            //if (true) {
            $sucursales = $this->catalogos_model->loadSucursal();
            foreach ($sucursales as $sucursal) {
                $monto_rezagado = 0;
                $monto_este_mes = 0;
                $fecha = DateTime::createFromFormat('Y-m-d', date('Y-m-t'));
                $fecha_comparacion = $this->addMonths($fecha, -1);
                $fecha_inicial = new DateTime('2015-01-01 00:00:00');
                while ($fecha_inicial < $fecha_comparacion) {
                    $fechas_seleccionadas = $this->informerezagados_model->obtenerFechasDePagoSinPagarPorAnoMesYSucursalId($fecha_comparacion->format('Y-m'), $sucursal->sucursal_id);
                    foreach ($fechas_seleccionadas as $fecha_pago) {
                        $monto_rezagado += round($fecha_pago->costo_mensual, 2);
                        $monto_rezagado -= round($fecha_pago->monto_pagado, 2);
                    }
                    $fecha_comparacion = $this->addMonths($fecha_comparacion, -1);
                }
                //$fechas_seleccionadas = $this->informerezagados_model->obtenerFechasDePagoSinPagarPorAnoMesYSucursalId($fecha_actual, $sucursal->sucursal_id);
                $fechas_seleccionadas = $this->informegeneral_model->obtenerCobranzaVivaPorPeriodoYSucursal(date('Y-m') . '-01', date('Y-m-t'), $sucursal->sucursal_id);
//                foreach ($fechas_seleccionadas as $fecha_pago) {
//                    $monto_este_mes += round($fecha_pago->costo_mensual, 2);
//                }
                $monto_este_mes += round($fechas_seleccionadas, 2);
                $monto_total = round($monto_este_mes + $monto_rezagado, 2);
                $data_insert = [
                    'fecha' => $fecha_actual,
                    'monto' => $monto_total,
                    'fecha_registro' => date('Y-m-d H:i:s'),
                    'sucursal_id' => $sucursal->sucursal_id
                ];
                $this->catalogos_model->insertObjetivoMensual($data_insert);
//                $datasession = array(
//                    'monto_rezagado' => $monto_rezagado,
//                    'monto_este_mes' => $monto_este_mes
//                );
//                $this->session->set_userdata($datasession);
            }
        }
    }

    private function addMonths($date, $months)
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

}

?>