# Contexto do projeto

## Preview (Lovable)

O repositório do site no Lovable é um **preview** visual e de UX.
Não é a versão final de produção.

## Produção (WordPress)

O site oficial será em **WordPress**.
Funcionalidades específicas serão plugins, começando por **Onde encontrar**.

## Onde encontrar

Prioridades: desempenho, praticidade e organização.

Baseado no preview Lovable (mapa, filtros, estabelecimentos × produtos), com:

1. **Automação n8n + SAP B1** — lê notas/pedidos, pega produto e cliente (mercado etc.) e atualiza o mapa diariamente.
2. **Painel** — relatório de entradas, datas e tempo que o produto está no local.

### Workflow n8n

- Fluxo: https://n8n.v4companyamaral.com/workflow/lBNujNGwhttefPIl?projectId=ZqW5ySVXaI1Z9iy2
- Webhook (produção): https://webhook-n8n.v4companyamaral.com/webhook/8f02e2f2-0a49-4daf-9dfd-b8f55e7788ff

**Atualização diária**

1. O WordPress chama o webhook do n8n (cron diário ou botão “Atualizar agora”).
2. O n8n devolve JSON com produto + loja (SAP).
3. O plugin importa e atualiza mapa/relatório.

O fluxo precisa estar **ativo** no n8n para a URL de produção responder.

### Tabelas SAP B1

| Tabela | Conteúdo | Uso no plugin |
|--------|----------|---------------|
| OINV | Notas fiscais | DocNum, DocDate, CardCode |
| ORDR | Pedidos | Cabeçalho do pedido |
| RDR1 | Itens do pedido | ItemCode por pedido |
| OITM | Produtos | ItemCode (SKU), ItemName |
| OITB | Grupo de itens | Categoria |
| OCRD | Clientes | Estabelecimento no mapa (CardCode) |
| OCPR | Contatos | Observação |
| OSLP | Vendedores | Observação |

**Sugestão de join no n8n:** `OINV` (ou `ORDR`+`RDR1`) → `OCRD` (cliente) + `OITM` (+ `OITB`) → HTTP POST no WordPress.

## Onde está o código

| O quê | Onde |
|-------|------|
| Plugin no XAMPP | `C:\xampp\htdocs\valle-branco\wp-content\plugins\valle-branco-onde-encontrar` |
| GitHub | https://github.com/ElissonLDR/valle-branco-onde-encontrar |
| Preview Lovable | repositório `site-valle-branco` |
| n8n | link acima |
