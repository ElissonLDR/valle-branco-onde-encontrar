# Valle Branco — Onde Encontrar

Plugin WordPress para o mapa de pontos de venda da Valle Branco.

## Contexto

- O site no **Lovable** foi um **preview**.
- O site oficial será em **WordPress**.
- Este plugin implementa **Onde encontrar**, com foco em desempenho, praticidade e organização.
- A automação vem do **n8n**: lê notas fiscais, identifica produto e estabelecimento, e atualiza o mapa (fluxo diário).
- No painel há **relatório de entradas**, com datas e tempo que o produto está no local.

## Instalação local (XAMPP)

Pasta:

`C:\xampp\htdocs\valle-branco\wp-content\plugins\valle-branco-onde-encontrar`

1. Ative em **Plugins** no WordPress.
2. Menu **Onde Encontrar** → Configurações (chave da API).
3. Em uma página: `[vb_onde_encontrar]`

## Estrutura

```
valle-branco-onde-encontrar/
├── valle-branco-onde-encontrar.php   # arquivo principal
├── uninstall.php
├── includes/                         # classes PHP
├── admin/                            # painel (CSS + views)
└── public/                           # mapa (CSS + JS)
```

## API n8n

| Método | Rota | Uso |
|--------|------|-----|
| POST | `/wp-json/valle-branco/v1/sincronizar` | 1 item |
| POST | `/wp-json/valle-branco/v1/sincronizar-lote` | vários itens |
| GET | `/wp-json/valle-branco/v1/locais` | mapa (público) |

Header: `X-VB-API-Key: sua-chave`

## Subir no site publicado

1. Compacte a pasta do plugin em `.zip`, ou clone este repositório.
2. Envie para `wp-content/plugins/` no servidor.
3. Ative e configure a chave de API do n8n apontando para a URL de produção.
