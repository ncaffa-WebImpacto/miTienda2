<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class InfoPedido extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'infoPedido';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Nicolás Caffa Carreras';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('infoPedido');
        $this->description = $this->l('Genere y guarde un archivo XML ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('INFOPEDIDO_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader')&&
            $this->registerHook('actionObjectOrderReturnAddAfter')&&
            $this->registerHook('displayOrderConfirmation')&&
            $this->registerHook('actionOrderReturn');
    }

    public function uninstall()
    {
        Configuration::deleteByName('INFOPEDIDO_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitInfoPedidoModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitInfoPedidoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'INFOPEDIDO_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'INFOPEDIDO_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'INFOPEDIDO_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'INFOPEDIDO_LIVE_MODE' => Configuration::get('INFOPEDIDO_LIVE_MODE', true),
            'INFOPEDIDO_ACCOUNT_EMAIL' => Configuration::get('INFOPEDIDO_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'INFOPEDIDO_ACCOUNT_PASSWORD' => Configuration::get('INFOPEDIDO_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */

    public function hookactionOrderReturn(array $params){

        $ultpedido = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
        SELECT MAX(`id_order`)
        FROM `' . _DB_PREFIX_ . 'orders`');

        $intPedido= (int) $ultpedido["MAX(`id_order`)"];
        dump($intPedido);

        //  if (Tools::isSubmit('id_order')) {
             // Save context (in order to apply cart rule)
             $order = new Order($intPedido);
            // $order = new Order(7);
            $carro = $this->context->cart = new Cart($order->id_cart);
            $customer= $this->context->customer = new Customer($order->id_customer);
        //  }
        //  $order = new Order(7);
         dump($order);
         $pago= $order->payment;
         dump($pago);
         $totalPedido = $order->total_paid;
         dump($totalPedido);
         $productos=$order->getCartProducts();
          dump($productos);
          $cantidadProductos = count($productos);
            
          $Pedidoxml= array(
            'idPedido' => $intPedido,
            'totalPedido'=>$totalPedido,
            'pago'=> $pago,
            'productos' => array(

            ),
            'cantidadProductos'=>$cantidadProductos
         ); 


         for ($i=0; $i < count($productos) ; $i++) { 
            $nombreProducto=$productos[$i]["product_name"];
            $precioProducto=$productos[$i]["product_price"];

            array_push($Pedidoxml['productos'],$nombreProducto,$precioProducto);
         }


         $xml_user_info = new SimpleXMLElement('<?xml version="1.0"?><user_info></user_info>');

            //function call to convert array to xml
            $xml= $this->array_to_xml($Pedidoxml,$xml_user_info);

            //saving generated xml file
            $xml_file = $xml_user_info->asXML($intPedido.'.xml');

            //success and error message based on xml creation
             if($xml_file){
             echo 'XML file have been generated successfully.';
            }else{
              echo 'XML file generation error.';
             }   

            // $xml= $this->array_to_xml($Pedidoxml,$xml_user_info);
            dump($xml_user_info);

         exit("hola");

        //  dump($Pedidoxml);
        // //  $nombreProducto=$productos[0]["product_name"];
        // //  dump($nombreProducto);
        // //  dump($precioProducto);
        // //  dump($carro);
        // //  dump($customer);
        

    }

    public function hookBackOfficeHeader(array $params)
    {
        // $this->hookactionOrderReturn($params);
        //  exit("hola");
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {

        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }


    function array_to_xml($array, &$xml_user_info) {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    $subnode = $xml_user_info->addChild("$key");
                    $this->array_to_xml($value, $subnode);
                }else{
                    $subnode = $xml_user_info->addChild("item$key");
                    $this->array_to_xml($value, $subnode);
                }
            }else {
                $xml_user_info->addChild("$key",htmlspecialchars("$value"));
            }
        }
    
    }

    
   


  



 
}
