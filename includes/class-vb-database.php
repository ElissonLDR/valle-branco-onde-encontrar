<?php
/**
 * Banco de dados: tabela de relação produto × estabelecimento.
 *
 * Guarda data de entrada, última atualização e origem (n8n / manual).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Database
 */
class VB_OE_Database {

	/**
	 * Nome da tabela (sem prefixo).
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'vb_produto_local';
	}

	/**
	 * Cria ou atualiza a tabela.
	 */
	public static function create_tables() {
		global $wpdb;

		$tabela      = self::table_name();
		$charset     = $wpdb->get_charset_collate();
		$requerido   = 'dbDelta';

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$tabela} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			produto_id bigint(20) unsigned NOT NULL,
			estabelecimento_id bigint(20) unsigned NOT NULL,
			data_entrada datetime NOT NULL,
			data_atualizacao datetime NOT NULL,
			origem varchar(20) NOT NULL DEFAULT 'manual',
			nota_fiscal varchar(100) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'ativo',
			observacao text DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY produto_local (produto_id, estabelecimento_id),
			KEY estabelecimento_id (estabelecimento_id),
			KEY status (status),
			KEY data_entrada (data_entrada)
		) {$charset};";

		dbDelta( $sql );

		update_option( 'vb_oe_db_version', VB_OE_DB_VERSION );
	}

	/**
	 * Insere ou atualiza uma relação (usado pelo n8n e pelo painel).
	 *
	 * @param array $dados Dados da relação.
	 * @return int|false ID da linha ou false.
	 */
	public static function upsert_relacao( $dados ) {
		global $wpdb;

		$tabela = self::table_name();
		$agora  = current_time( 'mysql' );

		$produto_id         = absint( $dados['produto_id'] );
		$estabelecimento_id = absint( $dados['estabelecimento_id'] );

		if ( ! $produto_id || ! $estabelecimento_id ) {
			return false;
		}

		$existente = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, data_entrada FROM {$tabela} WHERE produto_id = %d AND estabelecimento_id = %d",
				$produto_id,
				$estabelecimento_id
			)
		);

		$linha = array(
			'produto_id'         => $produto_id,
			'estabelecimento_id' => $estabelecimento_id,
			'data_atualizacao'   => $agora,
			'origem'             => sanitize_key( $dados['origem'] ?? 'manual' ),
			'nota_fiscal'        => isset( $dados['nota_fiscal'] ) ? sanitize_text_field( $dados['nota_fiscal'] ) : null,
			'status'             => sanitize_key( $dados['status'] ?? 'ativo' ),
			'observacao'         => isset( $dados['observacao'] ) ? sanitize_textarea_field( $dados['observacao'] ) : null,
		);

		$formatos = array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

		if ( $existente ) {
			// Mantém a data de entrada original.
			$wpdb->update(
				$tabela,
				$linha,
				array( 'id' => (int) $existente->id ),
				$formatos,
				array( '%d' )
			);
			return (int) $existente->id;
		}

		$linha['data_entrada'] = ! empty( $dados['data_entrada'] )
			? sanitize_text_field( $dados['data_entrada'] )
			: $agora;

		$wpdb->insert(
			$tabela,
			$linha,
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Lista relações com filtros (relatório do painel).
	 *
	 * @param array $args Filtros.
	 * @return array
	 */
	public static function listar( $args = array() ) {
		global $wpdb;

		$tabela = self::table_name();
		$defaults = array(
			'status'             => '',
			'produto_id'         => 0,
			'estabelecimento_id' => 0,
			'busca'              => '',
			'orderby'            => 'data_atualizacao',
			'order'              => 'DESC',
			'limit'              => 100,
			'offset'             => 0,
		);
		$args = wp_parse_args( $args, $defaults );

		$where  = array( '1=1' );
		$params = array();

		if ( $args['status'] ) {
			$where[]  = 'r.status = %s';
			$params[] = $args['status'];
		}
		if ( $args['produto_id'] ) {
			$where[]  = 'r.produto_id = %d';
			$params[] = absint( $args['produto_id'] );
		}
		if ( $args['estabelecimento_id'] ) {
			$where[]  = 'r.estabelecimento_id = %d';
			$params[] = absint( $args['estabelecimento_id'] );
		}

		$orderby_permitidos = array( 'data_entrada', 'data_atualizacao', 'id' );
		$orderby = in_array( $args['orderby'], $orderby_permitidos, true ) ? $args['orderby'] : 'data_atualizacao';
		$order   = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

		$sql = "SELECT r.*,
			p.post_title AS produto_nome,
			e.post_title AS estabelecimento_nome
			FROM {$tabela} r
			LEFT JOIN {$wpdb->posts} p ON p.ID = r.produto_id
			LEFT JOIN {$wpdb->posts} e ON e.ID = r.estabelecimento_id
			WHERE " . implode( ' AND ', $where );

		if ( $args['busca'] ) {
			$sql     .= ' AND (p.post_title LIKE %s OR e.post_title LIKE %s OR r.nota_fiscal LIKE %s)';
			$like     = '%' . $wpdb->esc_like( $args['busca'] ) . '%';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql .= " ORDER BY r.{$orderby} {$order}";
		$sql .= ' LIMIT %d OFFSET %d';
		$params[] = absint( $args['limit'] );
		$params[] = absint( $args['offset'] );

		if ( ! empty( $params ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $wpdb->prepare( $sql, $params );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $sql );
	}

	/**
	 * Conta dias desde a data de entrada.
	 *
	 * @param string $data_entrada Data MySQL.
	 * @return int
	 */
	public static function dias_no_local( $data_entrada ) {
		$inicio = strtotime( $data_entrada );
		if ( ! $inicio ) {
			return 0;
		}
		$agora = current_time( 'timestamp' );
		return (int) floor( ( $agora - $inicio ) / DAY_IN_SECONDS );
	}

	/**
	 * Produtos ativos de um estabelecimento (para o mapa).
	 *
	 * @param int $estabelecimento_id ID do estabelecimento.
	 * @return array
	 */
	public static function produtos_do_estabelecimento( $estabelecimento_id ) {
		global $wpdb;

		$tabela = self::table_name();

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.produto_id, r.data_entrada, r.data_atualizacao, p.post_title AS nome
				FROM {$tabela} r
				INNER JOIN {$wpdb->posts} p ON p.ID = r.produto_id AND p.post_status = 'publish'
				WHERE r.estabelecimento_id = %d AND r.status = 'ativo'
				ORDER BY p.post_title ASC",
				absint( $estabelecimento_id )
			)
		);
	}
}
