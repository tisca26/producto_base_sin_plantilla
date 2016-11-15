<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Catalogos_model extends CI_Model
{

    /**
     * Constructor
     *
     * @access public
     */
    function __construct()
    {
        parent::__construct();
    }

    public function verificaValidaClientesIntereses($hoy)
    {
        return $this->db->like('fecha', $hoy)->get('valida_clientes_intereses')->num_rows() > 0 ? TRUE : FALSE;
    }

    public function insertValidaClientesIntereses($username)
    {
        $data = [
            'fecha' => date('Y-m-d H:m:s'),
            'quien' => $username
        ];
        $this->db->insert('valida_clientes_intereses', $data);
        return $this->db->insert_id();
    }

    public function getAllClientes()
    {
        return $this->db->where('estatus', 1)->get('clientes')->result();
    }

    public function fechaProximoPagoPorCliente($clientes_id)
    {
        return $this->db->where('cliente_id', $clientes_id)->
        where('pagado', 0)->
        order_by('fechas_de_pago_id', 'asc')->
        get('fechas_de_pago')->first_row();
    }

    public function verificaFechaPagoIdEnClientesIntereses($fechas_de_pago_id)
    {
        return $this->db->where('fechas_de_pago_id', $fechas_de_pago_id)->get('clientes_intereses')->num_rows() > 0 ? TRUE : FALSE;
    }

    public function porcentajeInteresPorCliente($clientes_id)
    {
        $cliente = $this->db->where('clientes_id', $clientes_id)->get('clientes')->row();
        $porcentaje = 0;
        switch ($cliente->tipo_plan) {
            case 'Fiscal':
                $porcentaje = .25;
                break;
            case 'Amueblada':
                $porcentaje = .05;
                break;
            case 'Comercial':
                $porcentaje = .25;
                break;

            default:
                break;
        }
        return $porcentaje;
    }

    public function insertClienteInteres($data)
    {
        $this->db->insert('clientes_intereses', $data);
        return $this->db->insert_id();
    }

    public function updateMontoAPagar($data, $fechas_de_pago_id)
    {
        $f_d_p = $this->db->where('fechas_de_pago_id', $fechas_de_pago_id)->get('fechas_de_pago')->row();
        $update = [
            'monto' => $data['monto'] + $f_d_p->monto,
            'monto_extra' => $data['monto_extra'] + $f_d_p->monto_extra,
            'motivo_extra' => $data['motivo_extra'] . '<br />' . $f_d_p->motivo_extra . '<br />'
        ];
        $this->db->update('fechas_de_pago', $update, array('fechas_de_pago_id' => $fechas_de_pago_id));
    }

    public function loadEstadosSelect()
    {
        $estados = $this->db->get('estados')->result();
        $options = array();
        foreach ($estados as $estado) {
            $options[$estado->estados_id] = $estado->nombre;
        }
        return $options;
    }

    public function getNombreEstadosSelect($id)
    {
        $query = $this->db->query('SELECT * FROM estados WHERE estados_id = ?', $id)->result();
        foreach ($query as $nombreEstado) {
            $nombreEstado->nombre;
        }
        return $nombreEstado;
    }

    public function loadTipoLineaTelefonicaSelect()
    {
        $options['PERSONALIZADA'] = "PERSONALIZADA";
        $options['UNIVERSAL'] = "UNIVERSAL";
        return $options;
    }

    public function loadUsuarioPerfil($id)
    {
        $data = array();
        $query = $this->db->where('v_usuarios.usuario', $id)->get('v_usuarios');
        if ($query->num_rows() > 0) {
            $data = $query->row();
        }
        return $data;
    }

    public function loadSiNo()
    {
        $options[0] = '-Seleccione-';
        $options['Si'] = 'Si';
        $options['No'] = 'No';
        return $options;
    }

    public function loadSiNoNull()
    {
        $options[null] = '-Seleccione-';
        $options['Si'] = 'Si';
        $options['No'] = 'No';
        return $options;
    }

    public function loadSiNoAplica()
    {
        $options[0] = '-Seleccione-';
        $options['Si'] = 'Si';
        $options['No'] = 'No';
        $options['NoAplica'] = 'No Aplica';
        return $options;
    }

    public function loadSucursal()
    {
        return $this->db->order_by('sucursal_id', 'asc')->get('sucursal')->result();
    }

    public function loadUsuarioPerfilByRfc($rfc)
    {
        $data = array();
        $query = $this->db->where('v_usuarios.rfc', $rfc)->get('v_usuarios');
        if ($query->num_rows() > 0) {
            $data = $query->row();
        }
        return $data;
    }

    public function getUserByRfc($rfc)
    {
        $data = array();
        $query = $this->db->where('users.rfc', $rfc)->get('users');
        if ($query->num_rows() > 0) {
            $data = $query->row();
        }
        return $data;
    }

    public function loadSucursalByUser($id)
    {
        $data = array();
        $query = $this->db->where('user_multiple.user_id', $id)->get('user_multiple');
        if ($query->num_rows() > 0) {
            $data = $query->result();
        }
        return $data;
    }

    public function loadSucursalArray()
    {
        return $this->db->get('sucursal')->result_array();
    }

    public function loadSucursalesSelect()
    {
        $sucursales = $this->db->get('sucursal')->result();
        $options = array();
        foreach ($sucursales as $sucursal) {
            $options[$sucursal->sucursal_id] = $sucursal->nombre;
        }
        return $options;
    }

    public function ventas_sucursales_select()
    {
        $sucursales = $this->db->get('sucursal')->result();
        $options = array();
        $options[0] = 'Sin Definir';
        foreach ($sucursales as $sucursal) {
            $options[$sucursal->sucursal_id] = $sucursal->nombre;
        }
        return $options;
    }

    public function sucursales_acronimo_select()
    {
        $sucursales = $this->db->get('sucursal')->result();
        $options = array();
        foreach ($sucursales as $sucursal) {
            $options[$sucursal->sucursal_id] = $sucursal->acronimo;
        }
        return $options;
    }

    public function loadSucursalesSelectTodas()
    {
        $sucursales = $this->db->get('sucursal')->result();
        $options[0] = '-Todas las sucursales-';
        foreach ($sucursales as $sucursal) {
            $options[$sucursal->sucursal_id] = $sucursal->nombre;
        }
        return $options;
    }

    public function loadTipoPlanSelect()
    {
        $planes = $this->db->get('tipo_plan')->result();
        $options = array();
        foreach ($planes as $plan) {
            $options[$plan->tipo_plan_id] = $plan->nombre;
        }
        return $options;
    }

    public function loadTipoPlanBySucursal($sucursal_id)
    {
        return $this->db->select('tipo_plan_id, nombre')->from('tipo_plan')->where('sucursal_id', $sucursal_id)->get()->result();
    }

    public function loadSucursalesSelectNull()
    {
        $sucursales = $this->db->get('sucursal')->result();
        $options = array();
        $options[null] = '-Seleccione-';
        foreach ($sucursales as $sucursal) {
            $options[$sucursal->sucursal_id] = $sucursal->nombre;
        }
        return $options;
    }

    public function loadDescuentosSelectNull()
    {
        $descuentos = $this->db->get('descuentos')->result();
        $options = array();
        $options[0] = '-Seleccione-';
        foreach ($descuentos as $descuento) {
            $options[$descuento->descuentos_id] = $descuento->nombre . ' - ' . $descuento->porcentaje . ' %';
        }
        return $options;
    }

    public function getPorcentajeDescuentoSelect($id)
    {
        $result = new stdClass();
        $query = $this->db->where('descuentos_id', $id)->get('descuentos');
        if ($query->num_rows() > 0) {
            $result = $query->row();
        }
        return $result;

    }

    public function loadTiposPlanes()
    {
        $options[null] = '-Seleccione-';
        $options['Fiscal'] = 'Fiscal';
        $options['Comercial'] = 'Comercial';
        $options['Amueblada'] = 'Amueblada';
        $options['Eventual'] = 'Eventual';
        return $options;
    }

    public function loadPagosSel($sucursal_id)
    {
        $query = $this->db->select('clientes.numero_referencia')->
        where('estatus', 1)->
        like('clientes.sucursal_id', $sucursal_id)->get('clientes');
        if ($query->num_rows() > 0) {
            $query = $query->result();
        }
        $options = array();
        $options[0] = '-Seleccione-';
        if (count($query) > 0) {
            foreach ($query as $num) {
                $options[$num->numero_referencia] = $num->numero_referencia;
            }
        }
        return $options;
    }

    public function loadServiciosAdicionalesSel()
    {
        $query = $this->db->where('estatus', 1)->get('servicios_adicionales')->result();
        $options = array();
        $options[0] = '-Seleccione-';
        foreach ($query as $serv) {
            $options[$serv->servicios_adicionales_id] = $serv->nombre;
        }
        return $options;
    }

    public function loadServiciosAdicionales()
    {
        return $query = $this->db->where('estatus', 1)->get('servicios_adicionales')->result();
    }

    public function soloMesesSel()
    {
        $options['01'] = 'Enero';
        $options['02'] = 'Febrero';
        $options['03'] = 'Marzo';
        $options['04'] = 'Abril';
        $options['05'] = 'Mayo';
        $options['06'] = 'Junio';
        $options['07'] = 'Julio';
        $options['08'] = 'Agosto';
        $options['09'] = 'Septiembre';
        $options['10'] = 'Octubre';
        $options['11'] = 'Noviembre';
        $options['12'] = 'Diciembre';
        return $options;
    }

    public function anosHastaActual()
    {
        $ano = date("Y");
        $ano_base = 2015;
        $options = array();
        $options[$ano] = $ano;
        while ($ano > $ano_base) {
            $ano -= 1;
            $options[$ano] = $ano;
        }
        return $options;
    }

    public function existeObjetivoMensual($fecha)
    {
        return $this->db->where('fecha', $fecha)->get('objetivos_cobranza_mensual')->num_rows() > 0 ? TRUE : FALSE;
    }

    public function existe_cobranza_anticipada($fecha)
    {
        return $this->db->where('fecha', $fecha)->get('cobranza_anticipada')->num_rows() > 0 ? TRUE : FALSE;
    }

    public function insertObjetivoMensual($data)
    {
        $this->db->insert('objetivos_cobranza_mensual', $data);
        return $this->db->insert_id();
    }

    public function insert_cobranza_anticipada($data)
    {
        $this->db->insert('cobranza_anticipada', $data);
        return $this->db->insert_id();
    }

    public function planes_ventas_sel()
    {
        $options['Fiscal'] = 'Fiscal';
        $options['Comercial'] = 'Comercial';
        $options['Amueblada'] = 'Amueblada';
        $options['Eventual'] = 'Eventual';
        $options['Oficina Virtual'] = 'Oficina Virtual';
        return $options;
    }

    public function forma_contacto_sel()
    {
        $options['Llamada'] = 'Llamada';
        $options['Recomendación'] = 'Recomendación';
        $options['Presencial'] = 'Presencial';
        $options['Web'] = 'Web';
        $options['WhatsApp'] = 'WhatsApp';
        return $options;
    }

    public function forma_contacto_seguimiento_sel()
    {
        $options[null] = '-- Forma de seguimiento --';
        $options['Cita'] = 'Cita';
        $options['Correo'] = 'Correo';
        $options['Llamada'] = 'Llamada';
        $options['WhatsApp'] = 'WhatsApp';
        return $options;
    }

    public function tipo_persona_sel()
    {
        $options[null] = '-- Tipo de persona --';
        $options['Física'] = 'Física';
        $options['Moral'] = 'Moral';
        return $options;
    }

    public function razones_no_concretado_sel()
    {
        $options[null] = '-- Razones --';
        $options['No hubo respuesta'] = 'No hubo respuesta';
        $options['Costo'] = 'Costo';
        $options['No se concretó su proyecto'] = 'No se concretó su proyecto';
        $options['Contrató en otro lugar'] = 'Contrató en otro lugar';
        $options['No contamos con los requerimientos'] = 'No contamos con los requerimientos';
        $options['No dió comentarios'] = 'No dió comentarios';
        $options['Estudio de mercado'] = 'Estudio de mercado';
        return $options;
    }

    public function razones_no_concretado()
    {
        $options['No hubo respuesta'] = 'No hubo respuesta';
        $options['Costo'] = 'Costo';
        $options['No se concretó su proyecto'] = 'No se concretó su proyecto';
        $options['Contrató en otro lugar'] = 'Contrató en otro lugar';
        $options['No contamos con los requerimientos'] = 'No contamos con los requerimientos';
        $options['No dió comentarios'] = 'No dió comentarios';
        $options['Estudio de mercado'] = 'Estudio de mercado';
        return $options;
    }
}

?>