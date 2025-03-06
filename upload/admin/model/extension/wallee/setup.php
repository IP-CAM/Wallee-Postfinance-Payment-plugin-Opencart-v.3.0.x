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
use Wallee\Model\AbstractModel;

class ModelExtensionWalleeSetup extends AbstractModel {

	public function install(){
		$this->load->model("extension/wallee/migration");
		$this->load->model('extension/wallee/modification');
		$this->load->model('extension/wallee/dynamic');
		
		$this->model_extension_wallee_migration->migrate();
		
		try {
			$this->model_extension_wallee_modification->install();
			$this->model_extension_wallee_dynamic->install();
		}
		catch (Exception $e) {
		}
		
		$this->addPermissions();
		$this->addEvents();
	}

	public function synchronize($space_id){
		\WalleeHelper::instance($this->registry)->refreshApiClient();
		\WalleeHelper::instance($this->registry)->refreshWebhook();
		\Wallee\Service\MethodConfiguration::instance($this->registry)->synchronize($space_id);
	}

	public function uninstall($purge = true){
		$this->load->model("extension/wallee/migration");
		$this->load->model('extension/wallee/modification');
		$this->load->model('extension/wallee/dynamic');
		
		$this->model_extension_wallee_dynamic->uninstall();
		if ($purge) {
			$this->model_extension_wallee_migration->purge();
		}
		$this->model_extension_wallee_modification->uninstall();
		
		$this->removeEvents();
		$this->removePermissions();
	}

	private function addEvents(){
		$this->getEventModel()->addEvent('wallee_create_dynamic_files', 'admin/controller/marketplace/modification/after',
				'extension/wallee/event/createMethodConfigurationFiles');
		$this->getEventModel()->addEvent('wallee_can_save_order', 'catalog/model/checkout/order/editOrder/before',
				'extension/wallee/event/canSaveOrder');
		$this->getEventModel()->addEvent('wallee_update_items_after_edit', 'catalog/controller/api/order/edit/after', 'extension/wallee/event/update');
		$this->getEventModel()->addEvent('wallee_include_scripts', 'catalog/controller/common/header/before',
				'extension/wallee/event/includeScripts');
	}

	private function removeEvents(){
		$this->getEventModel()->deleteEventByCode('wallee_create_dynamic_files');
		$this->getEventModel()->deleteEventByCode('wallee_can_save_order');
		$this->getEventModel()->deleteEventByCode('wallee_update_items_after_edit');
		$this->getEventModel()->deleteEventByCode('wallee_include_scripts');
	}

	/**
	 * Adds basic permissions.
	 * Permissions per payment method are added while creating the dynamic files.
	 */
	private function addPermissions(){
		$this->load->model("user/user_group");
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/event');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/completion');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/void');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/refund');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/update');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/pdf');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/alert');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/transaction');
	}

	private function removePermissions(){
		$this->load->model("user/user_group");
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/event');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/completion');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/void');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/refund');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/update');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/pdf');
		$this->model_user_user_group->removePermission($this->user->getGroupId(), 'access', 'extension/wallee/alert');
		$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/wallee/transaction');
	}
}