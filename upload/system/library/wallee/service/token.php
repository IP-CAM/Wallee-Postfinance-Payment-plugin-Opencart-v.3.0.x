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

namespace Wallee\Service;

/**
 * This service provides functions to deal with Wallee tokens.
 */
class Token extends AbstractService {
	
	/**
	 * The token API service.
	 *
	 * @var \Wallee\Sdk\Service\TokenService
	 */
	private $token_service;
	
	/**
	 * The token version API service.
	 *
	 * @var \Wallee\Sdk\Service\TokenVersionService
	 */
	private $token_version_service;

	public function updateTokenVersion($space_id, $token_version_id){
		$token_version = $this->getTokenVersionService()->read($space_id, $token_version_id);
		$this->updateInfo($space_id, $token_version);
	}

	public function updateToken($space_id, $token_id){
		$query = new \Wallee\Sdk\Model\EntityQuery();
		$filter = new \Wallee\Sdk\Model\EntityQueryFilter();
		$filter->setType(\Wallee\Sdk\Model\EntityQueryFilterType::_AND);
		$filter->setChildren(
				array(
					$this->createEntityFilter('token.id', $token_id),
					$this->createEntityFilter('state', \Wallee\Sdk\Model\TokenVersionState::ACTIVE) 
				));
		$query->setFilter($filter);
		$query->setNumberOfEntities(1);
		$token_versions = $this->getTokenVersionService()->search($space_id, $query);
		if (!empty($token_versions)) {
			$this->updateInfo($space_id, current($token_versions));
		}
		else {
			$info = \Wallee\Entity\TokenInfo::loadByToken($this->registry, $space_id, $token_id);
			if ($info->getId()) {
				$info->delete($this->registry);
			}
		}
	}

	protected function updateInfo($space_id, \Wallee\Sdk\Model\TokenVersion $token_version){
		$info = \Wallee\Entity\TokenInfo::loadByToken($this->registry, $space_id, $token_version->getToken()->getId());
		if (!in_array($token_version->getToken()->getState(),
				array(
					\Wallee\Sdk\Model\TokenVersionState::ACTIVE,
					\Wallee\Sdk\Model\TokenVersionState::UNINITIALIZED 
				))) {
			if ($info->getId()) {
				$info->delete($this->registry);
			}
			return;
		}
		
		$info->setCustomerId($token_version->getToken()->getCustomerId());
		$info->setName($token_version->getName());
		
		$payment_method = \Wallee\Entity\MethodConfiguration::loadByConfiguration($this->registry, $space_id,
				$token_version->getPaymentConnectorConfiguration()->getPaymentMethodConfiguration()->getId());
		$info->setPaymentMethodId($payment_method->getId());
		$info->setConnectorId($token_version->getPaymentConnectorConfiguration()->getConnector());
		
		$info->setSpaceId($space_id);
		$info->setState($token_version->getToken()->getState());
		$info->setTokenId($token_version->getToken()->getId());
		$info->save();
	}

	public function deleteToken($space_id, $token_id){
		$this->getTokenService()->delete($space_id, $token_id);
	}

	/**
	 * Returns the token API service.
	 *
	 * @return \Wallee\Sdk\Service\TokenService
	 */
	protected function getTokenService(){
		if ($this->token_service == null) {
			$this->token_service = new \Wallee\Sdk\Service\TokenService(\WalleeHelper::instance($this->registry)->getApiClient());
		}
		
		return $this->token_service;
	}

	/**
	 * Returns the token version API service.
	 *
	 * @return \Wallee\Sdk\Service\TokenVersionService
	 */
	protected function getTokenVersionService(){
		if ($this->token_version_service == null) {
			$this->token_version_service = new \Wallee\Sdk\Service\TokenVersionService(\WalleeHelper::instance($this->registry)->getApiClient());
		}
		
		return $this->token_version_service;
	}
}