<?php
/**
 * Metadados dos CPTs (campos extras).
 *
 * @package ValleBrancoOndeEncontrar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe VB_OE_Meta
 */
class VB_OE_Meta {

	/**
	 * Liga os hooks.
	 */
	public function hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_boxes' ) );
		add_action( 'save_post_vb_produto', array( $this, 'save_produto' ) );
		add_action( 'save_post_vb_estabelecimento', array( $this, 'save_estabelecimento' ) );
	}

	/**
	 * Caixas de meta no editor.
	 */
	public function add_boxes() {
		add_meta_box(
			'vb_oe_produto',
			'Dados do produto',
			array( $this, 'render_produto' ),
			'vb_produto',
			'normal',
			'high'
		);

		add_meta_box(
			'vb_oe_estabelecimento',
			'Dados do estabelecimento',
			array( $this, 'render_estabelecimento' ),
			'vb_estabelecimento',
			'normal',
			'high'
		);
	}

	/**
	 * Formulário do produto.
	 *
	 * @param WP_Post $post Post atual.
	 */
	public function render_produto( $post ) {
		wp_nonce_field( 'vb_oe_save_produto', 'vb_oe_produto_nonce' );

		$sku    = get_post_meta( $post->ID, '_vb_sku', true );
		$marca  = get_post_meta( $post->ID, '_vb_marca', true );
		$cat    = get_post_meta( $post->ID, '_vb_categoria', true );
		$pesos  = get_post_meta( $post->ID, '_vb_pesos', true );
		?>
		<p>
			<label for="vb_sku"><strong>SKU / código</strong></label><br>
			<input type="text" class="widefat" id="vb_sku" name="vb_sku" value="<?php echo esc_attr( $sku ); ?>">
		</p>
		<p>
			<label for="vb_marca"><strong>Marca</strong></label><br>
			<input type="text" class="widefat" id="vb_marca" name="vb_marca" value="<?php echo esc_attr( $marca ); ?>" placeholder="Valle Branco, Castelão, Aene, Vita...">
		</p>
		<p>
			<label for="vb_categoria"><strong>Categoria</strong></label><br>
			<input type="text" class="widefat" id="vb_categoria" name="vb_categoria" value="<?php echo esc_attr( $cat ); ?>" placeholder="Arroz, Feijão, Conservas...">
		</p>
		<p>
			<label for="vb_pesos"><strong>Pesos / embalagens</strong></label><br>
			<input type="text" class="widefat" id="vb_pesos" name="vb_pesos" value="<?php echo esc_attr( $pesos ); ?>" placeholder="1kg, 5kg">
		</p>
		<?php
	}

	/**
	 * Formulário do estabelecimento.
	 *
	 * @param WP_Post $post Post atual.
	 */
	public function render_estabelecimento( $post ) {
		wp_nonce_field( 'vb_oe_save_estabelecimento', 'vb_oe_estab_nonce' );

		$tipo     = get_post_meta( $post->ID, '_vb_tipo', true );
		$endereco = get_post_meta( $post->ID, '_vb_endereco', true );
		$cidade   = get_post_meta( $post->ID, '_vb_cidade', true );
		$uf       = get_post_meta( $post->ID, '_vb_uf', true );
		$cep      = get_post_meta( $post->ID, '_vb_cep', true );
		$lat      = get_post_meta( $post->ID, '_vb_lat', true );
		$lng      = get_post_meta( $post->ID, '_vb_lng', true );
		$tipos    = self::tipos_estabelecimento();
		?>
		<p>
			<label for="vb_tipo"><strong>Tipo</strong></label><br>
			<select id="vb_tipo" name="vb_tipo" class="widefat">
				<?php foreach ( $tipos as $valor => $rotulo ) : ?>
					<option value="<?php echo esc_attr( $valor ); ?>" <?php selected( $tipo, $valor ); ?>>
						<?php echo esc_html( $rotulo ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="vb_endereco"><strong>Endereço</strong></label><br>
			<input type="text" class="widefat" id="vb_endereco" name="vb_endereco" value="<?php echo esc_attr( $endereco ); ?>">
		</p>
		<p>
			<label for="vb_cidade"><strong>Cidade</strong></label><br>
			<input type="text" class="widefat" id="vb_cidade" name="vb_cidade" value="<?php echo esc_attr( $cidade ); ?>">
		</p>
		<p>
			<label for="vb_uf"><strong>UF</strong></label><br>
			<input type="text" id="vb_uf" name="vb_uf" value="<?php echo esc_attr( $uf ); ?>" maxlength="2" style="width:60px">
		</p>
		<p>
			<label for="vb_cep"><strong>CEP</strong></label><br>
			<input type="text" id="vb_cep" name="vb_cep" value="<?php echo esc_attr( $cep ); ?>" style="width:120px">
		</p>
		<p>
			<label for="vb_lat"><strong>Latitude</strong></label><br>
			<input type="text" id="vb_lat" name="vb_lat" value="<?php echo esc_attr( $lat ); ?>" placeholder="-23.3045">
		</p>
		<p>
			<label for="vb_lng"><strong>Longitude</strong></label><br>
			<input type="text" id="vb_lng" name="vb_lng" value="<?php echo esc_attr( $lng ); ?>" placeholder="-51.1696">
		</p>
		<p class="description">Latitude e longitude são obrigatórias para aparecer no mapa.</p>
		<?php
	}

	/**
	 * Tipos de estabelecimento.
	 *
	 * @return array
	 */
	public static function tipos_estabelecimento() {
		return array(
			'supermercado' => 'Supermercado',
			'mercado'      => 'Mercado',
			'atacado'      => 'Atacado',
			'emporio'      => 'Empório',
			'outro'        => 'Outro',
		);
	}

	/**
	 * Salva meta do produto.
	 *
	 * @param int $post_id ID do post.
	 */
	public function save_produto( $post_id ) {
		if ( ! isset( $_POST['vb_oe_produto_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vb_oe_produto_nonce'] ) ), 'vb_oe_save_produto' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$campos = array( 'vb_sku' => '_vb_sku', 'vb_marca' => '_vb_marca', 'vb_categoria' => '_vb_categoria', 'vb_pesos' => '_vb_pesos' );
		foreach ( $campos as $campo => $meta ) {
			if ( isset( $_POST[ $campo ] ) ) {
				update_post_meta( $post_id, $meta, sanitize_text_field( wp_unslash( $_POST[ $campo ] ) ) );
			}
		}
	}

	/**
	 * Salva meta do estabelecimento.
	 *
	 * @param int $post_id ID do post.
	 */
	public function save_estabelecimento( $post_id ) {
		if ( ! isset( $_POST['vb_oe_estab_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vb_oe_estab_nonce'] ) ), 'vb_oe_save_estabelecimento' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$map = array(
			'vb_tipo'     => '_vb_tipo',
			'vb_endereco' => '_vb_endereco',
			'vb_cidade'   => '_vb_cidade',
			'vb_uf'       => '_vb_uf',
			'vb_cep'      => '_vb_cep',
			'vb_lat'      => '_vb_lat',
			'vb_lng'      => '_vb_lng',
		);

		foreach ( $map as $campo => $meta ) {
			if ( isset( $_POST[ $campo ] ) ) {
				$valor = sanitize_text_field( wp_unslash( $_POST[ $campo ] ) );
				if ( in_array( $meta, array( '_vb_lat', '_vb_lng' ), true ) ) {
					$valor = (string) floatval( str_replace( ',', '.', $valor ) );
				}
				if ( '_vb_uf' === $meta ) {
					$valor = strtoupper( substr( $valor, 0, 2 ) );
				}
				update_post_meta( $post_id, $meta, $valor );
			}
		}
	}
}
