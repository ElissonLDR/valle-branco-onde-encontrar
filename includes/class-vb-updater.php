<?php
/**
 * Preparado para atualizações futuras do plugin.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Updater
 */
class VB_OE_Updater {

	/**
	 * Verifica se precisa atualizar banco ou opções.
	 */
	public function maybe_update() {
		$db_version = get_option( 'vb_oe_db_version', '0' );

		if ( version_compare( $db_version, VB_OE_DB_VERSION, '<' ) ) {
			VB_OE_Database::create_tables();
		}

		$settings = get_option( 'vb_oe_settings', array() );
		if ( empty( $settings['n8n_webhook_url'] ) ) {
			$settings['n8n_webhook_url'] = 'https://n8n.v4companyamaral.com/webhook-test/8f02e2f2-0a49-4daf-9dfd-b8f55e7788ff';
			update_option( 'vb_oe_settings', $settings );
		}
	}
}
