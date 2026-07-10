/**
 * Onde Encontrar — mapa, busca, filtro, produtos, lista
 */
(function () {
	'use strict';

	if (!window.vbOeMapa || !window.L) {
		return;
	}

	var cfg = window.vbOeMapa;
	var POR_PAGINA = 12;
	var grupos = {};

	var pinIcon = L.icon({
		iconUrl: cfg.pinUrl || '',
		iconSize: [44, 56],
		iconAnchor: [22, 56],
		popupAnchor: [0, -48],
	});

	function getGrupo(id) {
		id = id || 'padrao';
		if (!grupos[id]) {
			grupos[id] = {
				id: id,
				mapaEl: null,
				buscaWrap: null,
				buscaEl: null,
				buscaBtn: null,
				cidadeEl: null,
				geoBtn: null,
				produtosEl: null,
				listaEl: null,
				map: null,
				markers: null,
				markerById: {},
				locais: null,
				origem: null,
				queryAtiva: '',
				produtoId: null,
				pagina: 1,
				carregando: false,
				origem: null,
				_userMarker: null,
				_boundBusca: false,
				_boundBuscaBtn: false,
				_boundCidade: false,
				_boundGeo: false,
				_closeBound: false,
			};
		}
		return grupos[id];
	}

	function escapeHtml(str) {
		return String(str || '')
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function nomeCurtoProduto(nome) {
		return String(nome || '')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function distanciaKm(aLat, aLng, bLat, bLng) {
		var R = 6371;
		var dLat = ((bLat - aLat) * Math.PI) / 180;
		var dLng = ((bLng - aLng) * Math.PI) / 180;
		var a =
			Math.sin(dLat / 2) * Math.sin(dLat / 2) +
			Math.cos((aLat * Math.PI) / 180) *
				Math.cos((bLat * Math.PI) / 180) *
				Math.sin(dLng / 2) *
				Math.sin(dLng / 2);
		return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	}

	function filtrar(g) {
		var q = (g.queryAtiva || '').toLowerCase().trim();
		var cidade = g.cidadeEl ? g.cidadeEl.value : '';
		var produtoId = g.produtoId;
		var lista = g.locais || [];

		return lista.filter(function (l) {
			if (cidade && l.cidade !== cidade) {
				return false;
			}
			if (produtoId) {
				var tem = (l.produtos || []).some(function (p) {
					return String(p.id) === String(produtoId);
				});
				if (!tem) {
					return false;
				}
			}
			if (!q) {
				return true;
			}
			var texto =
				(l.nome || '') +
				' ' +
				(l.cidade || '') +
				' ' +
				(l.endereco || '') +
				' ' +
				(l.produtos || [])
					.map(function (p) {
						return p.nome;
					})
					.join(' ');
			return texto.toLowerCase().indexOf(q) !== -1;
		});
	}

	function catalogoProdutos(g) {
		var map = {};
		(g.locais || []).forEach(function (l) {
			(l.produtos || []).forEach(function (p) {
				if (p && p.id != null && !map[p.id]) {
					map[p.id] = { id: p.id, nome: p.nome };
				}
			});
		});
		return Object.keys(map)
			.map(function (k) {
				return map[k];
			})
			.sort(function (a, b) {
				return String(a.nome).localeCompare(String(b.nome), 'pt-BR');
			});
	}

	function preencherCidades(g) {
		if (!g.cidadeEl || !g.locais) {
			return;
		}
		var atual = g.cidadeEl.value;
		var set = {};
		g.locais.forEach(function (l) {
			if (l.cidade) {
				set[l.cidade] = true;
			}
		});
		g.cidadeEl.innerHTML = '';
		var opt0 = document.createElement('option');
		opt0.value = '';
		opt0.textContent = cfg.i18n.todas;
		g.cidadeEl.appendChild(opt0);
		Object.keys(set)
			.sort()
			.forEach(function (c) {
				var opt = document.createElement('option');
				opt.value = c;
				opt.textContent = c;
				g.cidadeEl.appendChild(opt);
			});
		if (atual && set[atual]) {
			g.cidadeEl.value = atual;
		}
	}

	function renderProdutos(g) {
		if (!g.produtosEl) {
			return;
		}
		var produtos = catalogoProdutos(g);
		var filtrados = filtrar(g);
		g.produtosEl.innerHTML =
			'<p class="vb-oe-produtos-titulo">PRODUTOS NA REDE (' +
			produtos.length +
			')</p>' +
			'<div class="vb-oe-produtos-box"></div>' +
			'<p class="vb-oe-produtos-total"></p>';

		var box = g.produtosEl.querySelector('.vb-oe-produtos-box');
		var totalEl = g.produtosEl.querySelector('.vb-oe-produtos-total');

		function chip(label, id, ativo) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'vb-oe-prod-chip' + (ativo ? ' is-active' : '');
			btn.textContent = label;
			btn.addEventListener('click', function () {
				g.produtoId = id;
				g.pagina = 1;
				if (g.buscaEl && id) {
					g.buscaEl.value = '';
					g.queryAtiva = '';
				}
				render(g);
			});
			return btn;
		}

		box.appendChild(chip('Todos', null, g.produtoId == null));
		produtos.forEach(function (p) {
			box.appendChild(
				chip(nomeCurtoProduto(p.nome), p.id, String(g.produtoId) === String(p.id))
			);
		});

		totalEl.textContent = filtrados.length
			? filtrados.length +
			  ' ponto' +
			  (filtrados.length !== 1 ? 's' : '') +
			  ' de venda encontrado' +
			  (filtrados.length !== 1 ? 's' : '')
			: 'Nenhum ponto encontrado com os filtros atuais.';
	}

	function mapsUrl(l) {
		return (
			'https://www.google.com/maps/search/?api=1&query=' +
			encodeURIComponent(l.lat + ',' + l.lng)
		);
	}

	function rotaUrl(l, origem) {
		var dest = l.lat + ',' + l.lng;
		if (origem) {
			return (
				'https://www.google.com/maps/dir/?api=1&origin=' +
				encodeURIComponent(origem.lat + ',' + origem.lng) +
				'&destination=' +
				encodeURIComponent(dest)
			);
		}
		return 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dest);
	}

	function tituloLocal(l) {
		return l.nome || l.endereco || 'Estabelecimento';
	}

	function focarLocal(g, l, marker) {
		if (!g.map || !l) {
			return;
		}
		if (g.mapaEl) {
			g.mapaEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
		g.map.flyTo([l.lat, l.lng], 17, { duration: 0.85 });
		if (marker) {
			setTimeout(function () {
				marker.openPopup();
			}, 450);
		}
	}

	function montarPopup(l) {
		var produtos = (l.produtos || [])
			.slice(0, 8)
			.map(function (p) {
				return '<li>' + escapeHtml(nomeCurtoProduto(p.nome)) + '</li>';
			})
			.join('');

		return (
			'<div class="vb-oe-popup-card">' +
			'<button type="button" class="vb-oe-popup-close" aria-label="Fechar" data-vb-oe-close>×</button>' +
			'<h3 class="vb-oe-popup-titulo">' +
			escapeHtml(tituloLocal(l)) +
			'</h3>' +
			'<p class="vb-oe-popup-cidade">' +
			escapeHtml(l.cidade || '') +
			'</p>' +
			'<p class="vb-oe-popup-end">' +
			escapeHtml(l.endereco || '') +
			'</p>' +
			(produtos
				? '<div class="vb-oe-popup-produtos"><span class="vb-oe-popup-label">PRODUTOS DISPONÍVEIS</span><ul>' +
				  produtos +
				  '</ul></div>'
				: '') +
			'<a class="vb-oe-popup-btn" target="_blank" rel="noopener" href="' +
			mapsUrl(l) +
			'">Abrir no Google Maps</a>' +
			'</div>'
		);
	}

	function montarCardLista(l, g, marker) {
		var produtos = (l.produtos || [])
			.slice(0, 6)
			.map(function (p) {
				return (
					'<span class="vb-oe-chip">' +
					escapeHtml(nomeCurtoProduto(p.nome)) +
					'</span>'
				);
			})
			.join('');

		var card = document.createElement('article');
		card.className = 'vb-oe-card';
		card.innerHTML =
			'<div class="vb-oe-card-topo">' +
			'<span class="vb-oe-card-icone" aria-hidden="true"></span>' +
			'<div class="vb-oe-card-textos">' +
			'<h3>' +
			escapeHtml(tituloLocal(l)) +
			'</h3>' +
			'<p class="vb-oe-card-cidade">' +
			escapeHtml(l.cidade || '') +
			'</p>' +
			'<p class="vb-oe-card-end">' +
			escapeHtml(l.endereco || '') +
			'</p>' +
			'</div></div>' +
			(produtos
				? '<div class="vb-oe-card-produtos"><span class="vb-oe-popup-label">PRODUTOS DISPONÍVEIS</span><div class="vb-oe-chips">' +
				  produtos +
				  '</div></div>'
				: '') +
			'<div class="vb-oe-card-acoes">' +
			'<a class="vb-oe-btn-rota" target="_blank" rel="noopener" href="' +
			rotaUrl(l, g.origem) +
			'">Traçar rota</a>' +
			'<button type="button" class="vb-oe-btn-mapa">Ver no mapa</button>' +
			'</div>';

		var btnMapa = card.querySelector('.vb-oe-btn-mapa');
		if (btnMapa) {
			btnMapa.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				focarLocal(g, l, marker || g.markerById[l.id]);
			});
		}

		return card;
	}

	function renderLista(g, filtrados) {
		if (!g.listaEl) {
			return;
		}

		g.listaEl.innerHTML = '';
		g.listaEl.classList.add('vb-oe-lista-grid');

		if (!filtrados.length) {
			g.listaEl.textContent = cfg.i18n.nenhum;
			return;
		}

		var totalPaginas = Math.max(1, Math.ceil(filtrados.length / POR_PAGINA));
		if (g.pagina > totalPaginas) {
			g.pagina = totalPaginas;
		}
		var inicio = (g.pagina - 1) * POR_PAGINA;
		var paginaItens = filtrados.slice(inicio, inicio + POR_PAGINA);

		var grid = document.createElement('div');
		grid.className = 'vb-oe-lista-cards';
		paginaItens.forEach(function (l) {
			grid.appendChild(montarCardLista(l, g, g.markerById[l.id]));
		});
		g.listaEl.appendChild(grid);

		if (totalPaginas > 1) {
			var nav = document.createElement('div');
			nav.className = 'vb-oe-paginacao';
			var prev = document.createElement('button');
			prev.type = 'button';
			prev.textContent = 'Anterior';
			prev.disabled = g.pagina <= 1;
			prev.addEventListener('click', function () {
				g.pagina -= 1;
				renderLista(g, filtrados);
				g.listaEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			});
			var info = document.createElement('span');
			info.textContent = g.pagina + ' / ' + totalPaginas;
			var next = document.createElement('button');
			next.type = 'button';
			next.textContent = 'Próxima';
			next.disabled = g.pagina >= totalPaginas;
			next.addEventListener('click', function () {
				g.pagina += 1;
				renderLista(g, filtrados);
				g.listaEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			});
			nav.appendChild(prev);
			nav.appendChild(info);
			nav.appendChild(next);
			g.listaEl.appendChild(nav);
		}
	}

	function ajustarMapaAosFiltros(g, filtrados) {
		if (!g.map || !filtrados.length) {
			return;
		}
		var bounds = filtrados
			.filter(function (l) {
				return l.lat && l.lng;
			})
			.map(function (l) {
				return [l.lat, l.lng];
			});
		if (!bounds.length) {
			return;
		}
		if (bounds.length === 1) {
			g.map.flyTo(bounds[0], 15, { duration: 0.5 });
		} else {
			g.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
		}
	}

	function render(g, opts) {
		opts = opts || {};
		if (!g.locais) {
			return;
		}

		var filtrados = filtrar(g);
		renderProdutos(g);

		if (g.map && g.markers) {
			g.markers.clearLayers();
			g.markerById = {};
			var iconOpts = cfg.pinUrl ? { icon: pinIcon } : {};

			filtrados.forEach(function (l) {
				if (!l.lat || !l.lng) {
					return;
				}
				var popupHtml = montarPopup(l);
				var marker = L.marker([l.lat, l.lng], iconOpts).bindPopup(popupHtml, {
					className: 'vb-oe-leaflet-popup',
					maxWidth: 300,
					minWidth: 240,
					closeButton: false,
					autoClose: true,
					closeOnClick: true,
				});

				marker.on('click', function () {
					focarLocal(g, l, marker);
				});

				g.markers.addLayer(marker);
				g.markerById[l.id] = marker;
			});

			// Fecha o popup pelo X (delegação — funciona sempre).
			if (!g._closeBound && g.map) {
				g._closeBound = true;
				g.map.getContainer().addEventListener(
					'click',
					function (e) {
						var btn = e.target.closest('[data-vb-oe-close], .vb-oe-popup-close');
						if (!btn) {
							return;
						}
						e.preventDefault();
						e.stopPropagation();
						g.map.closePopup();
					},
					true
				);
			}

			if (opts.ajustarMapa !== false) {
				ajustarMapaAosFiltros(g, filtrados);
			}

			setTimeout(function () {
				g.map.invalidateSize();
			}, 100);
		}

		renderLista(g, filtrados);
	}

	function executarBusca(g) {
		g.queryAtiva = g.buscaEl ? (g.buscaEl.value || '').trim() : '';
		g.pagina = 1;
		render(g, { ajustarMapa: true });
	}

	function carregarDados(g) {
		if (g.carregando || g.locais) {
			if (g.locais) {
				preencherCidades(g);
				render(g, { ajustarMapa: false });
			}
			return;
		}
		g.carregando = true;
		if (g.listaEl) {
			g.listaEl.textContent = cfg.i18n.carregando;
		}

		fetch(cfg.apiUrl)
			.then(function (r) {
				return r.json();
			})
			.then(function (data) {
				g.locais = data.locais || [];
				g.carregando = false;
				preencherCidades(g);
				render(g, { ajustarMapa: true });
			})
			.catch(function () {
				g.carregando = false;
				g.locais = [];
				if (g.listaEl) {
					g.listaEl.textContent = cfg.i18n.nenhum;
				}
			});
	}

	function iniciarMapa(g) {
		if (!g.mapaEl || g.map) {
			return;
		}

		g.map = L.map(g.mapaEl, {
			scrollWheelZoom: false,
			wheelPxPerZoomLevel: 60,
		}).setView([cfg.mapaLat, cfg.mapaLng], cfg.mapaZoom);

		L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			attribution: '&copy; OpenStreetMap',
			maxZoom: 18,
		}).addTo(g.map);

		// Ctrl / ⌘ / Alt + rolagem = zoom nativo do Leaflet (rápido).
		var container = g.map.getContainer();
		container.addEventListener(
			'wheel',
			function (e) {
				var comMod = e.ctrlKey || e.metaKey || e.altKey;
				if (comMod) {
					e.preventDefault();
					var jaAtivo = g.map.scrollWheelZoom.enabled();
					if (!jaAtivo) {
						g.map.scrollWheelZoom.enable();
						// Primeiro tick: o listener do Leaflet ainda não existia.
						if (g.map.scrollWheelZoom._onWheelScroll) {
							g.map.scrollWheelZoom._onWheelScroll(e);
						}
					}
				} else if (g.map.scrollWheelZoom.enabled()) {
					g.map.scrollWheelZoom.disable();
				}
			},
			{ passive: false }
		);
		container.addEventListener('mouseleave', function () {
			if (g.map.scrollWheelZoom.enabled()) {
				g.map.scrollWheelZoom.disable();
			}
		});
		window.addEventListener('keyup', function (e) {
			if ((e.key === 'Control' || e.key === 'Meta' || e.key === 'Alt') && g.map && g.map.scrollWheelZoom.enabled()) {
				g.map.scrollWheelZoom.disable();
			}
		});

		if (!container.querySelector('.vb-oe-zoom-hint')) {
			var hint = document.createElement('p');
			hint.className = 'vb-oe-zoom-hint';
			hint.textContent = 'Ctrl + rolagem para zoom';
			container.appendChild(hint);
		}

		g.markers = L.layerGroup().addTo(g.map);
		carregarDados(g);
	}

	function ligarEventos(g) {
		if (g.buscaEl && !g._boundBusca) {
			g._boundBusca = true;
			var timerBusca = null;

			g.buscaEl.addEventListener('input', function () {
				if (timerBusca) {
					clearTimeout(timerBusca);
				}
				timerBusca = setTimeout(function () {
					executarBusca(g);
				}, 250);
			});

			g.buscaEl.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					if (timerBusca) {
						clearTimeout(timerBusca);
					}
					executarBusca(g);
				}
			});

			g.buscaEl.addEventListener('focus', function () {
				if (g.buscaWrap) {
					g.buscaWrap.classList.add('is-focused');
				}
			});

			g.buscaEl.addEventListener('blur', function () {
				setTimeout(function () {
					if (g.buscaWrap) {
						g.buscaWrap.classList.remove('is-focused');
					}
				}, 150);
			});
		}

		if (g.buscaBtn && !g._boundBuscaBtn) {
			g._boundBuscaBtn = true;
			g.buscaBtn.addEventListener('click', function () {
				executarBusca(g);
			});
		}

		if (g.cidadeEl && !g._boundCidade) {
			g._boundCidade = true;
			g.cidadeEl.addEventListener('change', function () {
				g.pagina = 1;
				render(g, { ajustarMapa: true });
				if (g.mapaEl) {
					g.mapaEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
				}
			});
		}

		if (g.geoBtn && !g._boundGeo) {
			g._boundGeo = true;
			g.geoBtn.addEventListener('click', function () {
				if (!navigator.geolocation) {
					window.alert('Seu navegador não permite localização.');
					return;
				}

				g.geoBtn.disabled = true;
				var textoOriginal = g.geoBtn.textContent;
				g.geoBtn.textContent = 'Localizando...';

				navigator.geolocation.getCurrentPosition(
					function (pos) {
						g.origem = {
							lat: pos.coords.latitude,
							lng: pos.coords.longitude,
						};

						if (g.map) {
							if (g._userMarker) {
								g.map.removeLayer(g._userMarker);
							}
							g._userMarker = L.circleMarker([g.origem.lat, g.origem.lng], {
								radius: 9,
								color: '#1a3a6b',
								fillColor: '#3b82f6',
								fillOpacity: 0.85,
								weight: 2,
							}).addTo(g.map);
							g._userMarker.bindPopup('Você está aqui').openPopup();
							g.map.flyTo([g.origem.lat, g.origem.lng], 12, { duration: 0.7 });
						}

						// Ordena a lista por distância a partir daqui.
						if (g.locais) {
							g.locais = g.locais.slice().sort(function (a, b) {
								if (!a.lat || !b.lat) {
									return 0;
								}
								return (
									distanciaKm(g.origem.lat, g.origem.lng, a.lat, a.lng) -
									distanciaKm(g.origem.lat, g.origem.lng, b.lat, b.lng)
								);
							});
						}

						g.pagina = 1;
						g.geoBtn.disabled = false;
						g.geoBtn.textContent = textoOriginal;
						render(g, { ajustarMapa: false });

						if (g.mapaEl) {
							g.mapaEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
						}
					},
					function (err) {
						g.geoBtn.disabled = false;
						g.geoBtn.textContent = textoOriginal;
						var msg = 'Não foi possível obter sua localização.';
						if (err && err.code === 1) {
							msg = 'Permissão de localização negada. Libere no navegador e tente de novo.';
						} else if (err && err.code === 2) {
							msg = 'Localização indisponível no momento.';
						} else if (err && err.code === 3) {
							msg = 'Tempo esgotado ao buscar localização.';
						}
						window.alert(msg);
					},
					{
						enableHighAccuracy: true,
						timeout: 15000,
						maximumAge: 60000,
					}
				);
			});
		}
	}

	function registrarPeca(el) {
		var grupoId = el.getAttribute('data-vb-grupo') || 'padrao';
		var g = getGrupo(grupoId);

		if (el.hasAttribute('data-vb-oe-mapa')) {
			g.mapaEl = el;
		}
		if (el.hasAttribute('data-vb-oe-busca')) {
			g.buscaWrap = el;
			g.buscaEl = el.querySelector('.vb-oe-busca');
			g.buscaBtn = el.querySelector('.vb-oe-busca-btn');
		}
		if (el.hasAttribute('data-vb-oe-filtro')) {
			g.cidadeEl = el.querySelector('.vb-oe-cidade');
			g.geoBtn = el.querySelector('.vb-oe-geo');
		}
		if (el.hasAttribute('data-vb-oe-produtos')) {
			g.produtosEl = el;
		}
		if (el.hasAttribute('data-vb-oe-lista')) {
			g.listaEl = el;
		}

		ligarEventos(g);
		iniciarMapa(g);
		if (g.locais) {
			preencherCidades(g);
			render(g, { ajustarMapa: false });
		} else if (!g.mapaEl) {
			carregarDados(g);
		}
	}

	function boot() {
		document
			.querySelectorAll(
				'[data-vb-oe-mapa], [data-vb-oe-busca], [data-vb-oe-filtro], [data-vb-oe-produtos], [data-vb-oe-lista]'
			)
			.forEach(registrarPeca);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

	window.addEventListener('elementor/frontend/init', function () {
		if (window.elementorFrontend && elementorFrontend.hooks) {
			elementorFrontend.hooks.addAction('frontend/element_ready/global', function () {
				boot();
			});
		}
	});
})();
