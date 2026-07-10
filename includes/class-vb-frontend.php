<?php
/**
 * Front-end: shortcode do mapa e assets.
 *
 * Shortcode: [vb_onde_encontrar]
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Frontend
 */
class VB_OE_Frontend {

	/**
	 * Liga os hooks.
	 */
	public function hooks() {
		add_shortcode( 'vb_onde_encontrar', array( $this, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registra CSS/JS (só carrega quando o shortcode é usado).
	 */
	public function register_assets() {
		wp_register_style(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
			array(),
			'1.9.4'
		);

		wp_register_style(
			'vb-oe-mapa',
			VB_OE_URL . 'public/css/mapa.css',
			array( 'leaflet' ),
			VB_OE_VERSION
		);

		wp_register_script(
			'leaflet',
			'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
			array(),
			'1.9.4',
			true
		);

		wp_register_script(
			'vb-oe-mapa',
			VB_OE_URL . 'public/js/mapa.js',
			array( 'leaflet' ),
			VB_OE_VERSION,
			true
		);
	}

	/**
	 * Renderiza o mapa.
	 *
	 * @param array $atts Atributos do shortcode.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'altura' => '480',
			),
			$atts,
			'vb_onde_encontrar'
		);

		wp_enqueue_style( 'vb-oe-mapa' );
		wp_enqueue_script( 'vb-oe-mapa' );

		$settings = get_option( 'vb_oe_settings', array() );

		wp_localize_script(
			'vb-oe-mapa',
			'vbOeMapa',
			array(
				'apiUrl'   => esc_url_raw( rest_url( 'valle-branco/v1/locais' ) ),
				'mapaLat' => isset( $settings['mapa_lat'] ) ? (float) $settings['mapa_lat'] : -23.0,
				'mapaLng' => isset( $settings['mapa_lng'] ) ? (float) $settings['mapa_lng'] : -49.5,
				'mapaZoom'=> isset( $settings['mapa_zoom'] ) ? (int) $settings['mapa_zoom'] : 7,
				'i18n'     => array(
					'carregando'   => 'Carregando pontos de venda...',
					'nenhum'       => 'Nenhum estabelecimento encontrado.',
					'abrirMaps'    => 'Abrir no Google Maps',
					'produtos'     => 'Produtos',
					'buscar'       => 'Buscar produto, cidade ou loja',
					'cidade'       => 'Cidade',
					'todas'        => 'Todas as cidades',
					'usarLocal'    => 'Usar minha localização',
				),
			)
		);

		$altura = absint( $atts['altura'] );

		ob_start();
		?>
		<div class="vb-oe-wrap" data-vb-oe>
			<div class="vb-oe-filtros">
				<input type="search" class="vb-oe-busca" placeholder="<?php echo esc_attr( 'Buscar produto, cidade ou loja' ); ?>" aria-label="Buscar">
				<select class="vb-oe-cidade" aria-label="Cidade">
					<option value="">Todas as cidades</option>
				</select>
				<button type="button" class="vb-oe-geo">Usar minha localização</button>
			</div>
			<div class="vb-oe-mapa" style="height:<?php echo esc_attr( $altura ); ?>px;" role="region" aria-label="Mapa de pontos de venda"></div>
			<div class="vb-oe-lista" aria-live="polite"></div>
		</div>
		<?php
		return ob_get_clean();
	}
}
