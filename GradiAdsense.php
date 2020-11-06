<?php
/*
* 2007-2015 PrestaShop
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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class GradiAdsense extends Module implements WidgetInterface
{
    private $templateFile;

	public function __construct()
	{
		$this->name = 'GradiAdsense';
		$this->version = '2.1.0';
		$this->author = 'PrestaShop';
		$this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('GradiAdsense', array(), 'Modules.GradiAdsense.Admin');
        $this->description = $this->trans('Displays publicidad GradiAdsense.', array(), 'Modules.GradiAdsense.Admin');

        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

        $this->templateFile = 'module:GradiAdsense/GradiAdsense.tpl';
    }

    public function install()
    {
        return (parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('actionObjectLanguageAddAfter') &&
            $this->installFixtures() &&
            $this->disableDevice(Context::DEVICE_MOBILE));
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        return $this->installFixture((int)$params['object']->id, Configuration::get('GADSENSE_IMG', (int)Configuration::get('PS_LANG_DEFAULT')));
    }

    protected function installFixtures()
    {
        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
		  
            $this->installFixture((int)$lang['id_lang'],'sale70.png');
        }

        return true;
    }

    protected function installFixture($id_lang, $image = null)
    {
        $values['GADSENSE_IMG'][(int)$id_lang] = empty($image) ? 'sale70.png' : $image;
        $values['GADSENSE_TITLE'][(int)$id_lang] = '';
        $values['GADSENSE_CTA'][(int)$id_lang] = '';
        $values['GADSENSE_LINK_CTA'][(int)$id_lang] = '';
        $values['GADSENSE_DESC'][(int)$id_lang] = '';
        $values['GADSENSE_ESTATE'][(int)$id_lang] = '';

        Configuration::updateValue('GADSENSE_IMG', $values['GADSENSE_IMG']);
        Configuration::updateValue('GADSENSE_TITLE', $values['GADSENSE_TITLE']);
        Configuration::updateValue('GADSENSE_CTA', $values['GADSENSE_CTA']);
        Configuration::updateValue('GADSENSE_LINK_CTA', $values['GADSENSE_LINK_CTA']);
        Configuration::updateValue('GADSENSE_DESC', $values['GADSENSE_DESC']);
        Configuration::updateValue('GADSENSE_ESTATE', $values['GADSENSE_ESTATE']);
    }

    public function uninstall()
    {
        Configuration::deleteByName('GADSENSE_IMG');
        Configuration::deleteByName('GADSENSE_TITLE');
        Configuration::deleteByName('GADSENSE_CTA');
        Configuration::deleteByName('GADSENSE_LINK_CTA');
        Configuration::deleteByName('GADSENSE_DESC');
        Configuration::deleteByName('GADSENSE_ESTATE');

        return parent::uninstall();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitStoreConf')) {
            $languages = Language::getLanguages(false);
            $values = array();
            $update_images_values = false;

            foreach ($languages as $lang) {
                if (isset($_FILES['GADSENSE_IMG_'.$lang['id_lang']])
                    && isset($_FILES['GADSENSE_IMG_'.$lang['id_lang']]['tmp_name'])
                    && !empty($_FILES['GADSENSE_IMG_'.$lang['id_lang']]['tmp_name'])) {
                    if ($error = ImageManager::validateUpload($_FILES['GADSENSE_IMG_'.$lang['id_lang']], 4000000)) {
                        return $error;
                    } else {
                        $ext = substr($_FILES['GADSENSE_IMG_'.$lang['id_lang']]['name'], strrpos($_FILES['GADSENSE_IMG_'.$lang['id_lang']]['name'], '.') + 1);
                        $file_name = md5($_FILES['GADSENSE_IMG_'.$lang['id_lang']]['name']).'.'.$ext;

                        if (!move_uploaded_file($_FILES['GADSENSE_IMG_'.$lang['id_lang']]['tmp_name'], dirname(__FILE__).DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$file_name)) {
                            return $this->displayError($this->trans('An error occurred while attempting to upload the file.', array(), 'Admin.Notifications.Error'));
                        } else {
                            if (Configuration::hasContext('GADSENSE_IMG', $lang['id_lang'], Shop::getContext())
                                && Configuration::get('GADSENSE_IMG', $lang['id_lang']) != $file_name) {
                                @unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR . Configuration::get('GADSENSE_IMG', $lang['id_lang']));
                            }

                            $values['GADSENSE_IMG'][$lang['id_lang']] = $file_name;
                        }
                    }

                    $update_images_values = true;
                }

                $values['GADSENSE_TITLE'][$lang['id_lang']] = Tools::getValue('GADSENSE_TITLE_'.$lang['id_lang']);
                $values['GADSENSE_CTA'][$lang['id_lang']] = Tools::getValue('GADSENSE_CTA_'.$lang['id_lang']);
                $values['GADSENSE_LINK_CTA'][$lang['id_lang']] = Tools::getValue('GADSENSE_LINK_CTA_'.$lang['id_lang']);
                $values['GADSENSE_DESC'][$lang['id_lang']] = Tools::getValue('GADSENSE_DESC_'.$lang['id_lang']);
                $values['GADSENSE_ESTATE'][$lang['id_lang']] = Tools::getValue('GADSENSE_ESTATE');
            }
             
            if ($update_images_values) {
                Configuration::updateValue('GADSENSE_IMG', $values['GADSENSE_IMG']);
            }

            Configuration::updateValue('GADSENSE_TITLE', $values['GADSENSE_TITLE']);
            Configuration::updateValue('GADSENSE_CTA', $values['GADSENSE_CTA']);
            Configuration::updateValue('GADSENSE_LINK_CTA', $values['GADSENSE_LINK_CTA']);
            Configuration::updateValue('GADSENSE_DESC', $values['GADSENSE_DESC']);
            Configuration::updateValue('GADSENSE_ESTATE', $values['GADSENSE_ESTATE']);

            $this->_clearCache($this->templateFile);

            return $this->displayConfirmation($this->trans('The settings have been updated.', array(), 'Admin.Notifications.Success'));
        }

        return '';
    }

    public function getContent()
    {
        return $this->postProcess().$this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->trans('Settings', array(), 'Admin.Global'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'file_lang',
                        'label' => $this->trans('Gadsense Imagen', array(), 'Modules.GradiAdsense.Admin'),
                        'name' => 'GADSENSE_IMG',
                        'desc' => $this->trans('Actualizar imagen. Las dimenciones recomendadas son 1110 x 214px si tu estas usando la imagen por defecto.', array(), 'Modules.GradiAdsense.Admin'),
                        'lang' => true,
                    ),
                    array(
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Gadsense Título', array(), 'Modules.GradiAdsense.Admin'),
                        'name' => 'GADSENSE_TITLE',
                        'desc' => $this->trans('Por favor introduce un titulo.', array(), 'Modules.GradiAdsense.Admin')
                    ),
                    array(
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Gadsense descripción', array(), 'Modules.GradiAdsense.Admin'),
                        'name' => 'GADSENSE_DESC',
                        'desc' => $this->trans('Porfavor introduce una pequeña descripción.', array(), 'Modules.GradiAdsense.Admin')
                    ),
                    array(
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Gadsense CTA', array(), 'Modules.GradiAdsense.Admin'),
                        'name' => 'GADSENSE_CTA',
                        'desc' => $this->trans('Porfavor el nombre del CTA.', array(), 'Modules.GradiAdsense.Admin')
                    ),
					 array(
                        'type' => 'text',
                        'label' => $this->trans('Gadsense Cta', array(), 'Modules.GradiAdsense.Admin'),
                        'name' => 'GADSENSE_LINK_CTA',
                        'desc' => $this->trans('Porfavor ingrese el enlace al cta.', array(), 'Modules.GradiAdsense.Admin'),
                        'lang' => true,
                    ),
					array(
                        'type' => 'switch',
                        'lang' => true,
                        'label' => $this->trans('Gadsense estado', array(), 'Modules.GradiAdsense.Admin'),
                        'name' => 'GADSENSE_ESTATE',
						'is_bool' => true,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Yes')
							),
							array(
								'id' => 'active_off',
								'value' => 0,
								'label' => $this->l('No')
							)
						),
                        'desc' => $this->trans('Porfavor selecciona una opción.', array(), 'Modules.GradiAdsense.Admin')
                    )
                ),
                'submit' => array(
                    'title' => $this->trans('Save', array(), 'Admin.Actions')
                )
            ),
        );

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->module = $this;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStoreConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'uri' => $this->getPathUri(),
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues()
    {
        $languages = Language::getLanguages(false);
        $fields = array();

        foreach ($languages as $lang) {
            $fields['GADSENSE_IMG'][$lang['id_lang']] = Tools::getValue('GADSENSE_IMG_'.$lang['id_lang'], Configuration::get('GADSENSE_IMG', $lang['id_lang']));
            $fields['GADSENSE_TITLE'][$lang['id_lang']] = Tools::getValue('GADSENSE_TITLE_'.$lang['id_lang'], Configuration::get('GADSENSE_TITLE', $lang['id_lang']));
            $fields['GADSENSE_CTA'][$lang['id_lang']] = Tools::getValue('GADSENSE_CTA_'.$lang['id_lang'], Configuration::get('GADSENSE_CTA', $lang['id_lang']));
            $fields['GADSENSE_LINK_CTA'][$lang['id_lang']] = Tools::getValue('GADSENSE_LINK_CTA_'.$lang['id_lang'], Configuration::get('GADSENSE_LINK_CTA', $lang['id_lang']));
            $fields['GADSENSE_DESC'][$lang['id_lang']] = Tools::getValue('GADSENSE_DESC_'.$lang['id_lang'], Configuration::get('GADSENSE_DESC', $lang['id_lang']));
            $fields['GADSENSE_ESTATE'][$lang['id_lang']] = Tools::getValue('GADSENSE_ESTATE_'.$lang['id_lang'], (Configuration::get('GADSENSE_ESTATE', true)=="Yes"?1:0));
     
        }
  
        return $fields;
    }

    public function renderWidget($hookName, array $params)
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('GradiAdsense'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $params));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('GradiAdsense'));
    }

    public function getWidgetVariables($hookName, array $params)
    {
        $imgname = Configuration::get('GADSENSE_IMG', $this->context->language->id);
        
        if ($imgname && file_exists(_PS_MODULE_DIR_.$this->name.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.$imgname)) {
            $this->smarty->assign('gadsense_img', $this->context->link->protocol_content . Tools::getMediaServer($imgname) . $this->_path . 'img/' . $imgname);
        }

        $gadsense_link = Configuration::get('GADSENSE_LINK', $this->context->language->id);
        if (!$gadsense_link) {
            $gadsense_link = $this->context->link->getPageLink('index');
        }

        return array(
            'gadsense_estate' => Configuration::get('GADSENSE_ESTATE', $this->context->language->id),
            'gadsense_title' => Configuration::get('GADSENSE_TITLE', $this->context->language->id),
            'gadsense_cta' => Configuration::get('GADSENSE_CTA', $this->context->language->id),
            'gadsense_link_cta' => Configuration::get('GADSENSE_LINK_CTA', $this->context->language->id),
            'gadsense_desc' => Configuration::get('GADSENSE_DESC', $this->context->language->id)
        );
    }

    private function updateUrl($link)
    {
        if (substr($link, 0, 7) !== "http://" && substr($link, 0, 8) !== "https://") {
            $link = "http://" . $link;
        }

        return $link;
    }
}
