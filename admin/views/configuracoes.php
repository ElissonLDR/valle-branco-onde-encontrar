<?php
/**
 * View: configurações e integração n8n.
 *
 * @package ValleBrancoOndeEncontrar
 * @var array  $settings
 * @var string $api_key
 * @var string $endpoint
 * @var string $lote
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap vb-oe-admin">
	<h1>Configurações — Onde Encontrar</h1>

	<?php if ( isset( $_GET['atualizado'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Configurações salvas.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['chave'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nova chave de API gerada.</p></div>
	<?php endif; ?>

	<h2>Integração n8n</h2>
	<p>O fluxo n8n lê as notas fiscais e envia produto + local para estes endpoints (atualização diária).</p>

	<table class="form-table">
		<tr>
			<th>Endpoint (1 item)</th>
			<td><code><?php echo esc_html( $endpoint ); ?></code></td>
		</tr>
		<tr>
			<th>Endpoint (lote)</th>
			<td><code><?php echo esc_html( $lote ); ?></code></td>
		</tr>
		<tr>
			<th>Chave de API</th>
			<td>
				<code class="vb-oe-chave"><?php echo esc_html( $api_key ); ?></code>
				<p class="description">Envie no header <code>X-VB-API-Key</code>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
					<?php wp_nonce_field( 'vb_oe_regenerar_chave' ); ?>
					<input type="hidden" name="action" value="vb_oe_regenerar_chave">
					<button type="submit" class="button" onclick="return confirm('Gerar nova chave? A antiga deixa de funcionar.');">Regenerar chave</button>
				</form>
			</td>
		</tr>
	</table>

	<h3>Exemplo de JSON (1 item)</h3>
	<pre class="vb-oe-json">{
  "nota_fiscal": "123456",
  "produto": {
    "nome": "Arroz Valle Branco Extra 5kg",
    "sku": "vb-arroz-extra",
    "marca": "Valle Branco",
    "categoria": "Arroz"
  },
  "estabelecimento": {
    "nome": "Mercado Boa Vista",
    "tipo": "mercado",
    "endereco": "Rua Piauí, 320",
    "cidade": "Londrina",
    "uf": "PR",
    "lat": -23.3045,
    "lng": -51.1696
  }
}</pre>

	<hr>

	<h2>Mapa e alertas</h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'vb_oe_salvar_config' ); ?>
		<input type="hidden" name="action" value="vb_oe_salvar_config">

		<table class="form-table">
			<tr>
				<th><label for="dias_alerta">Dias para alerta</label></th>
				<td>
					<input type="number" name="dias_alerta" id="dias_alerta" value="<?php echo esc_attr( (string) ( $settings['dias_alerta'] ?? 90 ) ); ?>" min="1">
					<p class="description">No relatório, destaca produtos há mais tempo que isso no local.</p>
				</td>
			</tr>
			<tr>
				<th><label for="mapa_lat">Centro do mapa (lat)</label></th>
				<td><input type="text" name="mapa_lat" id="mapa_lat" value="<?php echo esc_attr( (string) ( $settings['mapa_lat'] ?? -23.0 ) ); ?>"></td>
			</tr>
			<tr>
				<th><label for="mapa_lng">Centro do mapa (lng)</label></th>
				<td><input type="text" name="mapa_lng" id="mapa_lng" value="<?php echo esc_attr( (string) ( $settings['mapa_lng'] ?? -49.5 ) ); ?>"></td>
			</tr>
			<tr>
				<th><label for="mapa_zoom">Zoom inicial</label></th>
				<td><input type="number" name="mapa_zoom" id="mapa_zoom" value="<?php echo esc_attr( (string) ( $settings['mapa_zoom'] ?? 7 ) ); ?>" min="1" max="18"></td>
			</tr>
		</table>

		<?php submit_button( 'Salvar configurações' ); ?>
	</form>

	<hr>
	<h2>Shortcode</h2>
	<p>Use na página do site: <code>[vb_onde_encontrar]</code> ou <code>[vb_onde_encontrar altura="520"]</code></p>
</div>
