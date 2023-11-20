<?php
/**
 * 2007-2019 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class HookLogger extends Module
{
    const KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS = 'HOOKLOGGER_IS_ACTIVE_MARK_DISPLAY_HOOKS';
    const KEY_IS_ACTIVE_PRESTASHOPLOGGER = 'HOOKLOGGER_IS_ACTIVE_PRESTASHOPLOGGER';
    const KEY_IS_ACTIVE_LOG_IN_A_FILE = 'HOOKLOGGER_IS_ACTIVE_LOG_IN_A_FILE';

    const LOG_FILE =
        __DIR__ .
        DIRECTORY_SEPARATOR . 'var' .
        DIRECTORY_SEPARATOR . 'hooks_log.txt';
    public static $hook_execution_counter = 0;
    /**
     * @var array the full list of registered hooks.
     */
    private $hooks;

    /**
     * HookLogger constructor.
     */
    public function __construct()
    {
        $this->name = 'hooklogger';
        $this->tab = 'others';
        $this->version = '2.0.0';
        $this->author = 'Jérémie Pasquis';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Hook Logger');

        $this->description = $this->l(
            'Development tool for PrestaShop that mark all display hooks and log all hooks activation. Please delete in production.'
        );

        $this->ps_versions_compliancy = array(
            'min' => '1.6',
            'max' => _PS_VERSION_,
        );

        $sql = new DbQuery();

        $sql->select('name')->from('hook', 'h');

        $this->hooks = Db::getInstance()->executeS($sql);
    }

    /**
     * @return bool
     */
    public function install(): bool
    {
        Configuration::updateValue(self::KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS, true);

        return
            parent::install()
            && $this->registerHooks($this->hooks);
    }

    /**
     * Helper function to register a list of hooks.
     * @param array $hooks
     * @return bool
     */
    private function registerHooks($hooks)
    {
        if (!is_array($hooks)) {
            return false;
        }

        $result = true;

        foreach ($hooks as $hook) {
            if (!$this->registerHook($hook)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /*
         * If values have been submitted in the form, process.
         */
        if ((Tools::isSubmit('submit_' . $this->name))) {
            $this->postProcess();
        }

        $log_file_url = file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'hooks_log.txt') ? _PS_BASE_URL_ . $this->getPathUri() . 'var/hooks_log.txt' : false;

        $this->context->smarty->assign([
            'log_file_url' => $log_file_url,
        ]);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $this->renderForm() . $output;
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
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        $inputs = $this->getConfigForm()['form']['input'];
        $output = [];

        foreach ($inputs as $input) {
            if ($input['type'] === 'select' && $input['multiple']) {
                $output[$input['name'] . '[]'] = explode(',', Configuration::get($input['name']));
                continue;
            }

            $output[$input['name']] = Configuration::get($input['name']);
        }

        return $output;
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Mark display hooks'),
                        'name' => self::KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS,
                        'values' => [
                            [
                                'id' => self::KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS . '_on',
                                'value' => 1,
                            ],
                            [
                                'id' => self::KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS . '_off',
                                'value' => 0,
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Log with PrestaShopLogger'),
                        'name' => self::KEY_IS_ACTIVE_PRESTASHOPLOGGER,
                        'values' => [
                            [
                                'id' => self::KEY_IS_ACTIVE_PRESTASHOPLOGGER . '_on',
                                'value' => 1,
                            ],
                            [
                                'id' => self::KEY_IS_ACTIVE_PRESTASHOPLOGGER . '_off',
                                'value' => 0,
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Log in a file'),
                        'name' => self::KEY_IS_ACTIVE_LOG_IN_A_FILE,
                        'values' => [
                            [
                                'id' => self::KEY_IS_ACTIVE_LOG_IN_A_FILE . '_on',
                                'value' => 1,
                            ],
                            [
                                'id' => self::KEY_IS_ACTIVE_LOG_IN_A_FILE . '_off',
                                'value' => 0,
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ]
        ];
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
        $helper->submit_action = 'submit_' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    /**
     * @param $hookName
     * @param $hookArguments
     * @return string
     */
    public function __call($hookName, $hookArguments)
    {
        self::$hook_execution_counter++;

        if (Configuration::get(self::KEY_IS_ACTIVE_LOG_IN_A_FILE)) {
            $this->log($hookName);
        }

        if (Configuration::get(self::KEY_IS_ACTIVE_PRESTASHOPLOGGER)) {
            PrestaShopLogger::addLog($hookName, 1, self::$hook_execution_counter, self::class, $this->id, true);
        }

        if (
            (stripos($hookName, 'hookdisplay') === 0)
            && ($hookName !== 'hookDisplayOverrideTemplate')
            && Configuration::get(self::KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS)
        ) {
            return '<span style="background-color: red;color: white;padding: 0 .5rem;border-radius: 3px;">' . $hookName . '</span>';
        }

        return '';
    }

    /**
     * @param $message
     */
    private function log($message)
    {
        file_put_contents(
            self::LOG_FILE,
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND
        );
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName(self::KEY_IS_ACTIVE_MARK_DISPLAY_HOOKS);
        Configuration::deleteByName(self::KEY_IS_ACTIVE_PRESTASHOPLOGGER);
        Configuration::deleteByName(self::KEY_IS_ACTIVE_LOG_IN_A_FILE);
        return parent::uninstall();
    }
}
