<?php
/**
 * Ativação do plugin.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Activator
 */
class VB_OE_Activator {

	/**
	 * Roda na ativação.
	 */
	public static function activate() {
		VB_OE_Database::create_tables();
		VB_OE_CPT::register();
		flush_rewrite_rules();

		// Gera chave de API para o n8n (se ainda não existir).
		if ( ! get_option( 'vb_oe_api_key' ) ) {
			update_option( 'vb_oe_api_key', wp_generate_password( 32, false, false ) );
		}

		// Configurações padrão.
		if ( ! get_option( 'vb_oe_settings' ) ) {
			update_option(
				'vb_oe_settings',
				array(
					'dias_alerta'     => 90,
					'mapa_zoom'      => 7,
					'mapa_lat'       => -23.0,
					'mapa_lng'       => -49.5,
					'n8n_webhook_url' => 'https://n8n.v4companyamaral.com/webhook-test/8f02e2f2-0a49-4daf-9dfd-b8f55e7788ff',
				)
			);
		} else {
			// Garante a URL do webhook n8n em instalações antigas.
			$settings = get_option( 'vb_oe_settings', array() );
			if ( empty( $settings['n8n_webhook_url'] ) ) {
				$settings['n8n_webhook_url'] = 'https://n8n.v4companyamaral.com/webhook-test/8f02e2f2-0a49-4daf-9dfd-b8f55e7788ff';
				update_option( 'vb_oe_settings', $settings );
			}
		}
	}
}
