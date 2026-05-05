<?php

if (!defined('_PS_VERSION_'))
	exit;

class Totalskroutzanalytics extends Module
{

	protected $_errors = array();


	public function __construct()
	{
		$this->name = 'totalskroutzanalytics';
		$this->tab = 'front_office_features';
		$this->version = '1.1';
		$this->author = 'netcraft.gr';
		$this->need_instance = 0;


		$this->bootstrap = true;


	 	parent::__construct();

		$this->displayName = $this->l('Skroutz Analytics for Total XML Exporter');
		$this->description = $this->l('Adds a block.');
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
			!Configuration::deleteByName('TOTALSKROUTZANALYTICS_ID') OR
			!Configuration::deleteByName('TOTALSKROUTZANALYTICS_COD_PAYMENT') OR
			!Configuration::deleteByName('TOTALSKROUTZANALYTICS_COD_FEE')
			)
			return false;
		return true;
	}
	
	public function getContent()
	{
		$html = '';
		
		if(Tools::isSubmit('submitUpdate'))
		{
			Configuration::updateValue('TOTALSKROUTZANALYTICS_ID', trim(Tools::getValue('accountid')));
			Configuration::updateValue('TOTALSKROUTZANALYTICS_COD_PAYMENT', trim(Tools::getValue('skroutz_cod_payment_name', '')));

			$cod_fee_raw = trim(Tools::getValue('skroutz_cod_fee', ''));
			$cod_fee_raw = str_replace(',', '.', $cod_fee_raw);
			$cod_fee = is_numeric($cod_fee_raw) ? (float)$cod_fee_raw : 0.0;
			if ($cod_fee < 0) {
				$cod_fee = 0.0;
			}
			Configuration::updateValue('TOTALSKROUTZANALYTICS_COD_FEE', $cod_fee);
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

		$selected_cod_payment = Configuration::get('TOTALSKROUTZANALYTICS_COD_PAYMENT');
		$cod_fee = (float)Configuration::get('TOTALSKROUTZANALYTICS_COD_FEE');
		
		$html .= '
		<form action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'" method="post" class="defaultForm form-horizontal">
			<div class="panel">
				<div class="panel-heading">'.$this->l('Settings').'</div>';
				
		$html .='
		<div class="form-group">
			<label class="control-label col-lg-3">'.$this->l('Shop Account ID').'</label>
			<div class="col-lg-6">
				<input type="text" name="accountid" class="form-control" value="'.Tools::safeOutput(Configuration::get('TOTALSKROUTZANALYTICS_ID')).'">
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
		<input type="submit" name="submitUpdate" value="'.$this->l('Save').'" class="btn btn-default">
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
	
	public function hookHeader($params)
	{
		$skroutz_id = Configuration::get('TOTALSKROUTZANALYTICS_ID');
		
		$this->context->smarty->assign(array(
			'skroutz_id' => $skroutz_id
		));
		
		return $this->display(__FILE__, 'totalskroutzanalyticsscript.tpl');
	}


	public function hookOrderConfirmation($params)
	{
		//var_dump($params['order']);

		$skroutz_id = Configuration::get('TOTALSKROUTZANALYTICS_ID');
		$order = $params['order'];
		$products = $order->getProducts();
		$cod_payment_name = Configuration::get('TOTALSKROUTZANALYTICS_COD_PAYMENT');
		$cod_fee = (float)Configuration::get('TOTALSKROUTZANALYTICS_COD_FEE');

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
