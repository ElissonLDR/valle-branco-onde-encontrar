<?php
/**
 * View: relatório de entradas (produto × local).
 *
 * @package ValleBrancoOndeEncontrar
 * @var array  $linhas
 * @var string $busca
 * @var string $status
 * @var int    $alerta
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap vb-oe-admin">
	<h1>Onde Encontrar — Relatório</h1>
	<p class="description">
		Controle de produtos nos estabelecimentos: data de entrada, tempo no local e origem (n8n ou manual).
		Itens com mais de <?php echo esc_html( (string) $alerta ); ?> dias ficam em alerta para revisão.
	</p>

	<?php if ( isset( $_GET['entrada'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Entrada registrada.</p></div>
	<?php endif; ?>

	<form method="get" class="vb-oe-filtros-admin">
		<input type="hidden" name="page" value="vb-onde-encontrar">
		<input type="search" name="busca" value="<?php echo esc_attr( $busca ); ?>" placeholder="Buscar produto, loja ou NF">
		<select name="status">
			<option value="ativo" <?php selected( $status, 'ativo' ); ?>>Ativos</option>
			<option value="revisar" <?php selected( $status, 'revisar' ); ?>>Revisar</option>
			<option value="inativo" <?php selected( $status, 'inativo' ); ?>>Inativos</option>
			<option value="" <?php selected( $status, '' ); ?>>Todos</option>
		</select>
		<button type="submit" class="button">Filtrar</button>
		<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=vb-oe-nova-entrada' ) ); ?>">Nova entrada</a>
	</form>

	<table class="widefat striped vb-oe-tabela">
		<thead>
			<tr>
				<th>Produto</th>
				<th>Estabelecimento</th>
				<th>Entrada</th>
				<th>Dias no local</th>
				<th>Última atualização</th>
				<th>Origem</th>
				<th>NF</th>
				<th>Status</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $linhas ) ) : ?>
				<tr><td colspan="9">Nenhuma entrada encontrada.</td></tr>
			<?php else : ?>
				<?php foreach ( $linhas as $linha ) : ?>
					<?php
					$dias   = VB_OE_Database::dias_no_local( $linha->data_entrada );
					$classe = $dias >= $alerta ? 'vb-oe-alerta' : '';
					?>
					<tr class="<?php echo esc_attr( $classe ); ?>">
						<td><?php echo esc_html( $linha->produto_nome ); ?></td>
						<td><?php echo esc_html( $linha->estabelecimento_nome ); ?></td>
						<td><?php echo esc_html( mysql2date( 'd/m/Y', $linha->data_entrada ) ); ?></td>
						<td><strong><?php echo esc_html( (string) $dias ); ?></strong></td>
						<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $linha->data_atualizacao ) ); ?></td>
						<td><?php echo esc_html( $linha->origem ); ?></td>
						<td><?php echo esc_html( $linha->nota_fiscal ); ?></td>
						<td><?php echo esc_html( $linha->status ); ?></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="vb-oe-inline">
								<?php wp_nonce_field( 'vb_oe_atualizar_status' ); ?>
								<input type="hidden" name="action" value="vb_oe_atualizar_status">
								<input type="hidden" name="relacao_id" value="<?php echo esc_attr( (string) $linha->id ); ?>">
								<select name="status">
									<option value="ativo" <?php selected( $linha->status, 'ativo' ); ?>>Ativo</option>
									<option value="revisar" <?php selected( $linha->status, 'revisar' ); ?>>Revisar</option>
									<option value="inativo" <?php selected( $linha->status, 'inativo' ); ?>>Inativo</option>
								</select>
								<button type="submit" class="button button-small">Salvar</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>
</div>
