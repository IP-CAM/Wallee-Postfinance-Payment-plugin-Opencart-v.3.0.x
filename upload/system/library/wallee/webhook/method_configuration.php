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
namespace Wallee\Webhook;

/**
 * Webhook processor to handle payment method configuration state transitions.
 */
class MethodConfiguration extends AbstractWebhook {

	/**
	 * Synchronizes the payment method configurations on state transition.
	 *
	 * @param Request $request
	 */
	public function process(Request $request){
		$payment_method_configuration_service = \Wallee\Service\MethodConfiguration::instance($this->registry);
		$space_id = $this->registry->get('config')->get('wallee_space_id');
		$payment_method_configuration_service->synchronize($space_id);
	}
}