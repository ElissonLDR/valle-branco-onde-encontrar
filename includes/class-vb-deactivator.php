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
		wp_clear_scheduled_hook( 'vb_oe_sync_diario' );
		flush_rewrite_rules();
	}
}
