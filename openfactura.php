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
 * @copyright   2022 Haulmer
 * @license     license.txt
 * @version:    1.0.3
 * @Date: 22-01-2020 11:00
 * @Last Modified by: Vicente Rojas Aranda - Practicante Ing. SW
 * @Last Modified time: 14-01-2022 
 */

if (!defined('_PS_VERSION_')) {
    exit; // Exit if accessed directly
}

class Openfactura extends Module{

    /**
     * Constructor of OpenFactura Class
     * @access public
     * @since Release 1.0.0
     */
    function __construct(){
        $this->name = 'openfactura';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0';
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
    function install(){
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
    function uninstall(){
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
    function uninstallTab(){
        $id_tab = (int)Tab::getIdFromClassName('AdminOpenFactura');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    /**
     * Insert demo data of OpenFactura on the PrestaShop's database.
     * @access public
     * @since Release 1.0.0
     */
    function insertDemoData(){
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
    function hookActionOrderStatusPostUpdate(array $params){
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

    /**
     * Get a specific prodcut of a array of products.
     * @param array $products Array of products of an order.
     * @param int $id_to_find ID of a product thats need to find in an array of products.
     * @return object
     * @access private
     * @since Release 1.1.0-beta
     */
    private function getProductByID($products, $id_to_find){
        foreach ($products as $product) {
            if($product['product_id'] == $id_to_find){
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
    private function getRestrictions($product_id, $cartRule_id){
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

    /**
     * Main function. Works with all the logic of the Products, Cart Rules and Restrictions to send the DTE.
     * @param object $order An order with all the info about the purchase of a client.
     * @param object $customer_detail The info of the customer.
     * @param object $openfacturaRegistryActive The info of the seller or the own of the shop.
     * @return boolean If the hook succeeds sent the DTE return true and sent to the PrestaShopLogger the token and the link to see the DTE.
     * @throws Exception If the hook can't send the DTE to the backend throw a exception with the reason.
     * @access public
     * @since Release 1.1.0-beta
     */
    function createJsonOpenFactura($order, $customer_detail, $openfacturaRegistryActive){

#       ╔═════════════════════════════════════╗
#       ║ Set response config to SELF_SERVICE ║
#       ╚═════════════════════════════════════╝        
        $response = array();
        $response['response'] = ['SELF_SERVICE'];

#       ╔═════════════════════════════════════╗
#       ║       Set customer information      ║
#       ╚═════════════════════════════════════╝
        /**
         * Changes: the last version verify if the fields: firstname, lastname
         *          and email whether or not they exist but PrestaShop
         *          already verify these fields when a customer buy something
         *          or create an account.
         */
        $customer = array();
        $customer['customer'] = [
            "fullName" => Tools::substr($customer_detail->firstname . " " . $customer_detail->lastname, 0, 100),
            "email" => Tools::substr($customer_detail->email, 0, 80)
        ];

#       ╔═════════════════════════════════════╗
#       ║    Set customize config from the    ║
#       ║        company like the logo        ║
#       ╚═════════════════════════════════════╝
        /**
         * Changes: Nothing
         */
        $customize_page = array();
        $urlHistory = $this->context->link->getPageLink('history', true);
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

#       ╔═════════════════════════════════════╗
#       ║   Config about if allow 'Boleta'    ║
#       ║        and if allow 'Factura'       ║
#       ╚═════════════════════════════════════╝
        /**
         * Changes: The last version verify with a if condition and assign
         *          true or false to a variable.
         */
        $generate_boleta = boolval($openfacturaRegistryActive['generate_boleta']);
        $allow_factura = boolval($openfacturaRegistryActive['allow_factura']);


#       ╔═════════════════════════════════════╗
#       ║       Config the Self Service       ║
#       ╚═════════════════════════════════════╝
        /**
         * Changes: Nothing
         */
        $date = date('Y-m-d');
        $self_service = array();
        $self_service["selfService"] = [
            "issueBoleta" => $generate_boleta,
            "allowFactura" => $allow_factura,
            "documentReference" => [
                "type" => "801",
                "ID" => $order->id,
                "date" => $date,
            ],
        ];

#       ╔═════════════════════════════════════╗
#       ║ Get the objects with the info about ║
#       ║  the rules, products and the order  ║
#       ╚═════════════════════════════════════╝
        $products = $order->getProducts();
        $orderDetail = $order->getOrderDetailList();
        $cart = new Cart($order->id_cart);
        $cartRules = $cart->getCartRules();

#       ╔═════════════════════════════════════╗
#       ║  Find the cheaper product if exist  ║
#       ║  a discount in the cheaper product  ║
#       ╚═════════════════════════════════════╝
        /**
         * Changes: Nothing
         */
        $minimumAmount = 999999999999;
        $cheaper_product_id = '0';
        foreach ($products as $product) {
            if ($product['total_price_tax_excl'] < $minimumAmount) {
                $minimumAmount = $product['total_price_tax_excl'];
                $cheaper_product_id = $product['product_id'];
            }
        }

#       ╔═════════════════════════════════════╗
#       ║ Iterate across the list of products ║
#       ╚═════════════════════════════════════╝
        /**
         * Changes: Everything... change the logic
         *          about how to work the amount
         *          of the products prices and their
         *          discounts,now notify if the product
         *          has an internal discount.
         */
        $bill_is_afe = false;
        $amount_products_exclude_taxes = 0; # <- Amount accumulated of the products that have taxes, but only the price without it
        $amount_exempt_products = 0;        # <- Amount accumulated of the products exempts of taxes
        $detail = array();
        $line = 1;

        $only_exe = false; #   ╔════════════════════════════════════════════════════════════════════╗
        $only_afe = false; #   ║ This vars are just for the general amount discount [See line #700] ║
        $tax_discount = 0; #   ╚════════════════════════════════════════════════════════════════════╝

        foreach($products as $product){

#           ╔════════════════════════════════════╗
#           ║  Extract the important info about  ║
#           ║  the actual product in the detail  ║
#           ╚════════════════════════════════════╝

            $product_id = $product['product_id'];                           # <- Product ID
            $product_name = $product['product_name'];                       # <- Name of the product
            $product_reference = $product['product_reference'];             # <- Product reference or product code
            $product_quantity = $product['product_quantity'];               # <- Quantity of the product
            $original_product_price = $product['original_product_price'];   # <- Original price of the product without discounts and without taxes (if has it)
            $unit_price_tax_incl = $product['unit_price_tax_incl'];         # <- Unit price of the product with taxes (if has it, in other case is the same that excl)
            $unit_price_tax_excl = $product['unit_price_tax_excl'];         # <- Unit price of the product without taxes
            $total_price_tax_incl = $product['total_price_tax_incl'];       # <- Total price of the item include taxes, this value is the unite price multiplied with the quantity
            $total_price_tax_excl = $product['total_price_tax_excl'];       # <- Total price of the item exclude taxes, this value is the unite price multiplied with the quantity
            $tax_name = $product['tax_name'];                               # <- Name of the tax of the product, if doesn't have the value is empty
            $tax_rate = $product['tax_rate'];                               # <- Tax rate of the product, if doesn't have the value is 0
            
#           ╔════════════════════════════════════╗
#           ║ Setting the basic info of the prod ║
#           ╚════════════════════════════════════╝
            $items = array();
            $items = [
                'NroLinDet'     => $line,
                'NmbItem'       => $product_name,
                'QtyItem'       => $product_quantity,
                'PrcItem'       => $unit_price_tax_excl,
            ];

#           ╔═════════════════════════════════════╗
#           ║ Verify if the product has a code of ║
#           ║ of reference and give to the config ║
#           ╚═════════════════════════════════════╝
            if(!empty($product_reference)){
                $items['CdgItem'] = [
                    'TpoCodigo' => 'INT1',
                    'VlrCodigo' => $product_reference,
                ];
            }

#           ╔════════════════════════════════════╗
#           ║  Check if the product has specific ║
#           ║         product discounts          ║
#           ╚════════════════════════════════════╝
            $discount_amount = 0;
            foreach ($cartRules as $rule) {

#               ╔═══════════════════════════╗
#               ║ Specific Product Discount ║
#               ║         Percentage        ║
#               ╚═══════════════════════════╝
                if($rule['reduction_product'] == $product_id && $rule['reduction_percent'] != 0){
                    $discount_amount = $discount_amount + round($rule['value_tax_exc']);
                }

#               ╔═══════════════════════════╗
#               ║ Specific Product Discount ║
#               ║           Amount          ║
#               ╚═══════════════════════════╝
                if($rule['reduction_product'] == $product_id && $rule['reduction_amount'] != 0){
                    $discount_amount = $discount_amount + round($rule['value_tax_exc']);
                }

#               ╔══════════════════════════╗
#               ║ Cheaper Product Discount ║
#               ╠══════════════════════════╣
#               ║         IMPORTANT        ║
#               ║  When the prop reduction ║
#               ║  product has a value of  ║
#               ║  -1, means the discount  ║
#               ║  is for the cheaper prod ║
#               ║ but the discount doesn't ║
#               ║  apply to the total of   ║
#               ║   products, just one is  ║
#               ║ affected by the discount ║
#               ╚══════════════════════════╝
                if($rule['reduction_product'] == "-1" && $cheaper_product_id == $product_id){
                    $discount_amount = $discount_amount + round($rule['value_tax_exc']);
                }

#               ╔════════════════════════════╗
#               ║ Selected Products Discount ║
#               ╠════════════════════════════╣
#               ║   Is similar to specific   ║
#               ║  discount product but its  ║
#               ║  for the selected products ║
#               ║   options, so check this   ║
#               ║ feature in the admin panel ║
#               ╚════════════════════════════╝
                if($rule['reduction_product'] == "-2"){
                    $restrictions = $this->getRestrictions($product_id, $rule['id_cart_rule']);
                    if($restrictions[0]['reduction_percent'] != 0){
                        $real_percent = $restrictions[0]['reduction_percent'] / 100;
                        $discount_amount = $discount_amount + round($total_price_tax_excl * $real_percent);
                    }
                }
            }

#           ╔════════════════════╗
#           ║ Make the discounts ║
#           ╚════════════════════╝
            if($tax_rate == 0){
                $only_exe = true;
                $amount_exempt_products = $amount_exempt_products - $discount_amount;
            }
            else{
                $only_afe = true;
                $amount_products_exclude_taxes = $amount_products_exclude_taxes - $discount_amount;
            }
            
#           ╔═════════════════════════════════════╗
#           ║   Verify if the product has taxes   ║
#           ╠═════════════════════════════════════╣
#           ║     IndExe = 1 is exempt of IVA     ║
#           ║      If has IVA dont set IndExe     ║
#           ╚═════════════════════════════════════╝
            if ($tax_rate == 0) {
                $items['IndExe'] = 1;
                $amount_exempt_products = $amount_exempt_products + $total_price_tax_excl;
            }
            else{
                $bill_is_afe = true;
                $amount_products_exclude_taxes = $amount_products_exclude_taxes + $total_price_tax_excl;
            }
            
#           ╔════════════════════════════════════╗
#           ║ Add the discount to the bill if it ║
#           ║         is different of 0          ║
#           ╚════════════════════════════════════╝
            if($discount_amount != 0){
                $items['DescuentoMonto'] = $discount_amount;
            }

#           ╔════════════════════════════════════╗
#           ║  Add the final amount of the item  ║
#           ╚════════════════════════════════════╝
            $items['MontoItem'] = round($total_price_tax_excl - $discount_amount);
            $line++;
            if($items['MontoItem'] < 0){
                PrestaShoplogger::addLog("Error en el monto del item ". $items['NmbItem'] . ": $" . $items['MontoItem'] . ". Posible causa: Demasiados descuentos aplicados a un producto.",4);
            }
            array_push($detail, $items);

            
        };

        #       ╔════════════════════════════════════╗ ┌────────────────────────────────────────────────────┐
        #       ║ Manage the shipping and their cost ║ │                      IMPORTANT                     │
        #       ╠════════════════════════════════════╣ │      When the order has a general reduction        │
        #       ║ Shipping is other item in the bill ║ │ Prestashop exclude the shipping in the computation │
        #       ║  that's why include in the detail  ║ │   of the final amount, thats the reason why the    │
        #       ║  and check if it has taxes or not  ║ │  shipping is managed in this section of the code   │
        #       ╚════════════════════════════════════╝ └────────────────────────────────────────────────────┘
        $total_shipping = intval(round($order->total_shipping));
        $total_shipping_tax_incl = intval(round($order->total_shipping_tax_incl));
        $total_shipping_tax_excl = intval(round($order->total_shipping_tax_excl));
        $carrier_tax_rate = floatval(round($order->carrier_tax_rate));
        $items = array();
        if($total_shipping != 0){
            $items = [
                'NroLinDet'     => $line,
                'NmbItem'       => "Envío",
                'QtyItem'       => 1,
                'PrcItem'       => $total_shipping_tax_excl,
                'MontoItem'     => $total_shipping_tax_excl
            ];

            if ($carrier_tax_rate == 0) {
                $items['IndExe'] = 1;
                $amount_exempt_products = $amount_exempt_products + $total_shipping_tax_excl;
            }
            else{
                $bill_is_afe = true;
                $amount_products_exclude_taxes = $amount_products_exclude_taxes + $total_shipping_tax_excl;
            }
            $line++;
            array_push($detail, $items);
        }

#       ╔══════════════════════════════════════════╗
#       ║    Iterate through the cart rules and    ║
#       ║         check if exist discounts         ║
#       ╠══════════════════════════════════════════╣
#       ║    the prop 'reduction_product' define   ║
#       ║  the type of discount, if the value is:  ║
#       ║ (-1) -> the discount is on the cheaper   ║
#       ║  (0) -> the discount is on general cart  ║
#       ║  (X) -> the discount is in the prod id X ║
#       ╚══════════════════════════════════════════╝
        $DscRcgGlobal = array();
        $free_shipping = false;
        $deltaReal = 0;
        $line2 = 1;
        foreach ($cartRules as $rule) {

#           ╔══════════════╗
#           ║ Free Product ║
#           ╚══════════════╝
            if($rule['gift_product'] > 0){
                $product = $this->getProductByID($products, $rule['gift_product']);
                $discount_item = array(
                    'NroLinDR' => $line2,
                    'TpoMov' => 'D',
                    'GlosaDR' => 'Producto de Regalo - ' . $rule['code'],
                    'TpoValor' => '$',
                    'ValorDR' => round($product['unit_price_tax_excl'],2),
                );

                if($product['tax_rate'] == 0){
                    $discount_item['IndExeDR'] = 1;
                    $amount_exempt_products = $amount_exempt_products - $product['unit_price_tax_excl'];
                }
                else{
                    $amount_products_exclude_taxes  = $amount_products_exclude_taxes - $product['unit_price_tax_excl'];
                }
                array_push($DscRcgGlobal, $discount_item);
                $line2++;
            }

#           ╔═══════════════╗
#           ║ Free Shipping ║
#           ╚═══════════════╝
            if($rule['free_shipping'] == "1" && !$free_shipping){
                $discount_item = array(
                    'NroLinDR' => $line2,
                    'TpoMov' => 'D',
                    'GlosaDR' => 'Envío Gratis - ' . $rule['code'],
                    'TpoValor' => '$',
                    'ValorDR' => $order->total_shipping_tax_excl,
                );
                if($carrier_tax_rate == 0){
                    $discount_item['IndExeDR'] = 1 ;
                    $amount_exempt_products = $amount_exempt_products - $total_shipping_tax_excl;
                }
                else{
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - $total_shipping_tax_excl;
                }
                $line2++;
                array_push($DscRcgGlobal, $discount_item);
                $free_shipping = true;
            }

#           ╔══════════════════╗
#           ║ General Discount ║
#           ║    Percentage    ║
#           ╚══════════════════╝
            if($rule['reduction_product'] == "0" && $rule['reduction_percent'] != 0){
                if ($amount_products_exclude_taxes != 0) {
                    $real_percentage = round($rule['reduction_percent'] / 100,2);
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => 'Descuento General Afectos - ' . $rule['code'],
                        'TpoValor'  => '%',
                        'ValorDR'   => $rule['reduction_percent'],
                    );
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes * (1 - $real_percentage);
                    $amount_tax_accumulated = $amount_tax_accumulated * (1 - $real_percentage);
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }
                if($amount_exempt_products != 0){
                    $real_percentage = round($rule['reduction_percent'] / 100,2);
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => 'Descuento General Exentos - ' . $rule['code'],
                        'TpoValor'  => '%',
                        'ValorDR'   => $rule['reduction_percent'],
                        'IndExeDR'  => 1,
                    );
                    $amount_exempt_products = $amount_exempt_products * (1 - $real_percentage);
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }
                //agregar la diferencia en el global
                if($total_shipping_tax_excl != 0 && !$free_shipping){
                    $delta = $total_shipping_tax_excl * ($rule['reduction_percent']/100);
                    $rechargeShippDelta = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'R',
                        'GlosaDR'   => 'Diferencia Envío-Descuento',
                        'TpoValor'  => '$',
                        'ValorDR'   => round($delta,2),
                    );
                    if($carrier_tax_rate == 0){
                        $rechargeShippDelta['IndExeDR'] = 1;
                        $amount_exempt_products = $amount_exempt_products + $delta;
                    }
                    else{
                        $amount_products_exclude_taxes = $amount_products_exclude_taxes + $delta;
                    }
                    $line2++;
                    array_push($DscRcgGlobal, $rechargeShippDelta);
                }
            }

#           ╔══════════════════╗ 
#           ║ General Discount ║ 
#           ║      Amount      ║ 
#           ╚══════════════════╝                     
            if($rule['reduction_product'] == "0" &&  $rule['reduction_amount'] != 0){

#               ╔═══════════════════════╗ ┌─────────────────┐
#               ║ Only Afected Products ║ │ This case works │
#               ╚═══════════════════════╝ └─────────────────┘
                if($only_afe && !$only_exe){
                    $tax_discount = $tax_discount + ($rule['value_real'] - $rule['value_tax_exc']);
                    $amount_products_exclude_taxes = $amount_products_exclude_taxes - $rule['value_tax_exc'];
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => 'Descuento General - ' . $rule['code'],
                        'TpoValor'  => '$',
                        'ValorDR'   => round($rule['value_tax_exc'],2),
                    );
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }

#               ╔═══════════════════════╗ ┌─────────────────┐
#               ║ Only Exempts Products ║ │ This case works │
#               ╚═══════════════════════╝ └─────────────────┘
                if(!$only_afe && $only_exe){
                    $amount_exempt_products = $amount_exempt_products - $rule['value_tax_exc'];
                    $discount_item = array(
                        'NroLinDR'  => $line2,
                        'TpoMov'    => 'D',
                        'GlosaDR'   => 'Descuento General - ' . $rule['code'],
                        'TpoValor'  => '$',
                        'ValorDR'   => round($rule['reduction_amount'],2),
                        'IndExeDR'  => 1,
                    );
                    $line2++;
                    array_push($DscRcgGlobal, $discount_item);
                }

#               ╔══════════════════════════════╗
#               ║ Afected and exempts products ║
#               ╚══════════════════════════════╝
                if($only_afe && $only_exe){
                    if($rule['reduction_tax'] == 1){
                        if($carrier_tax_rate == 0){
                            $amount_exempt_products = $amount_exempt_products - $total_shipping_tax_excl;
                        }
                        else{
                            $amount_products_exclude_taxes = $amount_products_exclude_taxes - $total_shipping_tax_excl;
                        }
    
                        $subtotal = $amount_products_exclude_taxes + $amount_exempt_products;

                        $dscto_amount_exempt = round(($amount_exempt_products/$subtotal)*$rule['value_tax_exc'],2);
                        $dscto_amount_neto = round(($amount_products_exclude_taxes/$subtotal)*$rule['value_tax_exc'],2);

                        $amount_exempt_products = $amount_exempt_products - $dscto_amount_exempt;
                        $amount_products_exclude_taxes = $amount_products_exclude_taxes - $dscto_amount_neto;

                        $discount_item = array(
                            'NroLinDR'  => $line2,
                            'TpoMov'    => 'D',
                            'GlosaDR'   => 'Descuento General - ' . $rule['code'],
                            'TpoValor'  => '$',
                            'ValorDR'   => round($dscto_amount_neto,2),
                        );
                        $line2++;
                        array_push($DscRcgGlobal, $discount_item);
    
                        $discount_item = array(
                            'NroLinDR'  => $line2,
                            'TpoMov'    => 'D',
                            'GlosaDR'   => 'Descuento General - ' . $rule['code'],
                            'TpoValor'  => '$',
                            'ValorDR'   => round($dscto_amount_exempt,2),
                            'IndExeDR'  => 1,
                        );
                        $line2++;
                        array_push($DscRcgGlobal, $discount_item);

                        if($carrier_tax_rate == 0){
                            $amount_exempt_products = $amount_exempt_products + $total_shipping_tax_excl;
                        }
                        else{
                            $amount_products_exclude_taxes = $amount_products_exclude_taxes + $total_shipping_tax_excl;
                        }

                    }
                    else{
                        if($carrier_tax_rate == 0){
                            $amount_exempt_products = $amount_exempt_products - $total_shipping_tax_excl;
                        }
                        else{
                            $amount_products_exclude_taxes = $amount_products_exclude_taxes - $total_shipping_tax_excl;
                        }
    
                        $subtotal = $amount_products_exclude_taxes + $amount_exempt_products;
                        $dscto_amount_exempt = round(($amount_exempt_products/$subtotal)*round($rule['value_tax_exc'],2),2);
                        $dscto_amount_neto = round(($amount_products_exclude_taxes/$subtotal)*round($rule['value_tax_exc'],2),2);
    
                        $amount_exempt_products = $amount_exempt_products - $dscto_amount_exempt;
                        $amount_products_exclude_taxes = $amount_products_exclude_taxes - $dscto_amount_neto;
    
                        $discount_item = array(
                            'NroLinDR'  => $line2,
                            'TpoMov'    => 'D',
                            'GlosaDR'   => 'Descuento General - ' . $rule['code'],
                            'TpoValor'  => '$',
                            'ValorDR'   => round($dscto_amount_neto,2),
                        );
                        $line2++;
                        array_push($DscRcgGlobal, $discount_item);
    
                        $discount_item = array(
                            'NroLinDR'  => $line2,
                            'TpoMov'    => 'D',
                            'GlosaDR'   => 'Descuento General - ' . $rule['code'],
                            'TpoValor'  => '$',
                            'ValorDR'   => round($dscto_amount_exempt,2),
                            'IndExeDR'  => 1,
                        );
                        $line2++;
                        array_push($DscRcgGlobal, $discount_item);
                        if($carrier_tax_rate == 0){
                            $amount_exempt_products = $amount_exempt_products + $total_shipping_tax_excl;
                        }
                        else{
                            $amount_products_exclude_taxes = $amount_products_exclude_taxes + $total_shipping_tax_excl;
                        }
                    }
                }
            }
        }

#       ╔═══════════════════════════════════════╗
#       ║ End the config with the total amounts ║
#       ╚═══════════════════════════════════════╝
        if($bill_is_afe){
            $id_doc = array(
                "FchEmis" => $date,
                "IndMntNeto" => 2
            );
            $totales = array(
                "MntNeto" => round($amount_products_exclude_taxes),
                'MntExe' => round($amount_exempt_products),
                "TasaIVA" => "19.000",
                "IVA" => round($amount_products_exclude_taxes * 0.19),
                "MntTotal" => round($order->total_paid_tax_incl),
            );
            
        }
        else{
                $id_doc = array(
                    "FchEmis" => $date,
                );
                $totales = array(
                    'MntExe' => round($amount_exempt_products),
                    "MntTotal" => round($order->total_paid_tax_incl),
                );
        }

#       ╔═════════════════════════════════════════╗
#       ║ Final config and prepare the final json ║
#       ║ to send to the API to register the bill ║
#       ╚═════════════════════════════════════════╝        
        /**
         * Changes: Nothing... this block works good
         */
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

        
        PrestaShopLogger::addLog("Order: " . json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
        PrestaShopLogger::addLog("Products: " . json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
        PrestaShopLogger::addLog("Cart Rules: " . json_encode($cartRules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1);
        

#       ╔═════════════════════════════════════════╗
#       ║ Assemble the final object with the info ║
#       ║    of the differents items in the DTE   ║
#       ╚═════════════════════════════════════════╝        
        /**
         * Changes: Idempotency-key changed.
         * Reason:  The last config only use the order's id
         *          and caused problems when the store was restarted.
         */
        $document_send = array();
        $document_send = array_merge($document_send, $response);
        $document_send = array_merge($document_send, $customer);
        $document_send = array_merge($document_send, $customize_page);
        $document_send = array_merge($document_send, $self_service);
        $document_send = array_merge($document_send, $dte);
        $document_send = array_merge($document_send, $custom);
        $document_send = json_encode($document_send, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $url_generate = '';
        if ($openfacturaRegistryActive['is_demo'] == "1") {
            $url_generate = 'https://dev-api.haulmer.com/v2/dte/document';
        } else {            
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
                "Idempotency-Key:" . "PRESTASHOP". "_" . $openfacturaRegistryActive['rut'] . "_" . date("Y/m/d_H:i:s") . "_" . $order->id,
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, true);
        $id_order = $orderDetail[0]['id_order'];
        $id_order_invoice = $orderDetail[0]['id_order_invoice'];
        $urlSS = isset($response["SELF_SERVICE"]) ? $response["SELF_SERVICE"]["url"] : null;
        $urlSS = str_replace("https://","\n",$urlSS);
        try {
            if (isset($urlSS)) {
                $note = $urlSS;
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET 
                note="Obten tu documento tributario en: ' . pSQL($note) . '"
                WHERE id_order_invoice="' . (int)$id_order_invoice . '" and id_order="' . (int)$id_order . '"';
                Db::getInstance()->Execute($sql);
                PrestaShopLogger::addLog("Su documento tributario se generó correctamente, puede encontrar el link para acceder en la factura generada por PrestaShop y en el Log superior", 1);
                PrestaShopLogger::addLog("Response: " . json_encode($response, JSON_PRETTY_PRINT), 1);
            } else {
                $sql = 'UPDATE ' . _DB_PREFIX_ . 'order_invoice SET 
                note="Su documento tributario no se pudo generar."
                WHERE id_order_invoice="' . (int)$id_order_invoice . '" and id_order="' . (int)$id_order . '"';
                Db::getInstance()->Execute($sql);
                PrestaShopLogger::addLog("Su documento tributario no se pudo generar, favor revisar los logs superiores y contactarse con soporte", 3);
                PrestaShopLogger::addLog("Response: " . json_encode($response, JSON_PRETTY_PRINT), 1);
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage(), 3);
        }

        return true;
    }
}