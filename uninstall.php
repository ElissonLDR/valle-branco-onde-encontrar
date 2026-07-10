<?php
/**
 * Remoção completa do plugin (só roda se o usuário desinstalar).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Remove opções do plugin.
delete_option( 'vb_oe_db_version' );
delete_option( 'vb_oe_api_key' );
delete_option( 'vb_oe_settings' );

// Remove tabela de relação produto × local.
$tabela = $wpdb->prefix . 'vb_produto_local';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$tabela}" );

// Remove posts dos CPTs (opcional — descomente se quiser apagar tudo).
/*
$tipos = array( 'vb_produto', 'vb_estabelecimento' );
foreach ( $tipos as $tipo ) {
	$posts = get_posts(
		array(
			'post_type'      => $tipo,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);
	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}
*/
