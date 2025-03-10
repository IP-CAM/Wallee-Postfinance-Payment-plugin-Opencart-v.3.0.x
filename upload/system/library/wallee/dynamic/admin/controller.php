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
require_once modification(DIR_SYSTEM . "library/wallee/helper.php");
use Wallee\Controller\AbstractController;

abstract class ControllerExtensionPaymentWalleeBase extends AbstractController {

	public function order(){
		if(!$this->config->get('wallee_status')) {
			return '';
		}
		
		if (!isset($this->request->get['order_id'])) {
			return '';
		}
		$order_id = $this->request->get['order_id'];
		
		if (!\WalleeHelper::instance($this->registry)->isValidOrder($order_id)) {
			return '';
		}
		
		$transaction_info = \Wallee\Entity\TransactionInfo::loadByOrderId($this->registry, $order_id);
		
		$refund_jobs = \Wallee\Entity\RefundJob::loadByOrder($this->registry, $order_id);
		$completion_jobs = \Wallee\Entity\CompletionJob::loadByOrder($this->registry, $order_id);
		$void_jobs = \Wallee\Entity\VoidJob::loadByOrder($this->registry, $order_id);
		
		$this->language->load('extension/payment/wallee');
		
		$job_groups = array(
			array(
				'title' => $this->language->get('title_transaction_information'),
				'jobs' => array(
					array(
						'label_groups' => $this->getLabelTemplateVariables($transaction_info->getLabels(),
								$this->getTransactionDefaultLabels($transaction_info)) 
					) 
				) 
			) 
		);
		
		if ($completion_jobs) {
			$jobs = array();
			foreach ($completion_jobs as $job) {
				$jobs[] = array(
					'title' => sprintf($this->language->get('label_completion'), $job->getJobId()),
					'label_groups' => $this->getLabelTemplateVariables($job->getLabels(), $this->getCompletionDefaultLabels($job)) 
				);
			}
			
			$job_groups[] = array(
				'title' => $this->language->get('title_completion'),
				'jobs' => $jobs 
			);
		}
		
		if ($refund_jobs) {
			$jobs = array();
			foreach ($refund_jobs as $job) {
				$jobs[] = array(
					'title' => sprintf($this->language->get('label_refund'), $job->getJobId()),
					'label_groups' => $this->getLabelTemplateVariables($job->getLabels(), $this->getRefundDefaultLabels($job)) 
				);
			}
			
			$job_groups[] = array(
				'title' => $this->language->get('title_refund'),
				'jobs' => $jobs 
			);
		}
		
		if ($void_jobs) {
			$jobs = array();
			foreach ($void_jobs as $job) {
				$jobs[] = array(
					'title' => sprintf($this->language->get('label_void'), $job->getJobId()),
					'label_groups' => $this->getLabelTemplateVariables($job->getLabels(), $this->getVoidDefaultLabels($job)) 
				);
			}
			
			$job_groups[] = array(
				'title' => $this->language->get('title_void'),
				'jobs' => $jobs 
			);
		}
		
		return WalleeVersionHelper::wrapJobLabels($this->registry, $this->loadView('extension/wallee/labels', array(
			'job_groups' => $job_groups
		)));
	}

	private function getLabelHeader($title){
		return "<h4>$title</h4><hr/>";
	}

	private function getWalleeLink($type, $space, $id, $link_text){
		$base_url = \WalleeHelper::getBaseUrl();
		$url = "$base_url/s/$space/payment/$type/view/$id";
		return "<a href='$url' target='_blank'>$link_text</a>";
	}

	private function getDownloadLink($type){
		$link_text = $this->language->get('link_download');
		$url = $this->createUrl('extension/wallee/pdf/' . $type,
				array(
					'order_id' => $this->request->get['order_id'],
					\WalleeVersionHelper::TOKEN => $this->session->data[\WalleeVersionHelper::TOKEN] 
				));
		return "<a href='$url' target='_blank'>$link_text</a>";
	}

	private function getTransactionDefaultLabels(\Wallee\Entity\TransactionInfo $transaction_info){
		$labels = array(
			'default' => array(
				'name' => $this->language->get('label_default'),
				'description' => $this->language->get('description_default_transaction_information'),
				'labels' => array(
					array(
						'name' => $this->language->get('label_wallee_id'),
						'description' => $this->language->get('description_wallee_id'),
						'value' => $transaction_info->getTransactionId() 
					),
					array(
						'name' => $this->language->get('label_wallee_link'),
						'description' => $this->language->get('description_wallee_link'),
						'value' => $this->getWalleeLink('transaction', $transaction_info->getSpaceId(), $transaction_info->getTransactionId(),
								$this->language->get('link_transaction')) 
					),
					array(
						'name' => $this->language->get('label_status'),
						'description' => $this->language->get('label_status'),
						'value' => $transaction_info->getState() 
					) 
				) 
			) 
		);
		
		$download_labels = array();
		if (in_array($transaction_info->getState(),
				array(
					\Wallee\Sdk\Model\TransactionState::COMPLETED,
					\Wallee\Sdk\Model\TransactionState::FULFILL,
					\Wallee\Sdk\Model\TransactionState::DECLINE 
				))) {
			$download_labels[] = array(
				'name' => $this->language->get('label_invoice'),
				'description' => $this->language->get('description_invoice'),
				'value' => $this->getDownloadLink('invoice') 
			);
		}
		if ($transaction_info->getState() == \Wallee\Sdk\Model\TransactionState::FULFILL) {
			$download_labels[] = array(
				'name' => $this->language->get('label_packing'),
				'description' => $this->language->get('description_packing'),
				'value' => $this->getDownloadLink('packingSlip') 
			);
		}
		if ($download_labels) {
			$labels['downloads'] = array(
				'name' => $this->language->get('title_downloads'),
				'description' => $this->language->get('description_downloads'),
				'labels' => $download_labels 
			);
		}
		
		if ($transaction_info->getFailureReason() != null) {
			$labels['default']['labels'][] = array(
				'name' => $this->language->get('label_failure'),
				'description' => $this->language->get('description_failure'),
				'value' => $transaction_info->getFailureReason() 
			);
		}
		
		return $labels;
	}

	private function getCompletionDefaultLabels(\Wallee\Entity\CompletionJob $job){
		$labels = array(
			'default' => array(
				'name' => $this->language->get('label_default'),
				'description' => $this->language->get('description_default_completion'),
				'labels' => array() 
			) 
		);
		
		$this->addDefaultJobInformation($labels, $job, 'completion');
		
		return $labels;
	}

	private function addDefaultJobInformation(array &$labels, \Wallee\Entity\AbstractJob $job, $link_type){
		$labels['default']['labels'][] = array(
			'name' => $this->language->get('label_status'),
			'description' => $this->language->get('label_status'),
			'value' => $job->getState() 
		);
		
		if ($job->getJobId()) {
			$labels['default']['labels'][] = array(
				'name' => $this->language->get('label_wallee_id'),
				'description' => $this->language->get('description_wallee_id'),
				'value' => $job->getJobId() 
			);
			$labels['default']['labels'][] = array(
				'name' => $this->language->get('label_wallee_link'),
				'description' => $this->language->get('description_wallee_link'),
				'value' => $this->getWalleeLink($link_type, $job->getSpaceId(), $job->getJobId(), $this->language->get('link_' . $link_type)) 
			);
		}
		
		if ($job->getFailureReason() != null) {
			$labels['default']['labels'][] = array(
				'name' => $this->language->get('label_failure'),
				'description' => $this->language->get('description_failure'),
				'value' => $job->getFailureReason() 
			);
		}
	}

	private function getRefundDefaultLabels(\Wallee\Entity\RefundJob $job){
		$labels = array(
			'default' => array(
				'name' => $this->language->get('label_default'),
				'description' => $this->language->get('description_default_refund'),
				'labels' => array(
					array(
						'name' => $this->language->get('label_external'),
						'description' => $this->language->get('description_external'),
						'value' => $job->getExternalId() 
					),
					array(
						'name' => $this->language->get('label_amount'),
						'description' => $this->language->get('description_refund_amount'),
						'value' => $job->getAmount() 
					),
					array(
						'name' => $this->language->get('label_restock'),
						'description' => $this->language->get('description_restock'),
						'value' => $job->getRestock() ? $this->language->get('yes') : $this->language->get('no') 
					) 
				) 
			) 
		);
		
		$this->addDefaultJobInformation($labels, $job, 'refund');
		
		return $labels;
	}

	private function getVoidDefaultLabels(\Wallee\Entity\VoidJob $job){
		$labels = array(
			'default' => array(
				'name' => $this->language->get('label_default'),
				'description' => $this->language->get('description_default_void'),
				'labels' => array() 
			) 
		);
		$this->addDefaultJobInformation($labels, $job, 'void');
		
		return $labels;
	}

	
	/**
	 *
	 * @param map[string, string] $labels Translateable labels, from entities
	 * @param array $display_labels Optional additional labels.
	 * @return string
	 */
	private function getLabelTemplateVariables($labels, $display_labels = array()){
		if ($labels) {
			foreach ($labels as $label_id => $label_value) {
				$label_decriptor = \Wallee\Provider\LabelDescriptor::instance($this->registry)->find($label_id);
				if(!$label_decriptor) {
					\WalleeHelper::instance($this->registry)->log("Could not find label descriptor for id $label_id, skipping", \WalleeHelper::LOG_ERROR);
					continue;
				}
				$group_id = $label_decriptor->getGroup();
				if (!isset($display_labels[$group_id])) {
					$label_group = \Wallee\Provider\LabelDescriptionGroup::instance($this->registry)->find($group_id);
					if(!$label_group) {
						\WalleeHelper::instance($this->registry)->log("Could not find label group for id $group_id, skipping", \WalleeHelper::LOG_ERROR);
						continue;
					}
					$display_labels[$group_id] = array(
						'name' => htmlspecialchars(\WalleeHelper::instance($this->registry)->translate($label_group->getName()),
								ENT_HTML5 | ENT_QUOTES),
						'description' => htmlspecialchars(\WalleeHelper::instance($this->registry)->translate($label_group->getDescription()),
								ENT_HTML5 | ENT_QUOTES),
						'labels' => array()
					);
				}
				$display_labels[$group_id]['labels'][] = array(
					'name' => htmlspecialchars(\WalleeHelper::instance($this->registry)->translate($label_decriptor->getName()),
							ENT_HTML5 | ENT_QUOTES),
					'description' => htmlspecialchars(\WalleeHelper::instance($this->registry)->translate($label_decriptor->getDescription()),
							ENT_HTML5 | ENT_QUOTES),
					'value' => htmlspecialchars($label_value, ENT_HTML5 | ENT_QUOTES)
				);
			}
		}
		
		return $display_labels;
	}
	
	protected function getRequiredPermission(){
		return ''; // see isValidOrder
	}

	protected abstract function getCode();
}