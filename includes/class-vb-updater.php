<?php
/**
 * Preparado para atualizações futuras do plugin.
 *
 * Compara a versão instalada com VB_OE_VERSION / VB_OE_DB_VERSION
 * e roda migrações quando necessário.
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

		// Espaço para migrações futuras, ex.:
		// if ( version_compare( $db_version, '1.1.0', '<' ) ) { ... }
	}
}
