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
 * Webhook processor to handle delivery indication state transitions.
 */
class DeliveryIndication extends AbstractOrderRelated {

	/**
	 *
	 * @see AbstractOrderRelated::load_entity()
	 * @return \Wallee\Sdk\Model\DeliveryIndication
	 */
	protected function loadEntity(Request $request){
		$delivery_indication_service = new \Wallee\Sdk\Service\DeliveryIndicationService(\WalleeHelper::instance($this->registry)->getApiClient());
		return $delivery_indication_service->read($request->getSpaceId(), $request->getEntityId());
	}

	protected function getOrderId($delivery_indication){
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */
		return $delivery_indication->getTransaction()->getMerchantReference();
	}

	protected function getTransactionId($delivery_indication){
		/* @var $delivery_indication \Wallee\Sdk\Model\DeliveryIndication */
		return $delivery_indication->getLinkedTransaction();
	}

	protected function processOrderRelatedInner(array $order_info, $delivery_indication){
		/* @var \Wallee\Sdk\Model\DeliveryIndication $delivery_indication */
		switch ($delivery_indication->getState()) {
			case \Wallee\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
				$this->review($order_info);
				break;
			default:
				// Nothing to do.
				break;
		}
	}

	protected function review(array $order_info){
		\WalleeHelper::instance($this->registry)->addOrderHistory($order_info['order_id'], $order_info['order_status_id'],
				\WalleeHelper::instance($this->registry)->getTranslation('message_webhook_manual'), true);
	}
}