<?php
/**
 * View: nova entrada manual.
 *
 * @package ValleBrancoOndeEncontrar
 * @var WP_Post[] $produtos
 * @var WP_Post[] $locais
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap vb-oe-admin">
	<h1>Nova entrada</h1>
	<p class="description">Vincule um produto a um estabelecimento manualmente (além da automação n8n).</p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vb-oe-form">
		<?php wp_nonce_field( 'vb_oe_nova_entrada' ); ?>
		<input type="hidden" name="action" value="vb_oe_nova_entrada">

		<table class="form-table">
			<tr>
				<th><label for="produto_id">Produto</label></th>
				<td>
					<select name="produto_id" id="produto_id" required>
						<option value="">Selecione...</option>
						<?php foreach ( $produtos as $p ) : ?>
							<option value="<?php echo esc_attr( (string) $p->ID ); ?>"><?php echo esc_html( $p->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="estabelecimento_id">Estabelecimento</label></th>
				<td>
					<select name="estabelecimento_id" id="estabelecimento_id" required>
						<option value="">Selecione...</option>
						<?php foreach ( $locais as $l ) : ?>
							<option value="<?php echo esc_attr( (string) $l->ID ); ?>"><?php echo esc_html( $l->post_title ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="nota_fiscal">Nota fiscal (opcional)</label></th>
				<td><input type="text" name="nota_fiscal" id="nota_fiscal" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="observacao">Observação</label></th>
				<td><textarea name="observacao" id="observacao" class="large-text" rows="3"></textarea></td>
			</tr>
		</table>

		<?php submit_button( 'Salvar entrada' ); ?>
	</form>
</div>
