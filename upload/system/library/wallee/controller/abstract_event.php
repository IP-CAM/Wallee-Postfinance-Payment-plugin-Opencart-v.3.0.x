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

namespace Wallee\Controller;

abstract class AbstractEvent extends AbstractController {

	protected function validate(){
		$this->language->load('extension/payment/wallee');
		$this->validatePermission();
		// skip valdiating order.
	}
}