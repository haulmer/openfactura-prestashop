<?php
/**
 * 2020 Haulmer
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 * You must not modify, adapt or create derivative works of this source code
 *
 * Class of data log storage module
 *
 * @author Haulmer
 * @copyright  Haulmer 2020
 * @license license.txt
 * @version: 1.0.0
 * @Email: soporte@haulmer.com
 * @Date: 22-01-2020 11:00
 * @Last Modified by:
 * @Last Modified time:
 */

if (!defined('_PS_VERSION_')) {
    exit; // Exit if accessed directly
}

class AdminOpenFacturaController extends ModuleAdminController
{
    public function initContent()
    {
        parent::initContent();
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/forms.css');
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/links.css');
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/main.css');
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/modal.css');
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/snackbar-overrides.css');
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/snackbar.min.css');
        $this->context->controller->addCSS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/css/tinyModal.css');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/js/main.js');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/js/snackbar.min.js');
        $this->context->controller->addJS(Tools::getShopDomainSsl(true, true)
            . __PS_BASE_URI__ . 'modules/openfactura/views/js/tinyModal.min.js');

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('openfactura_registry', 'c');
        $sql->where('c.is_active = 1');
        $openfactura_registry = Db::getInstance()->executeS($sql);

        $this->context->smarty->assign('openfactura_registry', $openfactura_registry[0]);

        $actividades = json_decode($openfactura_registry[0]['actividades_economicas'], true);
        $this->context->smarty->assign('actividadesArray', $actividades);

        $sucursales = json_decode($openfactura_registry[0]['sucursales'], true);
        $this->context->smarty->assign('sucursalesArray', $sucursales);

        $moduleLink = Context::getContext()->shop->getBaseURL(true);
        $this->context->smarty->assign('moduleLink', $moduleLink);

        $template_file = _PS_MODULE_DIR_ . 'openfactura/views/templates/admin/configuration.tpl';
        $content = $this->context->smarty->fetch($template_file);
        $this->context->smarty->assign(array(
            'content' =>  $content,
        ));
    }

    /**
     * save data in table openfactura_registry
     */
    public function ajaxProcessSaveDataOpenFacturaRegistry()
    {
        if ($_REQUEST['demo'] == "true") {
            $demo = 1;
        } else {
            $demo = 0;
        }
        if ($_REQUEST['automatic39'] == "true") {
            $automatic39 = 1;
        } else {
            $automatic39 = 0;
        }
        if ($_REQUEST['allow33'] == "true") {
            $allow33 = 1;
        } else {
            $allow33 = 0;
        }
        if ($_REQUEST['enableLogo'] == "true") {
            $enableLogo = 1;
        } else {
            $enableLogo = 0;
        }
        $apikey = str_replace(' ', '', $_REQUEST['apikey']);

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('openfactura_registry', 'c');
        $sql->where('c.is_active=1');
        $openfactura_registry_active = Db::getInstance()->executeS($sql);

        $apikey_demo = '928e15a2d14d4a6292345f04960f4bd3';

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('openfactura_registry', 'c');
        $sql->where('c.apikey ="' . pSQL($apikey_demo) . '"');

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('openfactura_registry', 'c');
        $sql->where('c.apikey !="' . pSQL($apikey_demo) . '"');
        $openfactura_registry_aux = Db::getInstance()->executeS($sql);

        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('openfactura_registry', 'c');
        $sql->where('c.apikey ="' . pSQL($apikey) . '"');
        $openfactura_registry_received = Db::getInstance()->executeS($sql);

        if (isset($openfactura_registry_received) && empty($openfactura_registry_received) && !empty($apikey)) {
            //insert prod data
            $url_emision = 'https://api.haulmer.com/v2/dte/organization';
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url_emision,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Content-type: application/json",
                    "apikey:" . $apikey
                ),
            ));
            $response = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            if ($info['http_code'] != 200) {
                print_r($info);
                return;
            }
            $response = json_decode($response, true);

            Db::getInstance()->update('openfactura_registry', array(
                'is_active' => (bool)0), 'id=' . (int)$openfactura_registry_active[0]['id'] . '');
                

            $actividad_economica = $response['actividades'][0]['actividadEconomica']
                . "|" . $response['actividades'][0]['codigoActividadEconomica'];
            $codigo_actividad_economica_active = $response['actividades'][0]['codigoActividadEconomica'];

            $actividades_array = array();
            foreach ($response['actividades'] as $actividad) {
                $code = $actividad['codigoActividadEconomica'];
                $actividades_array[$code] = $actividad['actividadEconomica'];
            }
            $actividades_array = json_encode($actividades_array);

            $sucursales_array = array();
            $code = $response['cdgSIISucur'];
            $sucursales_array[$code] = $response['direccion'];
            foreach ($response['sucursales'] as $sucursal) {
                $code = $sucursal['cdgSIISucur'];
                $sucursales_array[$code] = $sucursal['direccion'];
            }
            $sucursales_array = json_encode($sucursales_array);

            if (empty($openfactura_registry_aux)) {
                Db::getInstance()->insert('openfactura_registry', array(
                    'is_active' => (bool)1,
                    'apikey' => pSQL($apikey),
                    'rut' => pSQL($response['rut']),
                    'is_demo' => (bool)false,
                    'generate_boleta' => (bool)false,
                    'allow_factura' => (bool)false,
                    'show_logo' => (bool)false,
                    'razon_social' => pSQL($response['razonSocial']),
                    'glosa_descriptiva' => pSQL($response['glosaDescriptiva']),
                    'sucursales' => pSQL($sucursales_array),
                    'sucursal_active' => pSQL($response['cdgSIISucur']),
                    'actividad_economica_active' => pSQL($actividad_economica),
                    'codigo_actividad_economica_active' => (int)$codigo_actividad_economica_active,
                    'actividades_economicas' => pSQL($actividades_array),
                    'direccion_origen' => pSQL($response['direccion']),
                    'comuna_origen' => pSQL($response['comuna']),
                    'json_info_contribuyente' => pSQL(json_encode($response)),
                    'url_doc_base' => pSQL('url orden de compra'),
                    'name_doc_base' => pSQL('orden de compra'),
                    'url_send' => pSQL($url_emision),
                    'cdgSIISucur' => pSQL($response['cdgSIISucur'])
                ));
            } else {
                $id = $openfactura_registry_aux[0]['id'];
                Db::getInstance()->update('openfactura_registry', array(
                    'is_active' => (bool)1,
                    'apikey' => pSQL($apikey),
                    'rut' => pSQL($response['rut']),
                    'is_demo' => (bool)false,
                    'generate_boleta' => (bool)false,
                    'allow_factura' => (bool)false,
                    'show_logo' => (bool)false,
                    'razon_social' => pSQL($response['razonSocial']),
                    'glosa_descriptiva' => pSQL($response['glosaDescriptiva']),
                    'sucursales' => pSQL($sucursales_array),
                    'sucursal_active' => pSQL($response['cdgSIISucur']),
                    'actividad_economica_active' => pSQL($actividad_economica),
                    'codigo_actividad_economica_active' => (int)$codigo_actividad_economica_active,
                    'actividades_economicas' => pSQL($actividades_array),
                    'direccion_origen' => pSQL($response['direccion']),
                    'comuna_origen' => pSQL($response['comuna']),
                    'json_info_contribuyente' => pSQL(json_encode($response)),
                    'url_doc_base' => pSQL('url orden de compra'),
                    'name_doc_base' => pSQL('orden de compra'),
                    'url_send' => pSQL($url_emision),
                    'cdgSIISucur' => pSQL($response['cdgSIISucur'])
                ), 'id=' . (int)$id . '');
            }
            echo json_encode(['data' => 'insert']);
            return;
        } else {
            if (empty($apikey)) {
                echo json_encode(['data' => 'insert']);
                return;
            } else {
                $is_demo = false;
                if ($openfactura_registry_active[0]['is_demo'] == "1") {
                    $is_demo = true;
                } else {
                    $is_demo = false;
                }
                $switch = false;
                if (isset($openfactura_registry_received) && !empty($openfactura_registry_received)) {
                    if ($openfactura_registry_active[0]['apikey']
                        != $openfactura_registry_received[0]['apikey']
                        || ($is_demo != $demo && !empty($openfactura_registry_aux))
                    ) {
                        $id = $openfactura_registry_received[0]['id'];
                        $switch = true;
                    } else {
                        $id = $openfactura_registry_active[0]['id'];
                        $switch = false;
                    }
                }
            }
            $url_logo = str_replace(' ', '', $_REQUEST['urlLogo']);
            if (empty($url_logo)) {
                $url_logo = "";
            }
            Db::getInstance()->update('openfactura_registry', array(
                'generate_boleta' => (bool)$automatic39,
                'sucursal_active' => pSQL($_REQUEST['sucursal']),
                'show_logo' => (bool)$enableLogo,
                'link_logo' => pSQL($url_logo),
                'codigo_actividad_economica_active' => (int)$_REQUEST['actividad'],
                'cdgSIISucur' => pSQL($_REQUEST['sucursal']),
                'allow_factura' => (bool)$allow33,
                'show_logo' => (bool)$enableLogo
            ), 'id=' . (int)$id . '');

            if ($switch) {
                Db::getInstance()->update('openfactura_registry', array(
                    'is_active' => (bool)!$switch), 'id=' . (int)$id . '');
                Db::getInstance()->update('openfactura_registry', array(
                    'is_active' => (bool)$switch), 'id!=' . (int)$id . '');

                echo json_encode(['data' => 'insert']);
                return;
            } else {
                echo json_encode(['data' => 'update']);
                return;
            }
        }
    }

    /**
     * Update data table openfactura_registry
     */
    public function ajaxProcessUpdateDataOpenfacturaRegistry()
    {
        $apikey = str_replace(' ', '', $_REQUEST['apikey']);
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('openfactura_registry', 'c');
        $sql->where('c.apikey ="' . pSQL($apikey) . '"');
        $openfactura_registry = Db::getInstance()->executeS($sql);
        if (isset($openfactura_registry) && !empty($openfactura_registry)) {
            if ($openfactura_registry[0]['is_demo'] == 1) {
                //dev environment
                $url_organization = 'https://dev-api.haulmer.com/v2/dte/organization';
            } else {
                //prod environment
                $url_organization = 'https://api.haulmer.com/v2/dte/organization';
            }
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url_organization,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "Content-type: application/json",
                    "apikey:" . $apikey
                ),
            ));
            $response = curl_exec($curl);
            $info = curl_getinfo($curl);
            curl_close($curl);
            if ($info['http_code'] != 200) {
                echo json_encode(['data' => 'error']);
                return;
            }
            $response = json_decode($response, true);

            $actividades_array = array();
            $flag_actvidad = false;
            if (isset($response['actividades']) || !empty($response['actividades'])) {
                $actividades_array = array();
                foreach ($response['actividades'] as $actividad) {
                    $code = $actividad['codigoActividadEconomica'];
                    $actividades_array[$code] = $actividad['actividadEconomica'];
                    if ($openfactura_registry[0]['codigo_actividad_economica_active']
                        == $actividad['codigoActividadEconomica']
                    ) {
                        $flag_actvidad = true;
                        $codigo_actividad_active = $actividad['codigoActividadEconomica'];
                    }
                }
                if ($flag_actvidad == false) {
                    $codigo_actividad_active = $openfactura_registry[0]['sucursal_active'];
                }
            }
            $actividades_array = json_encode($actividades_array);

            $flag_sucursal = false;
            $code = $response['cdgSIISucur'];
            $sucursales_array = array();
            $sucursales_array[$code] = $response['direccion'];
            if (isset($response['actividades']) || !empty($response['actividades'])) {
                foreach ($response['sucursales'] as $sucursal) {
                    $code = $sucursal['cdgSIISucur'];
                    $sucursales_array[$code] = $sucursal['direccion'];
                    if ($openfactura_registry[0]['cdgSIISucur'] == $sucursal['cdgSIISucur']) {
                        $flag_sucursal = true;
                        $code = $sucursal['cdgSIISucur'];
                    }
                }
                if ($flag_sucursal == false) {
                    $code = $openfactura_registry[0]['cdgSIISucur'];
                }
            }
            $sucursales_array = json_encode($sucursales_array);

            Db::getInstance()->update('openfactura_registry', array(
                'rut' => pSQL($response['rut']),
                'razon_social' => pSQL($response['razonSocial']),
                'glosa_descriptiva' => pSQL($response['glosaDescriptiva']),
                'sucursales' => pSQL($sucursales_array),
                'actividades_economicas' => pSQL($actividades_array),
                'codigo_actividad_economica_active' => (int)$codigo_actividad_active,
                'direccion_origen' => pSQL($response['direccion']),
                'comuna_origen' => pSQL($response['comuna']),
                'cdgSIISucur' => pSQL($code),
                'json_info_contribuyente' => pSQL(json_encode($response)),
                'url_send' => pSQL($url_organization)
            ), 'id=' . (int)$openfactura_registry[0]['id'] . '');

            echo json_encode(['data' => 'update']);
            return;
        }
    }
}
