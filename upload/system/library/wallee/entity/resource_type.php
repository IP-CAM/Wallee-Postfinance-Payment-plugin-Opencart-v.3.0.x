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
namespace Wallee\Entity;

/**
 * Defines the different resource types
 */
interface ResourceType {
	const STRING = 'string';
	const DATETIME = 'datetime';
	const INTEGER = 'integer';
	const BOOLEAN = 'boolean';
	const OBJECT = 'object';
	const DECIMAL = 'decimal';
}