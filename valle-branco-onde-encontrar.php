<?php
/**
 * Plugin Name:       Valle Branco — Onde Encontrar
 * Plugin URI:        https://github.com/ElissonLDR/valle-branco-onde-encontrar
 * Description:       Mapa de pontos de venda com produtos, painel de controle e API para automação n8n (notas fiscais).
 * Version:           1.3.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Valle Branco
 * Author URI:        https://vallebranco.com.br
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       valle-branco-onde-encontrar
 * Domain Path:       /languages
 *
 * @package ValleBrancoOndeEncontrar
 */

// Impede acesso direto ao arquivo.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Constantes do plugin (úteis para atualizações e caminhos).
define( 'VB_OE_VERSION', '1.3.1' );
define( 'VB_OE_DB_VERSION', '1.0.0' );
define( 'VB_OE_FILE', __FILE__ );
define( 'VB_OE_PATH', plugin_dir_path( __FILE__ ) );
define( 'VB_OE_URL', plugin_dir_url( __FILE__ ) );
define( 'VB_OE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Carrega as classes principais.
 */
require_once VB_OE_PATH . 'includes/class-vb-database.php';
require_once VB_OE_PATH . 'includes/class-vb-activator.php';
require_once VB_OE_PATH . 'includes/class-vb-deactivator.php';
require_once VB_OE_PATH . 'includes/class-vb-cpt.php';
require_once VB_OE_PATH . 'includes/class-vb-meta.php';
require_once VB_OE_PATH . 'includes/class-vb-sap.php';
require_once VB_OE_PATH . 'includes/class-vb-relacao.php';
require_once VB_OE_PATH . 'includes/class-vb-rest-api.php';
require_once VB_OE_PATH . 'includes/class-vb-admin.php';
require_once VB_OE_PATH . 'includes/class-vb-frontend.php';
require_once VB_OE_PATH . 'includes/class-vb-elementor.php';
require_once VB_OE_PATH . 'includes/class-vb-sync-n8n.php';
require_once VB_OE_PATH . 'includes/class-vb-updater.php';
require_once VB_OE_PATH . 'includes/class-vb-plugin.php';

/**
 * Ativação: cria tabelas e opções iniciais.
 */
function vb_oe_activate() {
	VB_OE_Activator::activate();
}
register_activation_hook( __FILE__, 'vb_oe_activate' );

/**
 * Desativação: limpeza leve (não apaga dados).
 */
function vb_oe_deactivate() {
	VB_OE_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'vb_oe_deactivate' );

/**
 * Inicia o plugin depois que o WordPress carrega.
 */
function vb_oe_run() {
	$plugin = new VB_OE_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'vb_oe_run' );
