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
 * OpenFactura module class back-office
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

class Openfactura extends Module
{
    public function __construct()
    {
        $this->name = 'openfactura';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Haulmer';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '424d25a5df7d60490e8cc1972b0a8bee';

        parent::__construct();

        $this->displayName = 'OpenFactura';
        $this->description = $this->l('Automate the issuance of tickets and / or invoices in your ecommerce');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
    }

    public function install()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminOpenFactura';
        $tab->position = 3;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'OpenFactura';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('DEFAULT');
        $tab->module = $this->name;
        $tab->add();
        $tab->save();

        Db::getInstance()->execute('
        CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'openfactura_registry` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `apikey` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT NULL,
            `is_demo` tinyint(1) DEFAULT NULL,
            `generate_boleta` tinyint(1) DEFAULT NULL,
            `allow_factura` tinyint(1) DEFAULT NULL,
            `link_logo` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `show_logo` tinyint(1) DEFAULT NULL,
            `rut` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `razon_social` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `glosa_descriptiva` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `sucursales` text COLLATE utf8_unicode_ci DEFAULT NULL,
            `sucursal_active` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `actividad_economica_active` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `actividades_economicas` text COLLATE utf8_unicode_ci DEFAULT NULL,
            `codigo_actividad_economica_active` int(11) DEFAULT NULL,
            `direccion_origen` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `comuna_origen` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `json_info_contribuyente` text COLLATE utf8_unicode_ci DEFAULT NULL,
            `url_doc_base` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `name_doc_base` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `url_send` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `cdgsSIISucur` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
            `cdgSIISucur` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL
            )ENGINE = ' . _MYSQL_ENGINE_ . ' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;');

        return (parent::install()
            && $this->registerHook('ActionOrderStatusPostUpdate')
            && $this->insertDemoData());
    }

    public function uninstall()
    {
        Db::getInstance()->execute('
        DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'openfactura_registry`');
        $this->_clearCache('*');
        if (!parent::uninstall() || !$this->unregisterHook('displayHome')) {
            return false;
        }
        $this->uninstallTab();
        return true;
    }

    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminOpenFactura');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    public function insertDemoData()
    {
        $apikey = '928e15a2d14d4a6292345f04960f4bd3';
        //insert demo data dev
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://dev-api.haulmer.com/v2/dte/organization",
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
            return false;
        }

        $response = json_decode($response, true);
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


        Db::getInstance()->insert('openfactura_registry', array(
            'apikey' => pSQL($apikey),
            'is_active' => (bool)1,
            'is_demo' => (bool)1,
            'generate_boleta' => (bool)1,
            'allow_factura' => (bool)1,
            'show_logo' => (bool)0,
            'rut' => pSQL($response['rut']),
            'razon_social' => pSQL($response['razonSocial']),
            'glosa_descriptiva' => pSQL($response['glosaDescriptiva']),
            'sucursal_active' => pSQL($response['direccion']),
            'actividad_economica_active' => pSQL($actividad_economica),
            'codigo_actividad_economica_active' => (int)$codigo_actividad_economica_active,
            'actividades_economicas' => pSQL($actividades_array),
            'direccion_origen' => pSQL($response['direccion']),
            'comuna_origen' => pSQL($response['comuna']),
            'sucursales' => pSQL($sucursales_array),
            'cdgSIISucur' => pSQL($response['cdgSIISucur']),
            'json_info_contribuyente' => pSQL(json_encode($response))
        ));
        return true;
    }

    /**
     * Hook that is executed when paying an order creating a document to be issued
     */
    public function hookActionOrderStatusPostUpdate(array $params)
    {
        if (!empty($params['newOrderStatus'])) {
            if ($params['newOrderStatus']->id == Configuration::get('PS_OS_WS_PAYMENT') || $params['newOrderStatus']->id == Configuration::get('PS_OS_PAYMENT')) {
                PrestaShopLogger::addLog('params: ' . json_encode($params['newOrderStatus']->name), 1);
                $order = new Order((int)$params['id_order']);
                $customer = new Customer((int)$order->id_customer);
                $sql = new DbQuery();
                $sql->select('*');
                $sql->from('openfactura_registry', 'c');
                $sql->where('c.is_active=1');
                $openfacturaRegistryActive = Db::getInstance()->executeS($sql);
                $this->createJsonOpenFactura($order, $customer, $openfacturaRegistryActive[0]);
                return true;
            } else {
                return;
            }
        }
    }

    public function createJsonOpenFactura($order, $customer_detail, $openfacturaRegistryActive)
    {
        $document_send = array();
        $response = array();
        $response["response"] = ["SELF_SERVICE"];
        $customer = array();
        if (!empty($customer_detail->firstname)
            && !empty($customer_detail->lastname)
            && !empty($customer_detail->email)) {
            $customer["customer"] = [
                "fullName" =>
                    Tools::substr($customer_detail->firstname . " " . $customer_detail->lastname, 0, 100),
                "email" => Tools::substr($customer_detail->email, 0, 80)
            ];
        } elseif (!empty($customer_detail->email) && !empty($customer_detail->email)) {
            $customer["customer"] = [
                "fullName" => Tools::substr($customer_detail->firstname, 0, 100),
                "email" => Tools::substr($customer_detail->email, 0, 80)
            ];
        } else {
            $customer["customer"] = ["fullName" => Tools::substr($customer_detail->firstname, 0, 100)];
        }
        $customize_page = array();
        $urlHistory = $this->context->link->getPageLink('history', true);
        if (!empty($openfacturaRegistryActive['link_logo']) && $openfacturaRegistryActive['show_logo']) {
            $customize_page["customizePage"] =
                [
                "urlLogo" => $openfacturaRegistryActive['link_logo'],
                'externalReference' =>
                    ["hyperlinkText" => "Orden de Compra #" . $order->id, "hyperlinkURL" => $urlHistory]
            ];
        } else {
            $customize_page["customizePage"] =
                ['externalReference' =>
                ["hyperlinkText" => "Orden de Compra #" . $order->id, "hyperlinkURL" => $urlHistory]];
        }
        $date = date('Y-m-d');
        if ($openfacturaRegistryActive['generate_boleta'] == "1") {
            $generate_boleta = true;
        } else {
            $generate_boleta = false;
        }
        if ($openfacturaRegistryActive['allow_factura'] == "1") {
            $allow_factura = true;
        } else {
            $allow_factura = false;
        }
        $self_service = array();
        $self_service["selfService"] =
            ["issueBoleta" => $generate_boleta, "allowFactura"
            => $allow_factura, "documentReference"
            => [["type"
            => "801", "ID"
            => $order->id, "date"
            => $date]]];
        $is_exe = false;
        $is_afecta = false;
        $mnt_exe = 0;
        $mnt_total = 0;
        $detalle = array();
        $items = null;

        $products = $order->getProducts();
        $orderDetail = $order->getOrderDetailList();

        PrestaShopLogger::addLog('ORDEN: ' . json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
        PrestaShopLogger::addLog('PRODUCTS: ' . json_encode($order->getProducts(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
        $cart = new Cart($order->id_cart);
        $cartRules = $cart->getCartRules();
        PrestaShopLogger::addLog('CART RULES: ' . json_encode($cartRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);

        if ($order->total_paid_tax_incl == $order->total_paid_tax_excl && $order->total_shipping == $order->total_shipping_tax_excl) {
            $is_exe = true;
        } else {
            $is_afecta = true;
        }

        //find product more cheap
        $minimumAmount = 999999999;
        foreach ($products as $product) {
            if ($product['total_price_tax_excl'] < $minimumAmount) {
                $minimumAmount = $product['total_price_tax_excl'];
                $productIdMoreCheap = $product['product_id'];
            }
        }
        //record products and discount codes
        $freeShipping = false;
        $i = 1;
        $errorDiscount = 0;
        foreach ($products as $product) {
            if ($product['total_price_tax_incl'] == $product['total_price_tax_excl']) {
                //exenta
                if ($order->total_discounts > 0) {
                    $cart = new Cart($order->id_cart);
                    $cartRules = $cart->getCartRules();
                    $discount = 0;
                    $totalpriceAux = $product['total_price_tax_excl'];
                    foreach ($cartRules as $rule) {
                        //free distribution
                        if ($rule['free_shipping'] == "1") {
                            $freeShipping = true;
                        }
                        if ($rule['reduction_product'] == '0') {
                            //reduction_product = 0 global discount at order
                            if ($rule['reduction_percent'] > 0) {
                                $discount = $discount + ($product['total_price_tax_excl']
                                    * ($rule['reduction_percent'] / 100));
                            } else {
                                $percentTotal = $totalpriceAux / $order->total_products;
                                if ($freeShipping) {
                                    $discount = $discount + (($rule['value_tax_exc'] - $order->total_shipping_tax_excl)
                                        * $percentTotal);
                                } else {
                                    $discount = $discount + ($rule['value_tax_exc'] * $percentTotal);
                                }
                                $totalpriceAux = $totalpriceAux - $discount;
                            }
                        } elseif ($rule['reduction_product'] == '-1') {
                            //reduction_product = -1 discount product cheaper
                            if ($productIdMoreCheap == $product['product_id']) {
                                if ($rule['reduction_percent'] > 0) {
                                    $discount = ($product['unit_price_tax_excl']
                                        * ($rule['reduction_percent'] / 100));
                                } else {
                                    $percentTotal = $product['unit_price_tax_excl'] / $order->total_products;
                                    $discount = ($rule['value_tax_exc'] * $percentTotal);
                                }
                            }
                        } else {
                            //reduction_product = -2 o x discount specific product
                            $idProduct = $product['product_id'];
                            $idCartRule = $rule['id_cart_rule'];
                            $result = Db::getInstance()->executeS(
                                '
                            SELECT *,rg.quantity as minimum_quantity FROM `' . _DB_PREFIX_
                                    . 'cart_rule_product_rule_value` cv 
                                LEFT JOIN `' . _DB_PREFIX_
                                    . 'cart_rule_product_rule` pr 
                                ON cv.id_product_rule = pr.id_product_rule 
                                LEFT JOIN `' . _DB_PREFIX_
                                    . 'cart_rule_product_rule_group` rg 
                                ON pr.id_product_rule_group = rg.id_product_rule_group
                                LEFT JOIN `' . _DB_PREFIX_
                                    . 'cart_rule` cr ON cr.id_cart_rule = rg.id_cart_rule
                                WHERE 
                                cv.id_item = ' . (int)$idProduct . ' and 
                                cr.id_cart_rule=' . (int)$idCartRule . ''
                            );
                            PrestaShopLogger::addLog('restrictions: ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
                            if (!empty($result)) {
                                if ($rule['reduction_percent'] > 0) {
                                    $discount = $discount + ($product['total_price_tax_excl']
                                        * ($rule['reduction_percent'] / 100));
                                    $errorDiscount = $errorDiscount + $discount;
                                } else {
                                    if ($freeShipping) {
                                        $discount = $discount + ($rule['value_tax_exc']
                                            - $order->total_shipping_tax_excl);
                                        $errorDiscount = $errorDiscount + $discount;
                                    } else {
                                        $discount = $discount + ($rule['value_tax_exc']);
                                        $errorDiscount = $errorDiscount + $discount;
                                    }
                                }
                            }
                        }
                    }
                    $discount = round($discount);
                    $errorDiscount = $errorDiscount + $discount;
                    if ($i == count($products)) {
                        if ($freeShipping) {
                            $totalDiscountTaxExcluded = $order->total_discounts_tax_excl
                                - $order->total_shipping_tax_excl;
                        } else {
                            $totalDiscountTaxExcluded = $order->total_discounts_tax_excl;
                        }
                        PrestaShopLogger::addLog(' OrderID: ' . $order->id . ' i: ' . $i
                            . " count: " . count($products) . " sumaDeDescuentos: "
                            . $errorDiscount . " descuentoTotal: " . $order->total_discounts_tax_excl
                            . ' total discount without shipping: ' . $totalDiscountTaxExcluded, 1);
                        if ($totalDiscountTaxExcluded > $errorDiscount) {
                            $discount = $discount + ($totalDiscountTaxExcluded - $errorDiscount);
                        }
                    }
                    $totalDiscount = $product['total_price_tax_excl'] - $discount;
                    $mnt_exe = $mnt_exe + $totalDiscount;
                    $items = [
                        "NroLinDet" => $i, 'NmbItem' => $product['product_name'],
                        'QtyItem' => $product['product_quantity'],
                        'PrcItem' => round($product['unit_price_tax_excl'], 6),
                        'MontoItem' => round($totalDiscount), 
                        'IndExe' => 1, 
                        'DescuentoMonto' => ($discount)
                    ];
                } else {
                    $mnt_exe = $mnt_exe + $product['total_price_tax_excl'];
                    $items = [
                        "NroLinDet" => $i,
                        'NmbItem' => $product['product_name'],
                        'QtyItem' => $product['product_quantity'],
                        'PrcItem' => round($product['unit_price_tax_excl'], 6),
                        'MontoItem' => round($product['total_price_tax_excl']), 
                        'IndExe' => 1
                    ];
                }
            } else {
                //afecto
                if ($order->total_discounts > 0) {
                    $cart = new Cart($order->id_cart);
                    $cartRules = $cart->getCartRules();
                    $discount = 0;
                    $minimumAmount = 999999999;
                    $totalpriceAux = $product['total_price_tax_excl'];
                    foreach ($cartRules as $rule) {
                        if ($rule['free_shipping'] == "1") {
                            $freeShipping = true;
                        }
                        if ($rule['reduction_product'] == '0') {
                            //reduction_product = 0 total discount at order
                            if ($rule['reduction_percent'] > 0) {
                                $discount = $discount + ($product['total_price_tax_excl']
                                    * ($rule['reduction_percent'] / 100));
                            } else {
                                //discount percentaje at order
                                $percentTotal = $totalpriceAux / $order->total_products;
                                if ($freeShipping) {
                                    $discount = $discount + (($rule['value_tax_exc'] - $order->total_shipping_tax_excl)
                                        * $percentTotal);
                                } else {
                                    $discount = $discount + ($rule['value_tax_exc'] * $percentTotal);
                                }
                                $totalpriceAux = $totalpriceAux - $discount;
                            }
                        } elseif ($rule['reduction_product'] == '-1') {
                            //reduction_product = -1 discount cheaper product
                            if ($productIdMoreCheap == $product['product_id']) {
                                if ($rule['reduction_percent'] > 0) {
                                    $discount = ($product['unit_price_tax_excl'] * ($rule['reduction_percent'] / 100));
                                } else {
                                    $percentTotal = $product['unit_price_tax_excl'] / $order->total_products;
                                    $discount = ($rule['value_tax_exc'] * $percentTotal);
                                }
                            }
                        } else {
                            //reduction_product = x discount specific product
                            $idProduct = $product['product_id'];
                            $idCartRule = $rule['id_cart_rule'];
                            $result = Db::getInstance()->executeS(
                                '
                            SELECT *,rg.quantity as minimum_quantity FROM `'
                                    . _DB_PREFIX_ . 'cart_rule_product_rule_value` cv 
                                LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule` pr 
                                ON cv.id_product_rule = pr.id_product_rule 
                                LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` rg 
                                ON pr.id_product_rule_group = rg.id_product_rule_group
                                LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule` cr 
                                ON cr.id_cart_rule = rg.id_cart_rule
                                WHERE 
                                cv.id_item = ' . (int)$idProduct .
                                    ' and cr.id_cart_rule=' . (int)$idCartRule . ''
                            );
                            if (!empty($result)) {
                                if ($rule['reduction_percent'] > 0) {
                                    $discount = $discount + ($product['total_price_tax_excl']
                                        * ($rule['reduction_percent'] / 100));
                                    $errorDiscount = $errorDiscount + $discount;
                                } else {
                                    if ($freeShipping) {
                                        $discount = $discount + ($rule['value_tax_exc']
                                            - $order->total_shipping_tax_excl);
                                        $errorDiscount = $errorDiscount + $discount;
                                    } else {
                                        $discount = $discount + ($rule['value_tax_exc']);
                                        $errorDiscount = $errorDiscount + $discount;
                                    }
                                }
                            }
                        }
                    }
                    $discount = round($discount);
                    $errorDiscount = $errorDiscount + $discount;
                    if ($i == count($products)) {
                        if ($freeShipping) {
                            $totalDiscountTaxExcluded = $order->total_discounts_tax_excl
                                - $order->total_shipping_tax_excl;
                        } else {
                            $totalDiscountTaxExcluded = $order->total_discounts_tax_excl;
                        }
                        PrestaShopLogger::addLog(' OrderID: ' . $order->id . ' i: ' . $i
                            . " count: " . count($products) . " sumaDeDescuentos: "
                            . $errorDiscount . " descuentoTotal: " . $order->total_discounts_tax_excl
                            . ' total discount without shipping: ' . $totalDiscountTaxExcluded, 1);
                        if ($totalDiscountTaxExcluded > $errorDiscount) {
                            $discount = $discount + ($totalDiscountTaxExcluded - $errorDiscount);
                        }
                    }
                    $totalDiscount = ($product['total_price_tax_excl'] - $discount);
                    $mnt_total = $mnt_total + $totalDiscount;
                    $items = [
                        "NroLinDet" => $i, 'NmbItem' => $product['product_name'],
                        'QtyItem' => $product['product_quantity'],
                        'PrcItem' => round($product['unit_price_tax_excl'], 6),
                        'MontoItem' => round($totalDiscount), 'DescuentoMonto' => ($discount)
                    ];
                } else {
                    $mnt_total = $mnt_total + $product['total_price_tax_excl'];
                    $items = [
                        "NroLinDet" => $i,
                        'NmbItem' => $product['product_name'],
                        'QtyItem' => $product['product_quantity'],
                        'PrcItem' => round($product['unit_price_tax_excl'], 6),
                        'MontoItem' => round($product['total_price_tax_excl'])
                    ];
                }
            }
            $i++;
            array_push($detalle, $items);
        }

        $shippingTaxable = false;
        $shipping = new Carrier((int)($order->id_carrier));
        $idCarrier = $shipping->id;
        PrestaShopLogger::addLog('idCarrier: ' . $idCarrier, 1);
        $result = Db::getInstance()->executeS(
            '
            SELECT * FROM `'
                . _DB_PREFIX_ . 'carrier_tax_rules_group_shop` cr
            WHERE 
            cr.id_carrier = ' . (int)$idCarrier . ''
        );
        PrestaShopLogger::addLog('result carrier: ' . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
        //Record the office verifying if you have an associated tax
        if (!empty($result)) {
            $id = $result[0]['id_tax_rules_group'];
            PrestaShopLogger::addLog('id result carrier: ' . $id, 1);
            if ($id == 0) {
                $shippingTaxable = true;
            } else {
                $shippingTaxable = false;
            }
        }

        //Add the dispatch item verifying if the discount code used has the dispatch free
        if ($order->total_shipping > 0) {
            $shipping = new Carrier((int)($order->id_carrier));
            PrestaShopLogger::addLog('shipping: ' . json_encode($shipping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
            $items = [
                "NroLinDet" => $i,
                'NmbItem' => $shipping->name,
                'QtyItem' => 1,
                'PrcItem' => ($order->total_shipping_tax_excl)
            ];
            if ($shippingTaxable) {
                if ($freeShipping) {
                    $items = array_merge((array)$items, ['MontoItem' => (int)(0)]);
                    $items = array_merge((array)$items, ['IndExe' => 1]);
                    $items = array_merge((array)$items, ['DescuentoMonto' => round($order->total_shipping_tax_excl)]);
                } else {
                    $mnt_exe = $mnt_exe + $order->total_shipping_tax_excl;
                    $items = array_merge((array)$items, ['MontoItem' => round((int)($order->total_shipping_tax_excl))]);
                    $items = array_merge((array)$items, ['IndExe' => 1]);
                }
            } else {
                if ($freeShipping) {
                    //$mnt_total = $mnt_total + 1;
                    $items = array_merge((array)$items, ['MontoItem' => (int)(0)]);
                    $items = array_merge((array)$items, ['DescuentoMonto' => round($order->total_shipping_tax_excl)]);
                } else {
                    $mnt_total = $mnt_total + $order->total_shipping_tax_excl;
                    $items = array_merge((array)$items, ['PrcItem' => ($order->total_shipping_tax_excl)]);
                    $items = array_merge((array)$items, ['MontoItem' => round((int)($order->total_shipping_tax_excl))]);
                }
            }
            $i++;
            array_push($detalle, $items);
        }

        if ($is_exe) {
            $id_doc = array("FchEmis" => $date);
            $totales = array(
                "MntTotal" => intval($order->total_paid),
                'MntExe' => intval($mnt_exe)
            );
        } elseif ($is_afecta) {
            if ($mnt_total <= 3 && $freeShipping) {
                $iva = 1;
            } else {
                $iva = round($mnt_total * 0.19, 1);
                $iva = (int)($iva);
            }
            $id_doc = array("FchEmis" => $date, "IndMntNeto" => 2);
            $totales = array(
                "MntNeto" => intval($mnt_total),
                "TasaIVA" => "19.00",
                "IVA" => $iva,
                "MntTotal" => intval($order->total_paid),
                'MntExe' => (int)($mnt_exe)
            );
        }

        $emisor = array(
            "RUTEmisor" => $openfacturaRegistryActive['rut'],
            "RznSocEmisor" => $openfacturaRegistryActive['razon_social'],
            "GiroEmisor" => $openfacturaRegistryActive['glosa_descriptiva'],
            "CdgSIISucur" => $openfacturaRegistryActive['cdgSIISucur'],
            "DirOrigen" => $openfacturaRegistryActive['direccion_origen'],
            "CmnaOrigen" => $openfacturaRegistryActive['comuna_origen'],
            "Acteco" => $openfacturaRegistryActive['codigo_actividad_economica_active']
        );
        $dte = array();
        $dte["dte"] = [
            "Encabezado" =>
                [
                "IdDoc" => $id_doc,
                "Emisor" => $emisor,
                "Totales" => $totales
            ], "Detalle" => $detalle
        ];
        $custom['custom'] = [
            'origin' => 'PRESTASHOP'
        ];

        $document_send = array_merge($document_send, $response);
        $document_send = array_merge($document_send, $customer);
        $document_send = array_merge($document_send, $customize_page);
        $document_send = array_merge($document_send, $self_service);
        $document_send = array_merge($document_send, $dte);
        $document_send = array_merge($document_send, $custom);
        $document_send = json_encode($document_send, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        PrestaShopLogger::addLog($document_send, 1);
        //generate document
        $url_generate = '';
        if ($openfacturaRegistryActive['is_demo'] == "1") {
            //dev environment
            $url_generate = 'https://dev-api.haulmer.com/v2/dte/document';
        } else {
            //prod environment
            $url_generate = 'https://api.haulmer.com/v2/dte/document';
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url_generate,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $document_send,
            CURLOPT_HTTPHEADER => array(
                "Content-type: application/json",
                "apikey:" . $openfacturaRegistryActive['apikey'],
                "Idempotency-Key:" . $order->id
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        PrestaShopLogger::addLog(json_encode(json_decode($response, JSON_PRETTY_PRINT)), 1);
        $response = json_decode($response, true);
        $id_order = $orderDetail[0]['id_order'];
        $id_order_invoice = $orderDetail[0]['id_order_invoice'];

        $urlSS = isset($response["SELF_SERVICE"]) ? $response["SELF_SERVICE"]["url"] : null;
        try {
            if (isset($urlSS)) {
                $note = $urlSS;
                PrestaShopLogger::addLog('NOTA: ' . $note, 1);
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET 
                note="Obten tu documento tributario en: ' . pSQL($note) . '"
                WHERE id_order_invoice="' . (int)$id_order_invoice . '" and id_order="' . (int)$id_order . '"';
                Db::getInstance()->Execute($sql);
            } else {
                PrestaShopLogger::addLog(json_encode($response, JSON_PRETTY_PRINT), 3);
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET 
                note="Su documento tributario no se pudo generar."
                WHERE id_order_invoice="' . (int)$id_order_invoice . '" and id_order="' . (int)$id_order . '"';
                Db::getInstance()->Execute($sql);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 3);
        }

        return true;
    }
}
