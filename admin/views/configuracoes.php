<?php
/**
 * View: configurações (linguagem simples).
 *
 * @package ValleBrancoOndeEncontrar
 * @var array  $settings
 * @var string $api_key
 * @var string $endpoint
 * @var string $lote
 * @var string $webhook
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$n8n_fluxo   = 'https://n8n.v4companyamaral.com/workflow/lBNujNGwhttefPIl?projectId=ZqW5ySVXaI1Z9iy2';
$n8n_webhook = ! empty( $settings['n8n_webhook_url'] )
	? $settings['n8n_webhook_url']
	: VB_OE_Sync_N8N::DEFAULT_WEBHOOK;
$ultimo      = get_transient( 'vb_oe_ultimo_sync' );
$amostra     = get_option( 'vb_oe_ultimo_payload_amostra', null );
$proximo     = wp_next_scheduled( VB_OE_Sync_N8N::CRON_HOOK );
?>
<div class="wrap vb-oe-admin">
	<h1>Configurações</h1>
	<p class="vb-oe-lead">Aqui você ajusta o mapa e a atualização automática dos pontos de venda.</p>

	<?php if ( isset( $_GET['atualizado'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Pronto! Configurações salvas.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['chave'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Nova senha de acesso gerada.</p></div>
	<?php endif; ?>
	<?php if ( isset( $_GET['sync'] ) ) : ?>
		<?php if ( '1' === $_GET['sync'] ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					Atualização concluída:
					<?php echo esc_html( (string) ( $_GET['sucesso'] ?? 0 ) ); ?> item(ns) ok,
					<?php echo esc_html( (string) ( $_GET['erros'] ?? 0 ) ); ?> com erro.
				</p>
			</div>
		<?php else : ?>
			<div class="notice notice-error is-dismissible">
				<p>Não foi possível atualizar. Veja o detalhe abaixo — em geral o fluxo no n8n precisa estar <strong>ativo</strong>.</p>
			</div>
		<?php endif; ?>
	<?php endif; ?>
	<?php if ( isset( $_GET['geocode'] ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				Localização no mapa:
				<?php echo esc_html( (string) ( $_GET['ok'] ?? 0 ) ); ?> encontrados,
				<?php echo esc_html( (string) ( $_GET['falha'] ?? 0 ) ); ?> sem resultado,
				<?php echo esc_html( (string) ( $_GET['pendentes'] ?? 0 ) ); ?> ainda pendentes.
				<?php if ( ! empty( $_GET['pendentes'] ) ) : ?>
					Clique de novo em “Localizar no mapa” até zerar os pendentes.
				<?php endif; ?>
			</p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'vb_oe_salvar_config' ); ?>
		<input type="hidden" name="action" value="vb_oe_salvar_config">

		<div class="vb-oe-card-ajuda">
			<h2>Mapa (o que o visitante vê)</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="dias_alerta">Avisar no relatório após quantos dias?</label></th>
					<td>
						<input type="number" name="dias_alerta" id="dias_alerta" value="<?php echo esc_attr( (string) ( $settings['dias_alerta'] ?? 90 ) ); ?>" min="1" class="small-text">
					</td>
				</tr>
				<tr>
					<th><label for="mapa_lat">Centro — latitude</label></th>
					<td><input type="text" name="mapa_lat" id="mapa_lat" value="<?php echo esc_attr( (string) ( $settings['mapa_lat'] ?? -23.0 ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="mapa_lng">Centro — longitude</label></th>
					<td><input type="text" name="mapa_lng" id="mapa_lng" value="<?php echo esc_attr( (string) ( $settings['mapa_lng'] ?? -49.5 ) ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="mapa_zoom">Zoom inicial</label></th>
					<td><input type="number" name="mapa_zoom" id="mapa_zoom" value="<?php echo esc_attr( (string) ( $settings['mapa_zoom'] ?? 7 ) ); ?>" min="1" max="18" class="small-text"></td>
				</tr>
			</table>
			<?php submit_button( 'Salvar configurações do mapa' ); ?>
		</div>
	</form>

	<div class="vb-oe-card-ajuda">
		<h2>Localizar no mapa (pins)</h2>
		<p>
			Os dados do n8n vêm com endereço e cidade, mas <strong>sem latitude/longitude</strong>.
			Por isso os pins não apareciam. O plugin agora busca a posição pelo endereço (mesmo sem CEP).
		</p>
		<?php
		$pendentes_geo = VB_OE_Geocoder::contar_pendentes();
		$ultimo_geo    = get_transient( 'vb_oe_ultimo_geocode' );
		?>
		<p><strong>Ainda sem posição no mapa:</strong> <?php echo esc_html( (string) $pendentes_geo ); ?> estabelecimento(s).</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'vb_oe_geocode_agora' ); ?>
			<input type="hidden" name="action" value="vb_oe_geocode_agora">
			<?php submit_button( 'Localizar no mapa agora', 'primary', 'submit', false ); ?>
		</form>
		<p class="description">Processa cerca de 25 por vez (~30 segundos). Repita até chegar a zero. Depois disso os pins e a lista de estabelecimentos no site passam a aparecer.</p>
		<?php if ( is_array( $ultimo_geo ) ) : ?>
			<p class="description">Última rodada: <?php echo esc_html( $ultimo_geo['quando'] ?? '' ); ?> — ok <?php echo esc_html( (string) ( $ultimo_geo['ok'] ?? 0 ) ); ?>, falha <?php echo esc_html( (string) ( $ultimo_geo['falha'] ?? 0 ) ); ?>.</p>
		<?php endif; ?>
	</div>

	<div class="vb-oe-card-ajuda">
		<h2>Atualização diária (n8n)</h2>
		<p>Uma vez por dia o WordPress chama o webhook do n8n, recebe os dados e atualiza o mapa.</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'vb_oe_salvar_config' ); ?>
			<input type="hidden" name="action" value="vb_oe_salvar_config">
			<table class="form-table" role="presentation">
				<tr>
					<th><label for="n8n_webhook_url">Webhook do n8n</label></th>
					<td>
						<input type="url" name="n8n_webhook_url" id="n8n_webhook_url" value="<?php echo esc_attr( $n8n_webhook ); ?>" class="large-text">
						<p class="description">Produção: <code>https://webhook-n8n.v4companyamaral.com/webhook/...</code></p>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Salvar URL', 'secondary' ); ?>
		</form>

		<p>
			<a class="button" href="<?php echo esc_url( $n8n_fluxo ); ?>" target="_blank" rel="noopener noreferrer">Abrir fluxo no n8n</a>
		</p>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:12px 0">
			<?php wp_nonce_field( 'vb_oe_sync_agora' ); ?>
			<input type="hidden" name="action" value="vb_oe_sync_agora">
			<?php submit_button( 'Atualizar agora', 'primary', 'submit', false ); ?>
		</form>

		<?php if ( $proximo ) : ?>
			<p class="description">Próxima atualização automática: <?php echo esc_html( wp_date( 'd/m/Y H:i', $proximo ) ); ?></p>
		<?php endif; ?>

		<?php if ( is_array( $ultimo ) ) : ?>
			<h3>Última tentativa</h3>
			<ul>
				<li>Quando: <?php echo esc_html( $ultimo['quando'] ?? '—' ); ?></li>
				<li>Itens ok: <?php echo esc_html( (string) ( $ultimo['sucesso'] ?? 0 ) ); ?></li>
				<li>Erros: <?php echo esc_html( (string) ( $ultimo['erros'] ?? 0 ) ); ?></li>
				<?php if ( ! empty( $ultimo['erro'] ) ) : ?>
					<li><strong>Problema:</strong> <?php echo esc_html( $ultimo['erro'] ); ?></li>
				<?php endif; ?>
				<?php if ( ! empty( $ultimo['chaves'] ) ) : ?>
					<li>Campos recebidos: <code><?php echo esc_html( implode( ', ', $ultimo['chaves'] ) ); ?></code></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $amostra ) ) : ?>
			<h3>Amostra do que veio do n8n</h3>
			<pre class="vb-oe-json"><?php echo esc_html( wp_json_encode( $amostra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
		<?php else : ?>
			<p class="description">Ainda não há amostra. Ative o fluxo no n8n e clique em “Atualizar agora”.</p>
		<?php endif; ?>
	</div>

	<details class="vb-oe-card-ajuda vb-oe-avancado">
		<summary><strong>Opção alternativa: n8n envia para o WordPress</strong></summary>
		<div class="vb-oe-avancado-corpo">
			<p>Se preferir, o n8n pode fazer POST direto no site (em vez do WordPress buscar).</p>
			<table class="form-table" role="presentation">
				<tr>
					<th>Webhook do site</th>
					<td><code class="vb-oe-quebra"><?php echo esc_html( $webhook ); ?></code></td>
				</tr>
				<tr>
					<th>Senha</th>
					<td>
						<code class="vb-oe-chave"><?php echo esc_html( $api_key ); ?></code>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:8px">
							<?php wp_nonce_field( 'vb_oe_regenerar_chave' ); ?>
							<input type="hidden" name="action" value="vb_oe_regenerar_chave">
							<button type="submit" class="button">Gerar nova senha</button>
						</form>
					</td>
				</tr>
			</table>
			<p class="description">Header: <code>X-VB-API-Key</code></p>
		</div>
	</details>
</div>
