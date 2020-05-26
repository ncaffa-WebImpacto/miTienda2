<?php





use PrestaShop\PrestaShop\Core\Grid\Definition\EmployeeGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Definition\Factory\AbstractGridDefinitionFactory;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Column\ColumnCollection;
use Symfony\Component\Form\FormBuilderInterface;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinition;
use PrestaShop\PrestaShop\Core\Grid\Filter\Filter;
use PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface;


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

class TablaEmpleados extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tablaEmpleados';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Nicolás Caffa Carreras';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('tablaEmpleados');
        $this->description = $this->l('Crea un nuevo dato en el formulario equipo/empleado');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('TABLAEMPLEADOS_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader')&&
            $this->registerHook('actionEmployeeGridDefinitionModifier') &&
            $this->registerHook('actionEmployeeGridQueryBuilderModifier') &&
            $this->registerHook('actionEmployeeFormBuilderModifier') &&
            $this->registerHook('actionAfterCreateEmployeeFormHandler') &&
            $this->registerHook('actionAfterUpdateEmployeeFormHandler');
    }

    public function uninstall()
    {
        Configuration::deleteByName('TABLAEMPLEADOS_LIVE_MODE');
        include(dirname(__FILE__).'/sql/uninstall.php');

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
        if (((bool)Tools::isSubmit('submitTablaEmpleadosModule')) == true) {
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
        $helper->submit_action = 'submitTablaEmpleadosModule';
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
                        'name' => 'TABLAEMPLEADOS_LIVE_MODE',
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
                        'name' => 'TABLAEMPLEADOS_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'TABLAEMPLEADOS_ACCOUNT_PASSWORD',
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
            'TABLAEMPLEADOS_LIVE_MODE' => Configuration::get('TABLAEMPLEADOS_LIVE_MODE', true),
            'TABLAEMPLEADOS_ACCOUNT_EMAIL' => Configuration::get('TABLAEMPLEADOS_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'TABLAEMPLEADOS_ACCOUNT_PASSWORD' => Configuration::get('TABLAEMPLEADOS_ACCOUNT_PASSWORD', null),
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
    public function hookBackOfficeHeader()
    {
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

    public function hookActionEmployeeFormBuilderModifier(array $params)
    {
        /** @var FormBuilderInterface $formBuilder */
    
         $employeeId = $params['id'];
         $formBuilder = $params['form_builder'];    
        
        $formBuilder->add('prueba2', FormattedTextareaType::class, [
            'label' => $this->getTranslator()->trans('prueba2', [], 'Modules.Ps_tablaEmpleados'),
            'required' => false,
        ]);
        
        $formBuilder->setData($params['data']);
     }

public function hookActionAfterUpdateEmployeeFormHandler(array $params)
{
    $this->updateEmployeeReviewStatus($params);
}
    


public function hookActionAfterCreateEmployeeFormHandler(array $params)
{
    


    $this->crearEmployeeReviewStatus($params);
    
   
}

private function crearEmployeeReviewStatus(array $params)
{

    $employeeId = $params['id'];
    /** @var array $employeeFormData */
    $employeeFormData = $params['form_data'];

    // dump($employeeId);
        // exit("eo");

    $prueba2 = $employeeFormData['prueba2'];

   
    $newEmployee = new Employee($employeeId);
    $newEmployee->prueba2= $prueba2;
    $newEmployee->add();
    $newEmployee->save();

}


private function updateEmployeeReviewStatus(array $params)
{
    $employeeId= $params['id'];
    /** @var array $employeeFormData */
    $employeeFormData = $params['form_data'];

    
    //   dump($employeeFormData["shop_association"][0]);
    //    exit("eo");
    $prueba2 = $employeeFormData['prueba2'];


    $newEmployee = new Employee($employeeId);
    $newEmployee->prueba2= $prueba2;
    $newEmployee->update();
    $newEmployee->save();

}


public function hookActionEmployeeGridDefinitionModifier(array $params)
    {
        /** @var GridDefinitionInterface $definition */
          $definition = $params['definition'];

            //   dump($definition->getColumns());
            //   exit("ss");
        
         $translator = $this->getTranslator();
         $definition
        ->getColumns()
        ->addAfter(
            'email',
            ($dataColumn =new DataColumn('prueba2'))
                ->setName($translator->trans('prueba2',[], 'Modules.Ps_tablaEmpleados'))
                ->setOptions([
                    'field' => 'prueba2',
                ])
        )
    ;

    }



    public function hookActionEmployeeGridQueryBuilderModifier(array $params)
    {
           
        /** @var QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        // dump($searchQueryBuilder);
        // exit("ss");
   
        // /** @var CustomerFilters $searchCriteria */
        // $searchCriteria = $params['search_criteria'];

        $searchQueryBuilder->AddSelect('e.prueba2');
    
     
    }


}
