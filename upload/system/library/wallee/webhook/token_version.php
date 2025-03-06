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
 * Webhook processor to handle token version state transitions.
 */
class TokenVersion extends AbstractWebhook {

	public function process(Request $request){
		$token_service = \Wallee\Service\Token::instance($this->registry);
		$token_service->updateTokenVersion($request->getSpaceId(), $request->getEntityId());
	}
}