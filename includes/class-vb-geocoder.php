<?php
/**
 * Geocodificação de endereços (sem CEP) via OpenStreetMap Nominatim.
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Geocoder
 */
class VB_OE_Geocoder {

	const CRON_HOOK = 'vb_oe_geocode_lote';
	const POR_LOTE  = 25;

	/**
	 * Liga hooks.
	 */
	public function hooks() {
		add_action( self::CRON_HOOK, array( $this, 'processar_lote' ) );
		add_action( 'admin_post_vb_oe_geocode_agora', array( $this, 'geocode_manual' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 120, 'hourly', self::CRON_HOOK );
		}
	}

	/**
	 * Agenda processamento rápido (após sync).
	 */
	public static function agendar_agora() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK );
		} else {
			wp_schedule_single_event( time() + 5, self::CRON_HOOK );
		}
	}

	/**
	 * Botão no painel.
	 */
	public function geocode_manual() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Sem permissão.' );
		}
		check_admin_referer( 'vb_oe_geocode_agora' );

		$resultado = self::processar_lote_interno( self::POR_LOTE );
		set_transient( 'vb_oe_ultimo_geocode', $resultado, DAY_IN_SECONDS );

		// Se ainda houver pendentes, agenda o próximo lote.
		if ( ! empty( $resultado['pendentes'] ) ) {
			wp_schedule_single_event( time() + 30, self::CRON_HOOK );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'vb-oe-config',
					'geocode'  => '1',
					'ok'       => (int) $resultado['ok'],
					'falha'    => (int) $resultado['falha'],
					'pendentes'=> (int) $resultado['pendentes'],
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Cron.
	 */
	public function processar_lote() {
		$resultado = self::processar_lote_interno( self::POR_LOTE );
		set_transient( 'vb_oe_ultimo_geocode', $resultado, DAY_IN_SECONDS );
		if ( ! empty( $resultado['pendentes'] ) ) {
			wp_schedule_single_event( time() + 60, self::CRON_HOOK );
		}
	}

	/**
	 * Processa N estabelecimentos sem lat/lng.
	 *
	 * @param int $limite Quantidade.
	 * @return array
	 */
	public static function processar_lote_interno( $limite = 25 ) {
		$posts = get_posts(
			array(
				'post_type'      => 'vb_estabelecimento',
				'posts_per_page' => absint( $limite ),
				'post_status'    => 'publish',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_vb_lat',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_vb_lat',
						'value'   => '',
						'compare' => '=',
					),
				),
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$ok    = 0;
		$falha = 0;

		foreach ( $posts as $post_id ) {
			$coords = self::geocodificar_estabelecimento( $post_id );
			if ( $coords ) {
				$ok++;
			} else {
				$falha++;
				// Marca tentativa para não travar no mesmo item para sempre.
				update_post_meta( $post_id, '_vb_geo_tentativa', current_time( 'mysql' ) );
			}
			// Nominatim: no máximo 1 requisição por segundo.
			usleep( 1100000 );
		}

		$pendentes = self::contar_pendentes();

		return array(
			'ok'        => $ok,
			'falha'     => $falha,
			'processados'=> count( $posts ),
			'pendentes' => $pendentes,
			'quando'    => current_time( 'mysql' ),
		);
	}

	/**
	 * Conta estabelecimentos sem coordenadas.
	 *
	 * @return int
	 */
	public static function contar_pendentes() {
		$q = new WP_Query(
			array(
				'post_type'      => 'vb_estabelecimento',
				'posts_per_page' => 1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => '_vb_lat',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_vb_lat',
						'value'   => '',
						'compare' => '=',
					),
				),
			)
		);
		return (int) $q->found_posts;
	}

	/**
	 * Geocodifica um estabelecimento e salva lat/lng.
	 *
	 * @param int $post_id ID.
	 * @return array|false
	 */
	public static function geocodificar_estabelecimento( $post_id ) {
		$endereco = get_post_meta( $post_id, '_vb_endereco', true );
		$cidade   = get_post_meta( $post_id, '_vb_cidade', true );
		$uf       = get_post_meta( $post_id, '_vb_uf', true );

		$coords = self::buscar_coordenadas( $endereco, $cidade, $uf );
		if ( ! $coords ) {
			return false;
		}

		update_post_meta( $post_id, '_vb_lat', (string) $coords['lat'] );
		update_post_meta( $post_id, '_vb_lng', (string) $coords['lng'] );
		update_post_meta( $post_id, '_vb_geo_fonte', 'nominatim' );

		return $coords;
	}

	/**
	 * Consulta Nominatim (endereço + cidade + UF, sem CEP).
	 *
	 * @param string $endereco Endereço.
	 * @param string $cidade   Cidade.
	 * @param string $uf       UF.
	 * @return array|false
	 */
	public static function buscar_coordenadas( $endereco, $cidade, $uf ) {
		$endereco = self::expandir_endereco( trim( (string) $endereco ) );
		$cidade   = trim( (string) $cidade );
		$uf       = trim( (string) $uf );

		if ( ! $endereco && ! $cidade ) {
			return false;
		}

		// 1) Endereço completo.
		if ( $endereco ) {
			$coords = self::consultar_nominatim( implode( ', ', array_filter( array( $endereco, $cidade, $uf, 'Brasil' ) ) ) );
			if ( $coords ) {
				return $coords;
			}
		}

		// 2) Só cidade + UF (pin no centro da cidade — melhor que não aparecer).
		if ( $cidade ) {
			return self::consultar_nominatim( implode( ', ', array_filter( array( $cidade, $uf, 'Brasil' ) ) ) );
		}

		return false;
	}

	/**
	 * Expande abreviações comuns de logradouro do SAP.
	 *
	 * @param string $endereco Endereço.
	 * @return string
	 */
	private static function expandir_endereco( $endereco ) {
		$map = array(
			'/^MAL\b/i'   => 'Marechal',
			'/^CEL\b/i'   => 'Coronel',
			'/^DR\b/i'    => 'Doutor',
			'/^DRA\b/i'   => 'Doutora',
			'/^AV\b/i'    => 'Avenida',
			'/^R\b/i'     => 'Rua',
			'/^PCA\b/i'   => 'Praça',
			'/^TRAV\b/i'  => 'Travessa',
		);
		foreach ( $map as $padrao => $troca ) {
			$endereco = preg_replace( $padrao, $troca, $endereco );
		}
		return $endereco;
	}

	/**
	 * Chama a API do Nominatim.
	 *
	 * @param string $query Texto da busca.
	 * @return array|false
	 */
	private static function consultar_nominatim( $query ) {
		$url = add_query_arg(
			array(
				'q'            => $query,
				'format'       => 'json',
				'limit'        => 1,
				'countrycodes' => 'br',
			),
			'https://nominatim.openstreetmap.org/search'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => array(
					'User-Agent' => 'ValleBrancoOndeEncontrar/1.4 (WordPress; contato vallebranco)',
					'Accept'     => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$dados = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $dados[0]['lat'] ) || empty( $dados[0]['lon'] ) ) {
			return false;
		}

		return array(
			'lat' => (float) $dados[0]['lat'],
			'lng' => (float) $dados[0]['lon'],
		);
	}
}
