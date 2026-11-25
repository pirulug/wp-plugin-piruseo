(function ($) {
	'use strict';

	/* =========================================================
	 * 1. Tabs (Settings Page & Meta Box)
	 * ========================================================= */
	$(document).on('click', '.piruseo-nav-tabs .nav-tab', function (e) {
		e.preventDefault();
		var tab = $(this).data('tab');
		$('.piruseo-nav-tabs .nav-tab').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active');
		$('.piruseo-tab-panel').removeClass('is-active');
		$('#piruseo-tab-' + tab).addClass('is-active');
	});

	$(document).on('click', '.piruseo-mb-tab', function () {
		var tab = $(this).data('mb-tab');
		$('.piruseo-mb-tab').removeClass('is-active');
		$(this).addClass('is-active');
		$('.piruseo-mb-panel').removeClass('is-active');
		$('#piruseo-mb-' + tab).addClass('is-active');
	});

	/* =========================================================
	 * 2. WordPress Media Uploader
	 * ========================================================= */
	$(document).on('click', '.piruseo-media-upload', function (e) {
		e.preventDefault();
		var targetSelector = $(this).data('target');
		if (!window.wp || !wp.media) {
			return;
		}
		var frame = wp.media({
			title: 'Seleccionar imagen',
			button: { text: 'Usar esta imagen' },
			multiple: false
		});
		frame.on('select', function () {
			var attachment = frame.state().get('selection').first().toJSON();
			if (attachment && attachment.url) {
				$(targetSelector).val(attachment.url).trigger('input');
			}
		});
		frame.open();
	});

	/* =========================================================
	 * 3. Post Editor Live SERP Preview, SEO & Readability Analyzer
	 * ========================================================= */
	var $app = $('#piruseo-metabox-app');
	if (!$app.length || typeof window.piruSeoData === 'undefined') {
		return;
	}

	var config = window.piruSeoData;
	var SPANISH_TRANSITIONS = [
		'además', 'sin embargo', 'por lo tanto', 'asimismo', 'en cambio',
		'no obstante', 'por ejemplo', 'en conclusión', 'finalmente', 'primero',
		'segundo', 'luego', 'después', 'mientras tanto', 'aunque', 'porque',
		'ya que', 'debido a', 'por consiguiente', 'es decir', 'en resumen',
		'por otro lado', 'en primer lugar', 'sobre todo', 'también', 'incluso'
	];

	// SERP Desktop / Mobile toggle.
	$(document).on('click', '.piruseo-serp-toggle button', function () {
		var mode = $(this).data('serp-mode');
		$('.piruseo-serp-toggle button').removeClass('is-active button-primary');
		$(this).addClass('is-active button-primary');
		$('#piruseo-serp-preview')
			.removeClass('mode-desktop mode-mobile')
			.addClass('mode-' + mode);
	});

	// Variable chips insertion.
	$(document).on('click', '.piruseo-insert-var', function () {
		var $target = $($(this).data('target'));
		var variable = $(this).data('var');
		var current = $target.val();
		var sep = current && !current.endsWith(' ') ? ' ' : '';
		$target.val(current + sep + variable).trigger('input').focus();
	});

	function normalizeText(str) {
		return (str || '')
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	function stripHtml(html) {
		var div = document.createElement('div');
		div.innerHTML = html || '';
		return (div.textContent || div.innerText || '').trim();
	}

	function getEditorState() {
		var postTitle = '';
		var postContent = '';
		var postSlug = config.postSlug || '';

		if (window.wp && wp.data && wp.data.select('core/editor')) {
			var editor = wp.data.select('core/editor');
			postTitle = editor.getEditedPostAttribute('title') || '';
			postContent = editor.getEditedPostContent() || '';
			postSlug = editor.getEditedPostAttribute('slug') || postSlug;
		} else {
			postTitle = $('#title').val() || '';
			if (window.tinymce && tinymce.get('content') && !tinymce.get('content').isHidden()) {
				postContent = tinymce.get('content').getContent() || '';
			} else {
				postContent = $('#content').val() || '';
			}
			var slugEl = $('#editable-post-name');
			if (slugEl.length && slugEl.text().trim()) {
				postSlug = slugEl.text().trim();
			}
		}

		return {
			postTitle: postTitle,
			postContent: postContent,
			postSlug: postSlug
		};
	}

	function resolveTemplate(template, postTitle) {
		var year = String(new Date().getFullYear());
		return (template || '')
			.replace(/%%title%%/g, postTitle || 'Título de ejemplo')
			.replace(/%%sitename%%/g, config.siteName || '')
			.replace(/%%sitedesc%%/g, config.siteDesc || '')
			.replace(/%%sep%%/g, config.separator || '|')
			.replace(/%%year%%/g, year)
			.replace(/\s+/g, ' ')
			.trim();
	}

	function updateMeter($bar, $counter, length, minGood, maxGood, maxHard) {
		$counter.text(length + ' / ' + maxGood);
		var pct = Math.min(100, Math.round((length / maxHard) * 100));
		var cls = 'is-bad';
		if (length >= minGood && length <= maxGood) {
			cls = 'is-good';
		} else if (length > 0 && length < minGood) {
			cls = 'is-ok';
		} else if (length > maxGood && length <= maxHard) {
			cls = 'is-ok';
		}
		$bar.css('width', pct + '%').removeClass('is-good is-ok is-bad').addClass(cls);
	}

	function countOccurrences(haystack, needle) {
		if (!needle) {
			return 0;
		}
		var escaped = needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		var matches = haystack.match(new RegExp(escaped, 'g'));
		return matches ? matches.length : 0;
	}

	function renderChecks($list, $badge, $dot, checks) {
		var html = '';
		var good = 0;
		var bad = 0;

		checks.forEach(function (item) {
			if (item.status === 'good') {
				good++;
			} else if (item.status === 'bad') {
				bad++;
			}
			html += '<li><span class="piruseo-dot piruseo-dot-' + item.status + '"></span><span>' + item.text + '</span></li>';
		});

		$list.html(html);

		var overall = 'ok';
		var label = 'Mejorable';
		if (checks.length === 0) {
			overall = 'na';
			label = 'Sin datos';
		} else if (bad === 0 && good >= Math.ceil(checks.length * 0.6)) {
			overall = 'good';
			label = 'Bueno';
		} else if (bad >= Math.ceil(checks.length * 0.4)) {
			overall = 'bad';
			label = 'Necesita mejoras';
		}

		$badge
			.text(label)
			.removeClass('is-good is-ok is-bad')
			.addClass(overall !== 'na' ? 'is-' + overall : '');
		$dot
			.removeClass('piruseo-dot-good piruseo-dot-ok piruseo-dot-bad piruseo-dot-na')
			.addClass('piruseo-dot-' + overall);

		return overall;
	}

	function runAnalysis() {
		var state = getEditorState();
		var focusKw = ($('#piruseo-focus-kw').val() || '').trim();
		var rawTitle = ($('#piruseo-title').val() || '').trim();
		var rawDesc = ($('#piruseo-description').val() || '').trim();

		var resolvedTitle = resolveTemplate(rawTitle || config.titleTemplate, state.postTitle);
		var plainText = stripHtml(state.postContent);
		var autoDesc = plainText.substring(0, 155) + (plainText.length > 155 ? '…' : '');
		var resolvedDesc = rawDesc || autoDesc || 'Añade una meta descripción para controlar cómo se muestra esta página en los resultados de Google.';

		// Update SERP preview.
		$('#piruseo-serp-title').text(resolvedTitle.substring(0, 70));
		$('#piruseo-serp-desc').text(resolvedDesc.substring(0, 165));
		if (state.postSlug) {
			$('#piruseo-serp-url').text(config.siteUrl.replace(/\/$/, '') + '/' + state.postSlug + '/');
		}

		// Update progress bars.
		updateMeter($('#piruseo-title-bar'), $('#piruseo-title-counter'), resolvedTitle.length, 30, 60, 75);
		updateMeter($('#piruseo-desc-bar'), $('#piruseo-desc-counter'), rawDesc.length, 120, 160, 180);

		// Parse DOM from content.
		var $contentDom = $('<div>').html(state.postContent);
		var words = plainText ? plainText.split(/\s+/).filter(Boolean) : [];
		var wordCount = words.length;

		var normKw = normalizeText(focusKw);
		var normTitle = normalizeText(resolvedTitle);
		var normDesc = normalizeText(rawDesc);
		var normText = normalizeText(plainText);
		var normSlug = normalizeText(state.postSlug).replace(/-/g, ' ');

		var seoChecks = [];

		// 1. Focus Keyphrase checks.
		if (!focusKw) {
			seoChecks.push({
				status: 'bad',
				text: '<strong>Frase clave objetivo:</strong> No has definido ninguna frase clave objetivo. Añade una para analizar la optimización.'
			});
		} else {
			// In SEO title.
			if (normTitle.indexOf(normKw) !== -1) {
				seoChecks.push({
					status: 'good',
					text: '<strong>Frase clave en el título SEO:</strong> ¡Bien! La frase clave aparece en el título SEO.'
				});
			} else {
				seoChecks.push({
					status: 'bad',
					text: '<strong>Frase clave en el título SEO:</strong> La frase clave no aparece en el título SEO.'
				});
			}

			// In Meta Description.
			if (rawDesc && normDesc.indexOf(normKw) !== -1) {
				seoChecks.push({
					status: 'good',
					text: '<strong>Frase clave en la meta descripción:</strong> ¡Bien! Aparece en la meta descripción.'
				});
			} else {
				seoChecks.push({
					status: 'ok',
					text: '<strong>Frase clave en la meta descripción:</strong> Incluye la frase clave en tu meta descripción.'
				});
			}

			// In Slug.
			if (normSlug && normSlug.indexOf(normKw) !== -1) {
				seoChecks.push({
					status: 'good',
					text: '<strong>Frase clave en el slug (URL):</strong> ¡Bien! La URL contiene la frase clave.'
				});
			} else {
				seoChecks.push({
					status: 'ok',
					text: '<strong>Frase clave en el slug (URL):</strong> Considera incluir la frase clave en el enlace permanente.'
				});
			}

			// In First paragraph.
			var firstParagraph = normalizeText($contentDom.find('p').first().text() || plainText.substring(0, 250));
			if (firstParagraph && firstParagraph.indexOf(normKw) !== -1) {
				seoChecks.push({
					status: 'good',
					text: '<strong>Frase clave en la introducción:</strong> ¡Bien! Aparece en el primer párrafo.'
				});
			} else {
				seoChecks.push({
					status: 'ok',
					text: '<strong>Frase clave en la introducción:</strong> Intenta mencionar la frase clave en el primer párrafo.'
				});
			}

			// Keyphrase density.
			var kwCount = countOccurrences(normText, normKw);
			var kwWordLen = normKw.split(/\s+/).filter(Boolean).length || 1;
			var density = wordCount > 0 ? ((kwCount * kwWordLen) / wordCount) * 100 : 0;
			if (kwCount === 0) {
				seoChecks.push({
					status: 'bad',
					text: '<strong>Densidad de frase clave:</strong> La frase clave no aparece en el texto (0 veces).'
				});
			} else if (density >= 0.5 && density <= 3.0) {
				seoChecks.push({
					status: 'good',
					text: '<strong>Densidad de frase clave:</strong> Aparece ' + kwCount + ' veces (' + density.toFixed(1) + '%). ¡Excelente!'
				});
			} else if (density > 3.0) {
				seoChecks.push({
					status: 'bad',
					text: '<strong>Densidad de frase clave:</strong> Demasiado alta (' + density.toFixed(1) + '%). Evita la sobreoptimización.'
				});
			} else {
				seoChecks.push({
					status: 'ok',
					text: '<strong>Densidad de frase clave:</strong> Baja (' + kwCount + ' veces, ' + density.toFixed(1) + '%). Úsala un poco más de forma natural.'
				});
			}
		}

		// 2. Title length.
		if (resolvedTitle.length >= 30 && resolvedTitle.length <= 60) {
			seoChecks.push({
				status: 'good',
				text: '<strong>Longitud del título SEO:</strong> Perfecta (' + resolvedTitle.length + ' caracteres).'
			});
		} else if (resolvedTitle.length > 60) {
			seoChecks.push({
				status: 'ok',
				text: '<strong>Longitud del título SEO:</strong> Es algo largo (' + resolvedTitle.length + ' caracteres). Google suele truncar sobre los 60.'
			});
		} else {
			seoChecks.push({
				status: 'ok',
				text: '<strong>Longitud del título SEO:</strong> Es corto (' + resolvedTitle.length + ' caracteres). Recomendado: 30–60.'
			});
		}

		// 3. Meta description length.
		if (!rawDesc) {
			seoChecks.push({
				status: 'bad',
				text: '<strong>Meta descripción:</strong> No has escrito una meta descripción personalizada.'
			});
		} else if (rawDesc.length >= 120 && rawDesc.length <= 160) {
			seoChecks.push({
				status: 'good',
				text: '<strong>Meta descripción:</strong> Longitud ideal (' + rawDesc.length + ' caracteres).'
			});
		} else {
			seoChecks.push({
				status: 'ok',
				text: '<strong>Meta descripción:</strong> Tiene ' + rawDesc.length + ' caracteres (recomendado: 120–160).'
			});
		}

		// 4. Word count.
		if (wordCount >= 300) {
			seoChecks.push({
				status: 'good',
				text: '<strong>Longitud del texto:</strong> El contenido tiene ' + wordCount + ' palabras. ¡Buen trabajo!'
			});
		} else if (wordCount >= 150) {
			seoChecks.push({
				status: 'ok',
				text: '<strong>Longitud del texto:</strong> Tiene ' + wordCount + ' palabras. El mínimo recomendado es de 300 palabras.'
			});
		} else {
			seoChecks.push({
				status: 'bad',
				text: '<strong>Longitud del texto:</strong> Muy corto (' + wordCount + ' palabras). Añade más contenido de valor (mínimo 300).'
			});
		}

		// 5. Internal & External links.
		var internalLinks = 0;
		var externalLinks = 0;
		var siteHost = '';
		try {
			siteHost = new URL(config.siteUrl).hostname;
		} catch (e) {
			siteHost = window.location.hostname;
		}
		$contentDom.find('a[href]').each(function () {
			var href = $(this).attr('href') || '';
			if (href.startsWith('/') || href.startsWith('#') || href.indexOf(siteHost) !== -1) {
				internalLinks++;
			} else if (href.startsWith('http')) {
				externalLinks++;
			}
		});

		if (internalLinks > 0 && externalLinks > 0) {
			seoChecks.push({
				status: 'good',
				text: '<strong>Enlaces:</strong> Tienes ' + internalLinks + ' enlace(s) interno(s) y ' + externalLinks + ' externo(s).'
			});
		} else if (internalLinks === 0 && externalLinks === 0) {
			seoChecks.push({
				status: 'bad',
				text: '<strong>Enlaces:</strong> No hay enlaces internos ni externos en esta página.'
			});
		} else {
			seoChecks.push({
				status: 'ok',
				text: '<strong>Enlaces:</strong> ' + (internalLinks === 0 ? 'Faltan enlaces internos.' : 'Faltan enlaces externos de referencia.')
			});
		}

		// 6. Images & alt text.
		var $imgs = $contentDom.find('img');
		if ($imgs.length === 0) {
			seoChecks.push({
				status: 'ok',
				text: '<strong>Imágenes:</strong> No hay imágenes en el contenido. Añadir elementos visuales mejora la experiencia.'
			});
		} else {
			var missingAlt = 0;
			$imgs.each(function () {
				if (!($(this).attr('alt') || '').trim()) {
					missingAlt++;
				}
			});
			if (missingAlt === 0) {
				seoChecks.push({
					status: 'good',
					text: '<strong>Atributos ALT de imágenes:</strong> Todas las imágenes (' + $imgs.length + ') tienen atributo alt.'
				});
			} else {
				seoChecks.push({
					status: 'ok',
					text: '<strong>Atributos ALT de imágenes:</strong> ' + missingAlt + ' de ' + $imgs.length + ' imágenes no tienen texto alternativo (alt).'
				});
			}
		}

		var seoOverall = renderChecks($('#piruseo-seo-checks'), $('#piruseo-seo-badge'), $('#piruseo-tab-seo-dot'), seoChecks);
		$('#piruseo-seo-score-input').val(seoOverall);

		/* ---------- READABILITY ANALYSIS ---------- */
		var readChecks = [];

		// 1. Subheadings.
		var headings = $contentDom.find('h2, h3, h4').length;
		if (wordCount > 250 && headings === 0) {
			readChecks.push({
				status: 'bad',
				text: '<strong>Subtítulos (H2/H3):</strong> Tu texto supera las 250 palabras y no tiene subtítulos. Divide el contenido para facilitar la lectura.'
			});
		} else if (headings > 0) {
			readChecks.push({
				status: 'good',
				text: '<strong>Subtítulos (H2/H3):</strong> ¡Bien! Has utilizado ' + headings + ' subtítulo(s) para estructurar el artículo.'
			});
		} else {
			readChecks.push({
				status: 'ok',
				text: '<strong>Subtítulos (H2/H3):</strong> Añade subtítulos a medida que amplíes el contenido.'
			});
		}

		// 2. Paragraph length.
		var longParagraphs = 0;
		$contentDom.find('p').each(function () {
			var pWords = ($(this).text() || '').trim().split(/\s+/).filter(Boolean).length;
			if (pWords > 150) {
				longParagraphs++;
			}
		});
		if (longParagraphs === 0) {
			readChecks.push({
				status: 'good',
				text: '<strong>Longitud de párrafos:</strong> Ningún párrafo supera las 150 palabras. ¡Fácil de escanear!'
			});
		} else {
			readChecks.push({
				status: 'bad',
				text: '<strong>Longitud de párrafos:</strong> ' + longParagraphs + ' párrafo(s) superan las 150 palabras. Divídelos en bloques más cortos.'
			});
		}

		// 3. Sentence length.
		var sentences = plainText ? plainText.split(/[.!?]+/).map(function (s) { return s.trim(); }).filter(Boolean) : [];
		if (sentences.length > 0) {
			var longSentences = sentences.filter(function (s) {
				return s.split(/\s+/).filter(Boolean).length > 25;
			}).length;
			var longPct = Math.round((longSentences / sentences.length) * 100);
			if (longPct <= 25) {
				readChecks.push({
					status: 'good',
					text: '<strong>Longitud de frases:</strong> Solo el ' + longPct + '% de las frases son largas (>25 palabras). ¡Muy claro!'
				});
			} else {
				readChecks.push({
					status: 'ok',
					text: '<strong>Longitud de frases:</strong> El ' + longPct + '% de las frases superan 25 palabras. Intenta acortarlas (recomendado ≤25%).'
				});
			}

			// 4. Transition words.
			var withTransition = sentences.filter(function (s) {
				var norm = normalizeText(s);
				return SPANISH_TRANSITIONS.some(function (tw) {
					return norm.indexOf(normalizeText(tw)) !== -1;
				});
			}).length;
			var transPct = Math.round((withTransition / sentences.length) * 100);
			if (transPct >= 25) {
				readChecks.push({
					status: 'good',
					text: '<strong>Palabras de transición:</strong> El ' + transPct + '% de las frases incluyen conectores. ¡Buen ritmo de lectura!'
				});
			} else {
				readChecks.push({
					status: 'ok',
					text: '<strong>Palabras de transición:</strong> Solo el ' + transPct + '% de las frases usan conectores (además, sin embargo, por lo tanto…).'
				});
			}
		}

		renderChecks($('#piruseo-read-checks'), $('#piruseo-read-badge'), $('#piruseo-tab-read-dot'), readChecks);
	}

	// Debounce helper.
	var timer = null;
	function scheduleAnalysis() {
		clearTimeout(timer);
		timer = setTimeout(runAnalysis, 250);
	}

	// Bind input events on PiruSEO fields and Classic Editor fields.
	$('#piruseo-focus-kw, #piruseo-title, #piruseo-description, #title, #content').on('input change keyup', scheduleAnalysis);

	// Bind TinyMCE (Classic Editor).
	if (window.tinymce) {
		$(document).on('tinymce-editor-init', function (e, editor) {
			if (editor.id === 'content') {
				editor.on('keyup change', scheduleAnalysis);
			}
		});
	}

	// Subscribe to Gutenberg (Block Editor) store changes.
	if (window.wp && wp.data && typeof wp.data.subscribe === 'function') {
		var lastSig = '';
		wp.data.subscribe(function () {
			var editor = wp.data.select('core/editor');
			if (!editor) {
				return;
			}
			var t = editor.getEditedPostAttribute('title') || '';
			var c = editor.getEditedPostContent() || '';
			var s = editor.getEditedPostAttribute('slug') || '';
			var sig = t.length + ':' + c.length + ':' + s;
			if (sig !== lastSig) {
				lastSig = sig;
				scheduleAnalysis();
			}
		});
	}

	// Initial run.
	runAnalysis();
})(jQuery);
