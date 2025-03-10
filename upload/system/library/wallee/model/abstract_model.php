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

namespace Wallee\Model;

abstract class AbstractModel extends \Model {
	private $event_model;
	private $extension_model;
	private $modification_model;

	protected function createUrl($route, $query, $ssl = 'SSL'){
		return \WalleeVersionHelper::createUrl($this->url, $route, $query, $ssl);
	}

	protected function getEventModel(){
		if ($this->event_model == null) {
			$this->loadEventModel();
		}
		return $this->event_model;
	}

	protected function getExtensionModel(){
		if ($this->extension_model == null) {
			$this->loadExtensionModel();
		}
		return $this->extension_model;
	}

	protected function getModificationModel(){
		if ($this->modification_model == null) {
			$this->loadModificationModel();
		}
		return $this->modification_model;
	}

	private function loadModificationModel(){
		$this->load->model('setting/modification');
		$this->modification_model = $this->model_setting_modification;
	}

	private function loadExtensionModel(){
		$this->load->model('setting/extension');
		$this->extension_model = $this->model_setting_extension;
	}

	private function loadEventModel(){
		$this->load->model('setting/event');
		$this->event_model = $this->model_setting_event;
	}
}
