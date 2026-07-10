<?php
/**
 * Front-end: shortcodes separados + assets.
 *
 * Shortcodes:
 * - [vb_oe_mapa]     só o mapa
 * - [vb_oe_busca]    campo de busca (atualiza o mapa)
 * - [vb_oe_filtro]   filtros (cidade, localização)
 * - [vb_oe_lista]    lista de lojas (opcional)
 * - [vb_onde_encontrar] tudo junto (atalho)
 *
 * Use o mesmo grupo="padrao" nos shortcodes da mesma página
 * para eles conversarem entre si.
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
	 * Se os scripts já foram enfileirados nesta requisição.
	 *
	 * @var bool
	 */
	private static $assets_prontos = false;

	/**
	 * Liga os hooks.
	 */
	public function hooks() {
		add_shortcode( 'vb_oe_mapa', array( $this, 'sc_mapa' ) );
		add_shortcode( 'vb_oe_busca', array( $this, 'sc_busca' ) );
		add_shortcode( 'vb_oe_filtro', array( $this, 'sc_filtro' ) );
		add_shortcode( 'vb_oe_lista', array( $this, 'sc_lista' ) );
		add_shortcode( 'vb_onde_encontrar', array( $this, 'sc_completo' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Registra CSS/JS (carrega só quando algum shortcode/widget pedir).
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
	 * Enfileira assets e passa dados para o JavaScript.
	 */
	public function enqueue_runtime() {
		if ( self::$assets_prontos ) {
			return;
		}
		self::$assets_prontos = true;

		wp_enqueue_style( 'vb-oe-mapa' );
		wp_enqueue_script( 'vb-oe-mapa' );

		$settings = get_option( 'vb_oe_settings', array() );

		wp_localize_script(
			'vb-oe-mapa',
			'vbOeMapa',
			array(
				'apiUrl'    => esc_url_raw( rest_url( 'valle-branco/v1/locais' ) ),
				'pinUrl'    => esc_url_raw( VB_OE_URL . 'public/images/pin-valle-branco.png' ),
				'mapaLat'  => isset( $settings['mapa_lat'] ) ? (float) $settings['mapa_lat'] : -23.0,
				'mapaLng'  => isset( $settings['mapa_lng'] ) ? (float) $settings['mapa_lng'] : -49.5,
				'mapaZoom' => isset( $settings['mapa_zoom'] ) ? (int) $settings['mapa_zoom'] : 7,
				'i18n'      => array(
					'carregando' => 'Carregando pontos de venda...',
					'nenhum'     => 'Nenhum estabelecimento encontrado.',
					'abrirMaps'  => 'Abrir no Google Maps',
					'produtos'   => 'Produtos disponíveis',
					'buscar'     => 'Buscar produto, cidade ou loja',
					'todas'      => 'Todas as cidades',
					'usarLocal'  => 'Usar minha localização',
				),
			)
		);
	}

	/**
	 * Normaliza o nome do grupo (liga as peças na mesma página).
	 *
	 * @param array  $atts Atributos.
	 * @param string $tag  Tag do shortcode.
	 * @return array
	 */
	private function parse_grupo( $atts, $tag ) {
		$atts = shortcode_atts(
			array(
				'grupo'  => 'padrao',
				'altura' => '480',
			),
			$atts,
			$tag
		);
		$atts['grupo'] = sanitize_key( $atts['grupo'] );
		if ( ! $atts['grupo'] ) {
			$atts['grupo'] = 'padrao';
		}
		return $atts;
	}

	/**
	 * Só o mapa.
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function sc_mapa( $atts ) {
		$atts = $this->parse_grupo( $atts, 'vb_oe_mapa' );
		$this->enqueue_runtime();
		$altura = absint( $atts['altura'] );

		return sprintf(
			'<div class="vb-oe-mapa" data-vb-oe-mapa data-vb-grupo="%1$s" style="height:%2$dpx;" role="region" aria-label="Mapa de pontos de venda"></div>',
			esc_attr( $atts['grupo'] ),
			$altura
		);
	}

	/**
	 * Campo de busca (texto).
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function sc_busca( $atts ) {
		$atts = $this->parse_grupo( $atts, 'vb_oe_busca' );
		$this->enqueue_runtime();

		return sprintf(
			'<div class="vb-oe-busca-wrap" data-vb-oe-busca data-vb-grupo="%1$s">
				<label class="screen-reader-text" for="vb-oe-busca-%1$s">Buscar</label>
				<input type="search" id="vb-oe-busca-%1$s" class="vb-oe-busca" placeholder="Buscar produto, cidade ou loja" autocomplete="off">
			</div>',
			esc_attr( $atts['grupo'] )
		);
	}

	/**
	 * Filtros (cidade + minha localização).
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function sc_filtro( $atts ) {
		$atts = $this->parse_grupo( $atts, 'vb_oe_filtro' );
		$this->enqueue_runtime();

		return sprintf(
			'<div class="vb-oe-filtro-wrap" data-vb-oe-filtro data-vb-grupo="%1$s">
				<label class="screen-reader-text" for="vb-oe-cidade-%1$s">Cidade</label>
				<select id="vb-oe-cidade-%1$s" class="vb-oe-cidade" aria-label="Cidade">
					<option value="">Todas as cidades</option>
				</select>
				<button type="button" class="vb-oe-geo">Usar minha localização</button>
			</div>',
			esc_attr( $atts['grupo'] )
		);
	}

	/**
	 * Lista de estabelecimentos.
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function sc_lista( $atts ) {
		$atts = $this->parse_grupo( $atts, 'vb_oe_lista' );
		$this->enqueue_runtime();

		return sprintf(
			'<div class="vb-oe-lista" data-vb-oe-lista data-vb-grupo="%1$s" aria-live="polite">Carregando pontos de venda...</div>',
			esc_attr( $atts['grupo'] )
		);
	}

	/**
	 * Tudo junto (atalho).
	 *
	 * @param array $atts Atributos.
	 * @return string
	 */
	public function sc_completo( $atts ) {
		$atts = $this->parse_grupo( $atts, 'vb_onde_encontrar' );
		$g    = $atts['grupo'];

		ob_start();
		?>
		<div class="vb-oe-wrap" data-vb-oe-completo data-vb-grupo="<?php echo esc_attr( $g ); ?>">
			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shortcodes já escapam.
			echo $this->sc_busca( array( 'grupo' => $g ) );
			echo $this->sc_filtro( array( 'grupo' => $g ) );
			echo $this->sc_mapa( array( 'grupo' => $g, 'altura' => $atts['altura'] ) );
			echo $this->sc_lista( array( 'grupo' => $g ) );
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
