<?php

if (!defined('_PS_VERSION_'))
	exit;

class Totalskroutzanalytics extends Module
{

	protected $_errors = array();
	private $config_prefix = 'TOTALSKROUTZANALYTICS_';


	public function __construct()
	{
		$this->name = 'totalskroutzanalytics';
		$this->tab = 'front_office_features';
		$this->version = '2.0';
		$this->author = 'netcraft.gr';
		$this->need_instance = 0;


		$this->bootstrap = true;


	 	parent::__construct();

		$this->displayName = $this->l('Skroutz Analytics for Total XML Exporter');
		$this->description = $this->l('Complete Skroutz Analytics module');
		$this->confirmUninstall = $this->l('Are you sure you want to delete this module?');
	}
	
	public function install()
	{
		if (!parent::install() OR
			!$this->registerHook('orderConfirmation') OR
			!$this->registerHook('displayHeader')
			)
			return false;
		return true;
	}
	
	public function uninstall()
	{
		if (!parent::uninstall() OR
			!Configuration::deleteByName($this->getConfigKey('ID')) OR
			!Configuration::deleteByName($this->getConfigKey('COD_PAYMENT')) OR
			!Configuration::deleteByName($this->getConfigKey('COD_FEE'))
			)
			return false;
		return true;
	}
	
	public function getContent()
	{
		$html = '';
		$this->migrateLegacyConfig();
		$module_title = 'Total Skroutz Analytics Module by <a href="http://netcraft.gr" target="_blank" rel="noopener noreferrer">netcraft.gr</a>';
		$compat_note = 'This Skroutz Analytics module is compatible with <a href="https://netcraft.gr/product/total-xml-exporter-prestashop-feeds-for-skroutz/" target="_blank" rel="noopener noreferrer">Total XML Exporter</a> feeds';
		
		if(Tools::isSubmit('submitUpdate'))
		{
			Configuration::updateValue($this->getConfigKey('ID'), trim(Tools::getValue('accountid')));
			Configuration::updateValue($this->getConfigKey('COD_PAYMENT'), trim(Tools::getValue('skroutz_cod_payment_name', '')));

			$cod_fee_raw = trim(Tools::getValue('skroutz_cod_fee', ''));
			$cod_fee_raw = str_replace(',', '.', $cod_fee_raw);
			$cod_fee = is_numeric($cod_fee_raw) ? (float)$cod_fee_raw : 0.0;
			if ($cod_fee < 0) {
				$cod_fee = 0.0;
			}
			Configuration::updateValue($this->getConfigKey('COD_FEE'), $cod_fee);
			$html .= $this->displayConfirmation($this->l('Settings Updated'));
		}

		$payment_methods = array();
		$installed_payment_modules = PaymentModule::getInstalledPaymentModules();
		if (is_array($installed_payment_modules)) {
			foreach ($installed_payment_modules as $pm) {
				if (empty($pm['name'])) {
					continue;
				}

				$module = Module::getInstanceByName($pm['name']);
				$display_name = $module && !empty($module->displayName) ? $module->displayName : $pm['name'];
				$payment_methods[] = array(
					'name' => $pm['name'],
					'display_name' => $display_name,
				);
			}
		}
		usort($payment_methods, array($this, 'sortPaymentMethodsByDisplayName'));

		$selected_cod_payment = Configuration::get($this->getConfigKey('COD_PAYMENT'));
		$cod_fee = (float)Configuration::get($this->getConfigKey('COD_FEE'));
		
		$html .= '
		<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post" class="defaultForm form-horizontal">
			<div class="panel">
				<div class="panel-heading">'.$module_title.'</div>
				<div class="panel-body">';
				
		$html .='
		<div class="form-group">
			<label class="control-label col-lg-3">'.$this->l('Shop Account ID').'</label>
			<div class="col-lg-6">
				<input type="text" name="accountid" class="form-control" value="'.Tools::safeOutput(Configuration::get($this->getConfigKey('ID'))).'">
			</div>
		</div>
		';

		$html .='
		<div class="form-group">
			<label class="control-label col-lg-3">'.$this->l('COD Payment Method').'</label>
			<div class="col-lg-6">
				<select name="skroutz_cod_payment_name" class="form-control">
					<option value="">'.$this->l('-- Select COD payment method --').'</option>';

		foreach ($payment_methods as $pm) {
			$selected = ($selected_cod_payment == $pm['display_name']) ? ' selected="selected"' : '';
			$html .= '
					<option value="'.Tools::safeOutput($pm['display_name']).'"'.$selected.'>'.Tools::safeOutput($pm['display_name']).'</option>';
		}

		$html .='
				</select>
				<p class="help-block">'.$this->l('Choose the payment method name used for Cash on Delivery.').'</p>
			</div>
		</div>
		<div class="form-group">
			<label class="control-label col-lg-3">'.$this->l('COD Fee').'</label>
			<div class="col-lg-6">
				<input type="text" name="skroutz_cod_fee" class="form-control" value="'.Tools::safeOutput($cod_fee).'" placeholder="0.00">
				<p class="help-block">'.$this->l('Fee amount to subtract from revenue and shipping for COD orders.').'</p>
			</div>
		</div>
		';
		
		$html .='
		<div class="form-group">
			<div class="col-lg-6 col-lg-offset-3">
				<button type="submit" name="submitUpdate" class="btn btn-primary" style="font-weight:700; letter-spacing:0; padding:10px 18px; box-shadow:0 2px 0 rgba(0,0,0,0.18); border-width:0;">
					'.$this->l('Save').'
				</button>
				<div style="margin-top:10px; line-height:1.5; color:#6b6f76;">
					'.$compat_note.'
				</div>
			</div>
		</div>
		';
		
		$html .='
			</div>
		</form>
		';
		
		return $html;
	}

	public function sortPaymentMethodsByDisplayName($a, $b)
	{
		return strcasecmp($a['display_name'], $b['display_name']);
	}

	private function getConfigKey($suffix)
	{
		return $this->config_prefix . $suffix;
	}

	private function migrateLegacyConfig()
	{
		$legacyMap = array(
			'ID' => 'SKROUTZANALYTICS_ID',
			'COD_PAYMENT' => 'SKROUTZANALYTICS_COD_PAYMENT',
			'COD_FEE' => 'SKROUTZANALYTICS_COD_FEE',
		);

		foreach ($legacyMap as $suffix => $legacyKey) {
			$newKey = $this->getConfigKey($suffix);
			if (!Configuration::get($newKey) && Configuration::get($legacyKey)) {
				Configuration::updateValue($newKey, Configuration::get($legacyKey));
			}
		}
	}
	
	public function hookHeader($params)
	{
		$skroutz_id = Configuration::get($this->getConfigKey('ID'));
		
		$this->context->smarty->assign(array(
			'skroutz_id' => $skroutz_id
		));
		
		return $this->display(__FILE__, 'totalskroutzanalyticsscript.tpl');
	}


	public function hookOrderConfirmation($params)
	{
		//var_dump($params['order']);

		$skroutz_id = Configuration::get($this->getConfigKey('ID'));
		$order = $params['order'];
		$products = $order->getProducts();
		$cod_payment_name = Configuration::get($this->getConfigKey('COD_PAYMENT'));
		$cod_fee = (float)Configuration::get($this->getConfigKey('COD_FEE'));

		// var_dump($products);
		$this->context->smarty->assign(array(
			'order'=> $order,
			'order_products' => $products,
			'skroutz_id' => $skroutz_id,
			'skroutz_cod_payment_name' => $cod_payment_name ? $cod_payment_name : '',
			'skroutz_cod_fee' => $cod_fee
		));
		
		return $this->display(__FILE__, 'totalskroutzanalytics.tpl');
	}

	
}
