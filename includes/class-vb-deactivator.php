<?php
/**
 * Desativação do plugin.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Deactivator
 */
class VB_OE_Deactivator {

	/**
	 * Roda na desativação (não apaga dados).
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
