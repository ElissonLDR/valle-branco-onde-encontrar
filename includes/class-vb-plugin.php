<?php
/**
 * Orquestra o plugin (carrega módulos).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Plugin
 */
class VB_OE_Plugin {

	/**
	 * Inicia tudo.
	 */
	public function run() {
		// Atualizações de banco/versão.
		$updater = new VB_OE_Updater();
		$updater->maybe_update();

		// CPTs.
		add_action( 'init', array( 'VB_OE_CPT', 'register' ) );

		// Metadados.
		$meta = new VB_OE_Meta();
		$meta->hooks();

		// API REST (n8n + mapa).
		$api = new VB_OE_REST_API();
		$api->hooks();

		// Painel.
		if ( is_admin() ) {
			$admin = new VB_OE_Admin();
			$admin->hooks();
		}

		// Front (shortcodes + mapa).
		$front = new VB_OE_Frontend();
		$front->hooks();

		// Widgets do Elementor (os hooks só disparam se o Elementor estiver ativo).
		$elementor = new VB_OE_Elementor();
		$elementor->hooks();

		// Atualização diária via webhook n8n.
		$sync = new VB_OE_Sync_N8N();
		$sync->hooks();

		// Geocodificação de endereços → lat/lng para o mapa.
		$geo = new VB_OE_Geocoder();
		$geo->hooks();
	}
}
