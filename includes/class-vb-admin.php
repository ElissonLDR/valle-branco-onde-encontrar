<?php
/**
 * Painel administrativo: menu, relatório e configurações.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Admin
 */
class VB_OE_Admin {

	/**
	 * Liga os hooks do admin.
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_vb_oe_salvar_config', array( $this, 'salvar_config' ) );
		add_action( 'admin_post_vb_oe_nova_entrada', array( $this, 'nova_entrada' ) );
		add_action( 'admin_post_vb_oe_atualizar_status', array( $this, 'atualizar_status' ) );
		add_action( 'admin_post_vb_oe_regenerar_chave', array( $this, 'regenerar_chave' ) );
	}

	/**
	 * Menu lateral.
	 */
	public function menu() {
		add_menu_page(
			'Onde Encontrar',
			'Onde Encontrar',
			'manage_options',
			'vb-onde-encontrar',
			array( $this, 'pagina_relatorio' ),
			'dashicons-location-alt',
			26
		);

		add_submenu_page(
			'vb-onde-encontrar',
			'Relatório',
			'Relatório',
			'manage_options',
			'vb-onde-encontrar',
			array( $this, 'pagina_relatorio' )
		);

		add_submenu_page(
			'vb-onde-encontrar',
			'Produtos',
			'Produtos',
			'edit_posts',
			'edit.php?post_type=vb_produto'
		);

		add_submenu_page(
			'vb-onde-encontrar',
			'Estabelecimentos',
			'Estabelecimentos',
			'edit_posts',
			'edit.php?post_type=vb_estabelecimento'
		);

		add_submenu_page(
			'vb-onde-encontrar',
			'Nova entrada',
			'Nova entrada',
			'manage_options',
			'vb-oe-nova-entrada',
			array( $this, 'pagina_nova_entrada' )
		);

		add_submenu_page(
			'vb-onde-encontrar',
			'Configurações',
			'Configurações',
			'manage_options',
			'vb-oe-config',
			array( $this, 'pagina_config' )
		);
	}

	/**
	 * CSS/JS do admin.
	 *
	 * @param string $hook Hook da página.
	 */
	public function assets( $hook ) {
		if ( false === strpos( $hook, 'vb-onde-encontrar' ) && false === strpos( $hook, 'vb-oe-' ) ) {
			return;
		}

		wp_enqueue_style(
			'vb-oe-admin',
			VB_OE_URL . 'admin/css/admin.css',
			array(),
			VB_OE_VERSION
		);
	}

	/**
	 * Página do relatório.
	 */
	public function pagina_relatorio() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$busca   = isset( $_GET['busca'] ) ? sanitize_text_field( wp_unslash( $_GET['busca'] ) ) : '';
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'ativo';
		$linhas  = VB_OE_Database::listar(
			array(
				'busca'  => $busca,
				'status' => $status,
				'limit'  => 200,
			)
		);
		$settings = get_option( 'vb_oe_settings', array() );
		$alerta   = isset( $settings['dias_alerta'] ) ? (int) $settings['dias_alerta'] : 90;

		include VB_OE_PATH . 'admin/views/relatorio.php';
	}

	/**
	 * Página de nova entrada manual.
	 */
	public function pagina_nova_entrada() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$produtos = get_posts(
			array(
				'post_type'      => 'vb_produto',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		$locais = get_posts(
			array(
				'post_type'      => 'vb_estabelecimento',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		include VB_OE_PATH . 'admin/views/nova-entrada.php';
	}

	/**
	 * Página de configurações (API n8n).
	 */
	public function pagina_config() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( 'vb_oe_settings', array() );
		$api_key  = get_option( 'vb_oe_api_key', '' );
		$endpoint = rest_url( 'valle-branco/v1/sincronizar' );
		$lote     = rest_url( 'valle-branco/v1/sincronizar-lote' );

		include VB_OE_PATH . 'admin/views/configuracoes.php';
	}

	/**
	 * Salva configurações.
	 */
	public function salvar_config() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'vb_oe_salvar_config' );

		$settings = array(
			'dias_alerta' => isset( $_POST['dias_alerta'] ) ? absint( $_POST['dias_alerta'] ) : 90,
			'mapa_zoom'   => isset( $_POST['mapa_zoom'] ) ? absint( $_POST['mapa_zoom'] ) : 7,
			'mapa_lat'   => isset( $_POST['mapa_lat'] ) ? floatval( $_POST['mapa_lat'] ) : -23.0,
			'mapa_lng'   => isset( $_POST['mapa_lng'] ) ? floatval( $_POST['mapa_lng'] ) : -49.5,
		);

		update_option( 'vb_oe_settings', $settings );

		wp_safe_redirect( add_query_arg( 'atualizado', '1', admin_url( 'admin.php?page=vb-oe-config' ) ) );
		exit;
	}

	/**
	 * Nova entrada manual no relatório.
	 */
	public function nova_entrada() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'vb_oe_nova_entrada' );

		VB_OE_Database::upsert_relacao(
			array(
				'produto_id'         => isset( $_POST['produto_id'] ) ? absint( $_POST['produto_id'] ) : 0,
				'estabelecimento_id' => isset( $_POST['estabelecimento_id'] ) ? absint( $_POST['estabelecimento_id'] ) : 0,
				'origem'             => 'manual',
				'nota_fiscal'        => isset( $_POST['nota_fiscal'] ) ? sanitize_text_field( wp_unslash( $_POST['nota_fiscal'] ) ) : '',
				'status'             => 'ativo',
				'observacao'         => isset( $_POST['observacao'] ) ? sanitize_textarea_field( wp_unslash( $_POST['observacao'] ) ) : '',
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=vb-onde-encontrar&entrada=1' ) );
		exit;
	}

	/**
	 * Atualiza status (ativo / inativo / revisar).
	 */
	public function atualizar_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'vb_oe_atualizar_status' );

		global $wpdb;
		$id     = isset( $_POST['relacao_id'] ) ? absint( $_POST['relacao_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'ativo';

		if ( $id && in_array( $status, array( 'ativo', 'inativo', 'revisar' ), true ) ) {
			$wpdb->update(
				VB_OE_Database::table_name(),
				array(
					'status'           => $status,
					'data_atualizacao' => current_time( 'mysql' ),
				),
				array( 'id' => $id ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		wp_safe_redirect( admin_url( 'admin.php?page=vb-onde-encontrar' ) );
		exit;
	}

	/**
	 * Gera nova chave de API.
	 */
	public function regenerar_chave() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'vb_oe_regenerar_chave' );

		update_option( 'vb_oe_api_key', wp_generate_password( 32, false, false ) );

		wp_safe_redirect( add_query_arg( 'chave', '1', admin_url( 'admin.php?page=vb-oe-config' ) ) );
		exit;
	}
}
