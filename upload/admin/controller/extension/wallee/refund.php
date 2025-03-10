<?php
/**
 * Wallee OpenCart
 *
 * This OpenCart module enables to process payments with Wallee (wallee164).
 *
 * @package Whitelabelshortcut\Wallee
 * @author wallee144 (wallee164)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */
require_once modification(DIR_SYSTEM . 'library/wallee/helper.php');

class ControllerExtensionWalleeRefund extends \Wallee\Controller\AbstractController {

	public function page(){
		$this->language->load('extension/payment/wallee');
		$this->response->addHeader('Content-Type: application/json');
		try {
			$this->validate();
			
			$this->response->setOutput(
					json_encode(
							array(
								'redirect' => $this->createUrl('extension/wallee/refund',
										array(
											\WalleeVersionHelper::TOKEN => $this->request->get[\WalleeVersionHelper::TOKEN],
											'order_id' => $this->request->get['order_id'] 
										)) 
							)));
		}
		catch (Exception $e) {
			$this->response->setOutput(json_encode(array(
				'error' => $e->getMessage() 
			)));
		}
	}

	public function index(){
		try {
			$this->validate();
		}
		catch (Exception $e) {
			$this->displayError($e->getMessage());
			return;
		}
		
		$variables = array();
		$variables['error_warning'] = '';
		$variables['success'] = '';
		
		$this->load->model('sale/order');
		$order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
		$transaction_info = \Wallee\Entity\TransactionInfo::loadByOrderId($this->registry, $this->request->get['order_id']);
		
		$line_items = \Wallee\Service\LineItem::instance($this->registry)->getReducedItemsFromOrder($order_info, $transaction_info->getTransactionId(),
				$transaction_info->getSpaceId());
		$this->document->setTitle($this->language->get('heading_refund'));
		$this->document->addScript('view/javascript/wallee/refund.js');
		
		$variables += $this->loadLanguageVariables();
		$variables += $this->getAdminSurroundingTemplates();
		$variables += $this->getBreadcrumbs();
		
		$variables['line_items'] = $line_items;
		$variables['fixed_tax'] = false;
		foreach ($line_items as $line_item) {
			if (strpos($line_item->getUniqueId(), 'fixed_tax_') === 0) {
				$variables['fixed_tax'] = $this->language->get('description_fixed_tax');
				break;
			}
		}
		
		$currency_info = \Wallee\Provider\Currency::instance($this->registry)->find($order_info['currency_code']);
		if(!$currency_info) {
			$this->displayError($this->language->get('error_currency'));
			return;
		}
		
		$variables['currency_step'] = pow(10, -$currency_info->getFractionDigits());
		$variables['currency_decimals'] = $currency_info->getFractionDigits();
		$variables['cancel'] = $this->createUrl('sale/order/info',
				array(
					\WalleeVersionHelper::TOKEN => $this->session->data[\WalleeVersionHelper::TOKEN],
					'order_id' => $this->request->get['order_id'] 
				));
		$variables['refund_action'] = $this->createUrl('extension/wallee/refund/process',
				array(
					\WalleeVersionHelper::TOKEN => $this->session->data[\WalleeVersionHelper::TOKEN],
					'order_id' => $this->request->get['order_id'] 
				));
		
		$this->response->setOutput($this->loadView("extension/wallee/refund", $variables));
	}

	public function process(){
		try {
			$this->validate();
			
			$transaction_info = \Wallee\Entity\TransactionInfo::loadByOrderId($this->registry, $this->request->get['order_id']);
			
			$running = \Wallee\Entity\RefundJob::loadRunningForOrder($this->registry, $transaction_info->getOrderId());
			if ($running->getId()) {
				throw new \Exception($this->language->get('error_already_running'));
			}
			
			if (!\WalleeHelper::instance($this->registry)->isRefundPossible($transaction_info)) {
				throw new \Exception($this->language->get('error_cannot_create_job'));
			}
			
			$job = \Wallee\Service\Refund::instance($this->registry)->create($transaction_info, $this->request->post['item'],
					isset($this->request->post['restock']));
			\Wallee\Service\Refund::instance($this->registry)->send($job);
			
			$this->response->redirect(
					$this->createUrl('sale/order/info',
							array(
								\WalleeVersionHelper::TOKEN => $this->request->get[\WalleeVersionHelper::TOKEN],
								'order_id' => $this->request->get['order_id'] 
							)));
		}
		catch (Exception $e) {
			$this->displayError($e->getMessage());
		}
	}

	private function getBreadcrumbs(){
		return array(
			'breadcrumbs' => array(
				array(
					'href' => $this->createUrl('common/dashboard',
							array(
								\WalleeVersionHelper::TOKEN => $this->session->data[\WalleeVersionHelper::TOKEN] 
							)),
					'text' => $this->language->get('text_home'),
					'separator' => false 
				),
				array(
					'href' => $this->createUrl('sale/order/info',
							array(
								\WalleeVersionHelper::TOKEN => $this->session->data[\WalleeVersionHelper::TOKEN],
								'order_id' => $this->request->get['order_id'] 
							)),
					'text' => $this->language->get('entry_order'),
					'separator' => false 
				),
				array(
					'href' => '#',
					'text' => $this->language->get('entry_refund'),
					'separator' => false 
				) 
			) 
		);
	}

	private function loadLanguageVariables(){
		$this->load->language('extension/payment/wallee');
		$variables = array(
			'heading_refund',
			'entry_refund',
			'description_refund',
			'entry_name',
			'entry_sku',
			'entry_type',
			'entry_tax',
			'entry_quantity',
			'entry_amount',
			'entry_total',
			'entry_item',
			'entry_id',
			'entry_unit_amount',
			'button_refund',
			'button_reset',
			'button_full',
			'button_cancel',
			'type_fee',
			'type_product',
			'type_discount',
			'entry_order',
			'entry_restock',
			'error_empty_refund',
			'type_shipping' 
		);
		$data = array();
		foreach ($variables as $key) {
			$data[$key] = $this->language->get($key);
		}
		return $data;
	}

	protected function getRequiredPermission(){
		return 'extension/wallee/refund';
	}
}