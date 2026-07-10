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
		$nova     = VB_OE_Sync_N8N::DEFAULT_WEBHOOK;
		$antiga   = isset( $settings['n8n_webhook_url'] ) ? $settings['n8n_webhook_url'] : '';

		// Atualiza URL antiga (test / domínio antigo) para a de produção.
		if (
			empty( $antiga )
			|| false !== strpos( $antiga, 'webhook-test' )
			|| false !== strpos( $antiga, 'n8n.v4companyamaral.com/webhook' )
		) {
			$settings['n8n_webhook_url'] = $nova;
			update_option( 'vb_oe_settings', $settings );
		}
	}
}
