<?php

/**
 * 2022 Haulmer
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
 * @author      Haulmer <soporte@haulmer.com>
 * @copyright   2023 Haulmer
 * @license     license.txt
 * @version:    1.0.5
 * @Date: 22-01-2020 11:00
 * @Last Modified by: Sebastián Cancino Ramos - Junior Ing. SW
 * @Last Modified time: 09-11-2023
 */
class Openfactura extends Module
{
    /**
     * Constructor of OpenFactura Class
     * @access public
     * @since Release 1.0.0
     */
    public function __construct()
    {
        $this->name = 'openfactura';
        $this->tab = 'front_office_features';
        $this->version = '3.0.0';
        $this->author = 'Haulmer';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '424d25a5df7d60490e8cc1972b0a8bee';

        parent::__construct();

        $this->displayName = 'OpenFactura';
        $this->description = $this->l('Automate the issuance of tickets and / or invoices in your ecommerce');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the module?');
    }

    /**
     * Installer function. Create the database and fill with demo data.
     * @access public
     * @since Release 1.0.0
     */
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

    /**
     * Uninstaller function. Drop the OpenFactura table on the PrestaShop's database.
     * @access public
     * @since Release 1.0.0
     */
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

    /**
     * Uninstaller Tab function. Delete the admin tab.
     * @access public
     * @since Release 1.0.0
     */
    public function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminOpenFactura');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    /**
     * Insert demo data of OpenFactura on the PrestaShop's database.
     * @access public
     * @since Release 1.0.0
     */
    public function insertDemoData()
    {
        $apikey = '928e15a2d14d4a6292345f04960f4bd3';
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
     * @access public
     * @since Release 1.0.0
     */
    public function hookActionOrderStatusPostUpdate(array $params)
    {
        if (!empty($params['newOrderStatus'])) {
            if (
                $params['newOrderStatus']->id == Configuration::get('PS_OS_WS_PAYMENT') ||
                $params['newOrderStatus']->id == Configuration::get('PS_OS_PAYMENT')
            ) {
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

    /**
     * Get a specific prodcut of a array of products.
     * @param array $products Array of products of an order.
     * @param int $id_to_find ID of a product thats need to find in an array of products.
     * @return object
     * @access private
     * @since Release 1.1.0-beta
     */
    private function getProductByID($products, $id_to_find)
    {
        foreach ($products as $product) {
            if ($product['product_id'] == $id_to_find) {
                return $product;
            }
        }
    }

    /**
     * Get the restrictions of a product with some cart rule.
     * @param int $product_id ID of a product to find on the database.
     * @param int $cartRule_id ID of a cart to find on the database.
     * @return object
     * @access private
     * @since Release 1.1.0-beta
     */
    private function getRestrictions($product_id, $cartRule_id)
    {
        $result = Db::getInstance()->executeS(
            'SELECT *, rg.quantity as minimum_quantity
            FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_value` cv
            LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule` pr
            ON cv.id_product_rule = pr.id_product_rule
            LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` rg
            ON pr.id_product_rule_group = rg.id_product_rule_group
            LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule` cr ON cr.id_cart_rule = rg.id_cart_rule
            WHERE cv.id_item = ' . (int)$product_id . ' and  cr.id_cart_rule=' . (int)$cartRule_id . ''
        );
        return $result;
    }

    private function getDiscountsDb($order_id, $id_cart_rule)
    {
        $query = "SELECT value AS value_real, value_tax_excl AS value_tax_exc " .
                 'FROM ' . _DB_PREFIX_ . 'order_cart_rule ' .
                 "WHERE id_order = {$order_id} AND id_cart_rule = {$id_cart_rule}";
        return Db::getInstance()->executeS($query)[0];
    }

    /**
     * Main function. Works with all the logic of the Products, Cart Rules and Restrictions to send the DTE.
     * @param object $order An order with all the info about the purchase of a client.
     * @param object $customer_detail The info of the customer.
     * @param object $openfacturaRegistryActive The info of the seller or the own of the shop.
     * @return boolean If the hook succeeds sent the DTE return true and sent to
     *                 the PrestaShopLogger the token and the link to see the DTE.
     * @throws Exception If the hook can't send the DTE to the backend throw a exception with the reason.
     * @access public
     * @since Release 1.1.0-beta
     */
    public function createJsonOpenFactura($order, $customer_detail, $openfacturaRegistryActive)
    {
        // Set response config to SELF_SERVICE
        $response = array();
        $response['response'] = ['SELF_SERVICE'];

        // Set customer information.
        $customer = array();
        $customer['customer'] = [
            "fullName" => Tools::substr($customer_detail->firstname . " " . $customer_detail->lastname, 0, 100),
            "email" => Tools::substr($customer_detail->email, 0, 80)
        ];

        // Set customize config from the company like the logo
        $customize_page = array();
        $urlHistory = $this->context->link->getPageLink('order-detail', true, null, ['id_order' => $order->id]);

        if (!empty($openfacturaRegistryActive['link_logo']) && $openfacturaRegistryActive['show_logo']) {
            $customize_page["customizePage"] = [
                "urlLogo" => $openfacturaRegistryActive['link_logo'],
                'externalReference' => [
                    "hyperlinkText" => "Orden de Compra #" . $order->id,
                    "hyperlinkURL" => $urlHistory
                ]
            ];
        } else {
            $customize_page["customizePage"] = [
                'externalReference' => [
                    "hyperlinkText" => "Orden de Compra #" . $order->id,
                    "hyperlinkURL" => $urlHistory
                ]
            ];
        }

        // Config about if allow 'Boleta' and if allow 'Factura'
        $generate_boleta = boolval($openfacturaRegistryActive['generate_boleta']);
        $allow_factura = boolval($openfacturaRegistryActive['allow_factura']);

        // Config the Self Service
        $date = date('Y-m-d');
        $self_service = array();
        $self_service["selfService"] = [
            "issueBoleta" => $generate_boleta,
            "allowFactura" => $allow_factura,
            "documentReference" => [
                [
                    "type" => "801",
                    "ID" => $order->id,
                    "date" => $date,
                ]
            ]
        ];

        // Get the objects with info about the rules, products and order
        $products = $order->getProducts();
        $orderDetail = $order->getOrderDetailList();
        $cart = new Cart($order->id_cart);
        $cartRules = $cart->getCartRules();

        /**
         * Finds the cheapest product. Prestashop has the option to give a
         * percentage discount to the cheapest option, but it doesn't return in
         * any of the order's items what product was the cheapest. Needed in
         * case there is such discount.
         */
        $minimumAmount = 999999999999;
        $cheapest_product_id = '0';
        foreach ($products as $product) {
            if ($product['total_price_tax_excl'] < $minimumAmount) {
                $minimumAmount = $product['total_price_tax_excl'];
                $cheapest_product_id = $product['product_id'];
            }
        }

        // Document is affected flag.
        $bill_is_afe = false;

        // Net affected accumulator. This is total without taxes.
        $amount_products_exclude_taxes = 0;

        // Net exempt accumulator.
        $amount_exempt_products = 0;

        // 'Document has exempt item' flag. Name is misleading.
        $only_exe = false;
        // 'Document has affected item' flag. Name is misleading.
        $only_afe = false;

        // These are used to determine discount proportions. Total accumulators.
        $net_affected = 0;
        $net_exempt = 0;

        // Document details array and it's counter. Used to construct the dte.
        $detail = array();
        $line = 1;

        // Iterate the list of products
        foreach ($products as $product) {
            // Extract the important info about the actual product in the detail
            $product_id = $product['product_id'];
            $product_name = $product['product_name'];
            $product_quantity = $product['product_quantity'];

            // Unit price of the product with taxes (if it has, in other case is the same that excl)
            $unit_price_tax_incl = $product['unit_price_tax_incl'];

            // Total price of item including taxes, this value is unit price x quantity
            $total_price_tax_incl = $product['total_price_tax_incl'];

            // Prestashop doesn't round decimal values correctly. It seems that
            // it has problems handling floating precision errors.
            $total_price_tax_incl = round($total_price_tax_incl);

            // Total price of item excluding taxes, this value is unit price x quantity
            $total_price_tax_excl = $product['total_price_tax_excl'];

            // Check if item is tax free
            $exemptItem = intval(round($product['total_price_tax_incl'] - $product['total_price_tax_excl'])) === 0;

            // Setting the basic info of the product
            $items = array();
            $items = [
                'NroLinDet'     => $line,
                'NmbItem'       => substr($product_name, 0, 80),
                'QtyItem'       => $product_quantity,
                'PrcItem'       => $unit_price_tax_incl,
            ];

            // Verify if the product has a code of reference and give it to the config
            // Commented: this value is optional
            /*if (!empty($product_reference)) {
                $items['CdgItem'] = [
                    'TpoCodigo' => 'INT1',
                    'VlrCodigo' => $product_reference,
                ];
            }*/

            /**
             * Check if the product has specific product discounts.
             * IMPORTANT: For some reason, the orders items sometimes don't
             * return the correct values of how much discount a specific item
             * has. To solve the issue, these values are obtained from the db,
             * where they are correct.
             */
            $net_discount = 0;
            $total_discount = 0;
            foreach ($cartRules as $rule) {
                // Specific Product Discount Percentage
                if (
                    $rule['reduction_product'] == $product_id &&
                    $rule['reduction_percent'] != 0
                ) {
                    $discounts = $this->getDiscountsDb($order->id, $rule['id_cart_rule']);
                    $net_discount = $net_discount + $discounts['value_tax_exc'];
                    $total_discount = $total_discount + $discounts['value_real'];
                }

                // Specific Product Discount Amount
                if (
                    $rule['reduction_product'] == $product_id &&
                    $rule['reduction_amount'] != 0
                ) {
                    $discounts = $this->getDiscountsDb($order->id, $rule['id_cart_rule']);
                    $net_discount = $net_discount + $discounts['value_tax_exc'];
                    $total_discount = $total_discount + $discounts['value_real'];
                }

                /**
                 * Cheapest Product Discount
                 * IMPORTANT: When the prop reduction product has a value of -1,
                 * it means the discount is for the cheaper product but the
                 * discount doesn't apply to the total of products, just one is
                 * affected by the discount
                 */
                if (
                    $rule['reduction_product'] == "-1" &&
                    $cheapest_product_id == $product_id
                ) {
                    $discounts = $this->getDiscountsDb($order->id, $rule['id_cart_rule']);
                    $net_discount = $net_discount + $discounts['value_tax_exc'];
                    $total_discount = $total_discount + $discounts['value_real'];
                }

                /**
                 * Selected Products Discount
                 * It is similar to specific discount product but it's for the
                 * selected products options, so check this feature in the admin panel.
                 */
                if ($rule['reduction_product'] == "-2") {
                    $restrictions = $this->getRestrictions($product_id, $rule['id_cart_rule']);
                    if ($restrictions[0]['reduction_percent'] != 0) {
                        $discounts = $this->getDiscountsDb($order->id, $rule['id_cart_rule']);

                        // Here the original value_real from the cart is useful
                        // to get the item's original price and determine the
                        // item's proportion of the discount.
                        $real_percent = $restrictions[0]['reduction_percent'] / 100;
                        $total_applied_discount = $rule['value_real'] / $real_percent;
                        $proportion = $total_price_tax_incl / $total_applied_discount;

                        $net_discount = $net_discount + $discounts['value_tax_exc'] * $proportion;
                        $total_discount = $total_discount + $discounts['value_real'] * $proportion;
                    }
                }
            }

            // Apply discounts
            if ($exemptItem) {
                $only_exe = true;
                $amount_exempt_products = $amount_exempt_products - $net_discount;
                $net_exempt = $net_exempt + $total_price_tax_excl;
            } else {
                $only_afe = true;
                $amount_products_exclude_taxes = $amount_products_exclude_taxes - $net_discount;
                $net_affected = $net_affected + $total_price_tax_incl;
            }

            /**
             * Verify if the product has taxes
             * IndExe = 1 is exempt of IVA. If it has IVA dont set IndExe
             */
            if ($exemptItem) {
                $items['IndExe'] = 1;
                $amount_exempt_products = $amount_exempt_products + $total_price_tax_excl;
            } else {
                $bill_is_afe = true;
                $amount_products_exclude_taxes = $amount_products_exclude_taxes + $total_price_tax_excl;
            }

            // Add the discount to the bill if it is different of 0
            if ($total_discount != 0) {
                $items['DescuentoMonto'] = round($total_discount);
            }

            // Add the final amount of the item
            $items['MontoItem'] = $total_price_tax_incl - round($total_discount);
            $line++;
            if ($items['MontoItem'] < 0) {
                PrestaShoplogger::addLog(
                    "Error en el monto del item " .
                    $items['NmbItem'] . ": $" . $items['MontoItem'] .
                    ". Posible causa: Demasiados descuentos aplicados a un producto.",
                    4
                );
            }

            array_push($detail, $items);
        };

        /**
         * Shipping values used to determine if there's shipping in discounts handling.
         * These values will also be used after the discounts are handled.
         */
        $total_shipping = intval(round($order->total_shipping));
        $hasShipping = $total_shipping != 0;
        $exemptShipping = intval(round($order->total_shipping_tax_incl - $order->total_shipping_tax_excl)) === 0;
        $total_shipping_tax_incl = intval(round($order->total_shipping_tax_incl));
        $total_shipping_tax_excl = intval(round($order->total_shipping_tax_excl));

        /*
         * If there's exempt and affected items in the order, the general
         * discounts will have a certain amount that's affeced by tax and another
         * that's not affected by tax. These amounts are proportional to the
         * order total. These values keep the proportion that's affected and
         * exempt, and are updated each time a discount is applied.
         */
        $affected_proportion = $net_affected / ($net_exempt + $net_affected);
        $exempt_proportion = 1 - $affected_proportion;

        /**
         * Iterate through the cart rules and check if there are discounts
         * The prop 'reduction_product' defines the type of discount.
         * If the value is:
         *  (-1) -> the discount is on the cheaper
         *  (0)  -> the discount is on general cart
         *  (X)  -> the discount is in the prod id X
         */
        $DscRcgGlobal = array();
        $line2 = 1;
        foreach ($cartRules as $rule) {
            /**
             * General discount: Gift product.
             */
            if ($rule['gift_product'] > 0) {
                $product = $this->getProductByID($products, $rule['gift_product']);
                $exemptItem = intval(round($product['total_price_tax_incl'] - $product['total_price_tax_excl'])) === 0;
                $discount_item = array(
                    'NroLinDR' => $line2,
                    'TpoMov' => 'D',
                    'GlosaDR' => substr('Producto de Regalo - ' . $rule['code'], 0, 45),
                    'TpoValor' => '$',
                    'ValorDR' => round($product['unit_price_tax_incl']),
                );

                if ($exemptItem) {
                    $discount_item['IndExeDR'] = 1;
                    $amount_exempt_products = $amount_exempt_products - $product['unit_price_tax_excl'];
                    $net_exempt = $net_exempt - $product['unit_price_tax_excl'];
                } else {
                    $amount_products_exclude_taxes  = $amount_products_exclude_taxes - $product['unit_price_tax_excl'];
                    $net_affected = $net_affected - $product['unit_price_tax_excl'];
                }

                $affected_proportion = $net_affected / ($net_exempt + $net_affected);
                $exempt_proportion = 1 - $affected_proportion;

                array_push($DscRcgGlobal, $discount_item);
                $line2++;
            }

            /**
             * General discount: Free shipping
             */
            if ($rule['free_shipping'] == "1") {
                $discount_item = array(
                    'NroLinDR' => $line2,
                    'TpoMov' => 'D',
                    'GlosaDR' => substr('Envío Gratis - ' . $rule['code'], 0, 45),
                    'TpoValor' => '$',
                    'ValorDR' => $order->total_shipping_tax_incl,
                );

                if ($exemptShipping) {
                    $discount_item['IndExeDR'] = 1 ;
                    $amount_exempt_products = $amount_exempt_products - $total_shipping_tax_excl;
                } else {
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - $total_shipping_tax_excl;
                }

                $line2++;
                array_push($DscRcgGlobal, $discount_item);
            }

            /**
             * General discount: percentage discount
             */
            if ($rule['reduction_product'] == "0" && $rule['reduction_percent'] != 0) {
                /**
                 * Percentage discount with only affected items in document
                 */
                if ($only_afe && !$only_exe) {
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr(
                            "Dscto. " . ($hasShipping ? '(excluye envío) ' : '') .
                            "{$rule['reduction_percent']}% - {$rule['code']}",
                            0,
                            45
                        ),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($rule['value_real']),
                    );
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - $rule['value_tax_exc'];
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }

                /**
                 * Percentage discount with only exempt items in document
                 */
                if (!$only_afe && $only_exe) {
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr(
                            "Dscto. " . ($hasShipping ? '(excluye envío) ' : '') .
                            "{$rule['reduction_percent']}% - {$rule['code']}",
                            0,
                            45
                        ),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($rule['value_real']),
                        'IndExeDR'  => 1,
                    );
                    $amount_exempt_products = $amount_exempt_products - $rule['value_tax_exc'];
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }

                /**
                 * Percentage discount with affected and exempt items in document
                 */
                if ($only_afe && $only_exe) {
                    $dscto_amount_exempt = $exempt_proportion * $rule['value_real'];
                    $dscto_amount_neto = $affected_proportion * $rule['value_real'];
                    $iva = $dscto_amount_neto - ($dscto_amount_neto / 1.19);

                    $amount_exempt_products = $amount_exempt_products - $dscto_amount_exempt;
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - ($dscto_amount_neto - $iva);

                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr(
                            "Dscto. afectos " . ($hasShipping ? '(excluye envío) ' : '') .
                            "{$rule['reduction_percent']}% - {$rule['code']}",
                            0,
                            45
                        ),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($dscto_amount_neto),
                    );

                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);

                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr(
                            "Dscto. exentos " . ($hasShipping ? '(excluye envío) ' : '') .
                            "{$rule['reduction_percent']}% - {$rule['code']}",
                            0,
                            45
                        ),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($dscto_amount_exempt),
                        'IndExeDR'  => 1,
                    );

                    $net_affected = $net_affected - $dscto_amount_neto;
                    $net_exempt = $net_exempt - $dscto_amount_exempt;

                    $affected_proportion = $net_affected / ($net_exempt + $net_affected);
                    $exempt_proportion = 1 - $affected_proportion;

                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }
            }

            /**
             * General discount: net discount
             */
            if ($rule['reduction_product'] == "0" &&  $rule['reduction_amount'] != 0) {
                /**
                 * Only Afected Products
                 */
                if ($only_afe && !$only_exe) {
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - $rule['value_tax_exc'];
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr('Descuento General - ' . $rule['code'], 0, 45),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($rule['value_real'], 2),
                    );
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }

                /**
                 * Only Exempts Products
                 */
                if (!$only_afe && $only_exe) {
                    $amount_exempt_products = $amount_exempt_products - $rule['value_tax_exc'];
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr('Descuento General - ' . $rule['code'], 0, 45),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($rule['value_real'], 2),
                        'IndExeDR'  => 1,
                    );
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }

                /**
                 * Afected and exempts products
                 */
                if ($only_afe && $only_exe) {
                    $dscto_amount_exempt = $exempt_proportion * $rule['value_real'];
                    $dscto_amount_neto = $affected_proportion * $rule['value_real'];
                    $iva = $dscto_amount_neto - ($dscto_amount_neto / 1.19);

                    $amount_exempt_products = $amount_exempt_products - $dscto_amount_exempt;
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - ($dscto_amount_neto - $iva);

                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr('Descuento General - ' . $rule['code'], 0, 45),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($dscto_amount_neto),
                    );

                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);

                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => substr('Descuento General - ' . $rule['code'], 0, 45),
                        'TpoValor'  => '$',
                        'ValorDR'   => round($dscto_amount_exempt),
                        'IndExeDR'  => 1,
                    );

                    $net_affected = $net_affected - $dscto_amount_neto;
                    $net_exempt = $net_exempt - $dscto_amount_exempt;

                    $affected_proportion = $net_affected / ($net_exempt + $net_affected);
                    $exempt_proportion = 1 - $affected_proportion;

                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }
            }
        }

        /**
         * Manage shipping.
         * Shipping is another item in the bill. That's why it's included in the
         * details and checks if it has taxes or not.
         *
         * IMPORTANT: Prestashop excludes shipping when applying general discounts.
         * It seems that it doesn't have the option to include shipping in
         * general percentage discount out of the box, so that operation mode is
         * assumed. If in the future there's a case where a client is having
         * issues related to shipping discount, check if they have a module
         * adding that functionality.
         */
        $items = array();
        if ($hasShipping) {
            $items = [
                'NroLinDet'     => $line,
                'NmbItem'       => "Envío",
                'QtyItem'       => 1,
                'PrcItem'       => $total_shipping_tax_incl,
                'MontoItem'     => $total_shipping_tax_incl
            ];

            if ($exemptShipping) {
                $items['IndExe'] = 1;
                $amount_exempt_products = $amount_exempt_products + $total_shipping_tax_excl;
            } else {
                $bill_is_afe = true;
                $amount_products_exclude_taxes = $amount_products_exclude_taxes + $total_shipping_tax_excl;
            }

            $line++;
            array_push($detail, $items);
        }

        // End the config with the total amounts
        if ($bill_is_afe) {
            $totales = array(
                "MntNeto" => round($amount_products_exclude_taxes),
                'MntExe' => round($amount_exempt_products),
                "TasaIVA" => "19.000",
                "IVA" => round($amount_products_exclude_taxes * 0.19),
                "MntTotal" => round($order->total_paid_tax_incl),
            );
        } else {
            $totales = array(
                'MntExe' => round($amount_exempt_products),
                "MntTotal" => round($order->total_paid_tax_incl),
            );
        }

        // Final config and prepare the final json to send to the API to register the bill
        $emisor = array(
            "RUTEmisor" => $openfacturaRegistryActive['rut'],
            "RznSocEmisor" => $openfacturaRegistryActive['razon_social'],
            "GiroEmisor" => $openfacturaRegistryActive['glosa_descriptiva'],
            "CdgSIISucur" => $openfacturaRegistryActive['cdgSIISucur'],
            "DirOrigen" => $openfacturaRegistryActive['direccion_origen'],
            "CmnaOrigen" => $openfacturaRegistryActive['comuna_origen'],
            "Acteco" => $openfacturaRegistryActive['codigo_actividad_economica_active']
        );

        $id_doc = array(
            "FchEmis" => $date,
        );

        $dte = array();
        $dte["dte"] = [
            "Encabezado" => [
                "IdDoc" => $id_doc,
                "Emisor" => $emisor,
                "Totales" => $totales,
            ],
            "Detalle" => $detail,
            "DscRcgGlobal" => $DscRcgGlobal,
        ];
        $custom['custom'] = [
            'origin' => 'PRESTASHOP'
        ];

        $json_bitmask = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        PrestaShopLogger::addLog("Order: " . json_encode($order, $json_bitmask), 1);
        PrestaShopLogger::addLog("Products: " . json_encode($products, $json_bitmask), 1);
        PrestaShopLogger::addLog("Cart Rules: " . json_encode($cartRules, $json_bitmask), 1);

        /**
         * Assemble the final object with the info of the differents items in the DTE
         * Changes: Idempotency-key changed.
         * Reason: The last config only use the order's id and caused problems
         * when the store was restarted.
         */
        $document_send = array();
        $document_send = array_merge($document_send, $response);
        $document_send = array_merge($document_send, $customer);
        $document_send = array_merge($document_send, $customize_page);
        $document_send = array_merge($document_send, $self_service);
        $document_send = array_merge($document_send, $dte);
        $document_send = array_merge($document_send, $custom);
        $document_send = json_encode($document_send, $json_bitmask);

        PrestaShopLogger::addLog("SS: {$document_send}");

        $url_generate = '';
        if ($openfacturaRegistryActive['is_demo'] == "1") {
            $url_generate = 'https://dev-api.haulmer.com/v2/dte/document';
        } else {
            $url_generate = 'https://api.haulmer.com/v2/dte/document';
        }

        $order_timestamp = date('Y-m-d H:i:s', strtotime($order->invoice_date));
        $idemKey = "PRESTASHOP_{$openfacturaRegistryActive['rut']}_{$order_timestamp}_{$order->id}";

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
                "Idempotency-Key:" . $idemKey,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        $id_order = $orderDetail[0]['id_order'];
        $id_order_invoice = $orderDetail[0]['id_order_invoice'];
        $urlSS = isset($response["SELF_SERVICE"]) ? $response["SELF_SERVICE"]["url"] : null;
        $urlSS = str_replace("https://", "\n", $urlSS);

        try {
            if (!is_null($urlSS)) {
                $note = $urlSS;
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET 
                note="Obten tu documento tributario en: ' . pSQL($note) . '"
                WHERE id_order_invoice="' . (int)$id_order_invoice . '" and id_order="' . (int)$id_order . '"';
                Db::getInstance()->Execute($sql);
                PrestaShopLogger::addLog("Su documento tributario se generó correctamente, " .
                "puede encontrar el link para acceder en la factura generada por PrestaShop y en el Log superior", 1);
            } else {
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET 
                note="Su documento tributario no se pudo generar."
                WHERE id_order_invoice="' . (int)$id_order_invoice . '" and id_order="' . (int)$id_order . '"';
                Db::getInstance()->Execute($sql);
                PrestaShopLogger::addLog("Su documento tributario no se pudo generar, " .
                "favor revisar los logs superiores y contactarse con soporte", 3);
            }
            PrestaShopLogger::addLog("Response: " . json_encode($response, JSON_PRETTY_PRINT), 1);
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 3);
        }

        return true;
    }
}
