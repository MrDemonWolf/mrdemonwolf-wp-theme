(function () {
	'use strict';

	var config = window.mdwColorPreview;
	if (!config) return;

	// --- Color math utilities ---

	function hexToRgb(hex) {
		hex = hex.replace(/^#/, '');
		if (hex.length === 3) {
			hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
		}
		var n = parseInt(hex, 16);
		return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
	}

	function rgbToHex(r, g, b) {
		return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
	}

	function hexToHsl(hex) {
		var rgb = hexToRgb(hex);
		var r = rgb.r / 255, g = rgb.g / 255, b = rgb.b / 255;
		var max = Math.max(r, g, b), min = Math.min(r, g, b);
		var h, s, l = (max + min) / 2;

		if (max === min) {
			h = s = 0;
		} else {
			var d = max - min;
			s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
			switch (max) {
				case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break;
				case g: h = ((b - r) / d + 2) / 6; break;
				case b: h = ((r - g) / d + 4) / 6; break;
			}
		}
		return { h: h * 360, s: s * 100, l: l * 100 };
	}

	function hslToHex(h, s, l) {
		h = ((h % 360) + 360) % 360;
		s = Math.max(0, Math.min(100, s)) / 100;
		l = Math.max(0, Math.min(100, l)) / 100;

		var c = (1 - Math.abs(2 * l - 1)) * s;
		var x = c * (1 - Math.abs((h / 60) % 2 - 1));
		var m = l - c / 2;
		var r, g, b;

		if (h < 60)       { r = c; g = x; b = 0; }
		else if (h < 120) { r = x; g = c; b = 0; }
		else if (h < 180) { r = 0; g = c; b = x; }
		else if (h < 240) { r = 0; g = x; b = c; }
		else if (h < 300) { r = x; g = 0; b = c; }
		else              { r = c; g = 0; b = x; }

		return rgbToHex(
			Math.round((r + m) * 255),
			Math.round((g + m) * 255),
			Math.round((b + m) * 255)
		);
	}

	// --- WCAG contrast utilities ---

	function relativeLuminance(hex) {
		var rgb = hexToRgb(hex);
		var rsRGB = rgb.r / 255;
		var gsRGB = rgb.g / 255;
		var bsRGB = rgb.b / 255;
		var r = rsRGB <= 0.03928 ? rsRGB / 12.92 : Math.pow((rsRGB + 0.055) / 1.055, 2.4);
		var g = gsRGB <= 0.03928 ? gsRGB / 12.92 : Math.pow((gsRGB + 0.055) / 1.055, 2.4);
		var b = bsRGB <= 0.03928 ? bsRGB / 12.92 : Math.pow((bsRGB + 0.055) / 1.055, 2.4);
		return 0.2126 * r + 0.7152 * g + 0.0722 * b;
	}

	function contrastRatio(hex1, hex2) {
		var l1 = relativeLuminance(hex1);
		var l2 = relativeLuminance(hex2);
		var lighter = Math.max(l1, l2);
		var darker = Math.min(l1, l2);
		return (lighter + 0.05) / (darker + 0.05);
	}

	var AA_THRESHOLD = 4.5;

	// Text color key → background color key pairs from the theme
	var CONTRAST_PAIRS = [
		{ text: 'body_text', bg: 'light_bg',   where: 'Main content' },
		{ text: 'secondary', bg: 'light_bg',   where: 'Headings' },
		{ text: 'primary',   bg: 'light_bg',   where: 'Links, accents' },
		{ text: 'body_text', bg: 'background',  where: 'Light sections' },
		{ text: 'secondary', bg: 'background',  where: 'Section headings' },
		{ text: '#ffffff',   bg: 'primary',     where: 'Buttons', fixed: 'text' },
		{ text: '#ffffff',   bg: 'secondary',   where: 'Dark badges/buttons', fixed: 'text' }
	];

	// --- State ---

	var state = {
		colors: {},
		originals: {},
		overrides: {},
		deltas: {},
		derivationMode: 'delta'
	};

	// Initialize state from config
	function initState() {
		var defs = config.colors;
		for (var key in defs) {
			var currentVal = (config.current && config.current[key]) || defs[key]['default'];
			state.colors[key] = currentVal;
			state.originals[key] = defs[key]['default'];
		}

		// Compute HSL deltas between each derivative and its source (using defaults)
		for (var key in defs) {
			if (defs[key].source) {
				var sourceHsl = hexToHsl(defs[defs[key].source]['default']);
				var derivHsl = hexToHsl(defs[key]['default']);
				state.deltas[key] = {
					dh: derivHsl.h - sourceHsl.h,
					ds: derivHsl.s - sourceHsl.s,
					dl: derivHsl.l - sourceHsl.l,
					rs: sourceHsl.s > 0 ? derivHsl.s / sourceHsl.s : null,
					rl: sourceHsl.l > 0 ? derivHsl.l / sourceHsl.l : null,
					// Relative mode: fraction of distance to white/black
					flS: (100 - sourceHsl.s) > 0 ? (derivHsl.s - sourceHsl.s) / (100 - sourceHsl.s) : 0,
					flL: (100 - sourceHsl.l) > 0 ? (derivHsl.l - sourceHsl.l) / (100 - sourceHsl.l) : 0,
					lighterL: derivHsl.l >= sourceHsl.l,
					lighterS: derivHsl.s >= sourceHsl.s
				};
			}
		}
	}

	// --- Live preview engine ---

	var styleEl = null;

	function getOrCreateStyleEl() {
		if (!styleEl) {
			styleEl = document.createElement('style');
			styleEl.id = 'mdw-cp-overrides';
			document.head.appendChild(styleEl);
		}
		return styleEl;
	}

	function buildSvgDataUri(color) {
		var encoded = color.replace('#', '%23');
		return 'url("data:image/svg+xml,<svg viewBox=\\"0 0 11 11\\" fill=\\"none\\" xmlns=\\"http://www.w3.org/2000/svg\\" class=\\"w-11 h-11\\"><path d=\\"M11 1.54972e-06L0 0L2.38419e-07 11C1.65973e-07 4.92487 4.92487 1.62217e-06 11 1.54972e-06Z\\" fill=\\"' + encoded + '\\"></path></svg>")';
	}

	function rebuildPreview() {
		var el = getOrCreateStyleEl();
		var rules = [];

		// 1. CSS variable overrides
		var varRules = [];
		var defs = config.colors;
		for (var key in defs) {
			if (defs[key].css_var) {
				varRules.push('\t' + defs[key].css_var + ': ' + state.colors[key] + ' !important;');
			}
		}
		// Mirror heading color to secondary
		varRules.push('\t--gcid-heading-color: ' + state.colors.secondary + ' !important;');
		rules.push(':root {\n' + varRules.join('\n') + '\n}');

		// 2. Hardcoded hex overrides from selector map
		var map = config.selectorMap;
		for (var colorKey in map) {
			if (colorKey === 'primary_rgba') continue;
			var entries = map[colorKey];
			for (var i = 0; i < entries.length; i++) {
				var entry = entries[i];
				if (entry.type === 'svg_data_uri') {
					rules.push(entry.selector + ' { ' + entry.property + ': ' + buildSvgDataUri(state.colors[colorKey]) + ' !important; }');
				} else {
					rules.push(entry.selector + ' { ' + entry.property + ': ' + state.colors[colorKey] + ' !important; }');
				}
			}
		}

		// 3. RGBA + gradient overrides from primary
		if (map.primary_rgba) {
			var pRgb = hexToRgb(state.colors.primary);
			var rgba03 = 'rgba(' + pRgb.r + ', ' + pRgb.g + ', ' + pRgb.b + ', 0.3)';
			var rgba015 = 'rgba(' + pRgb.r + ', ' + pRgb.g + ', ' + pRgb.b + ', 0.15)';
			var rgba0 = 'rgba(' + pRgb.r + ', ' + pRgb.g + ', ' + pRgb.b + ', 0)';
			var gradient = 'linear-gradient(-45deg, ' + rgba03 + ' 0%, ' + rgba0 + ' 50%, ' + rgba03 + ' 100%)';

			var entries = map.primary_rgba;
			for (var i = 0; i < entries.length; i++) {
				var entry = entries[i];
				if (entry.type === 'gradient') {
					rules.push(entry.selector + ' { ' + entry.property + ': ' + gradient + ' !important; }');
				} else if (entry.type === 'rgba_015') {
					rules.push(entry.selector + ' { ' + entry.property + ': ' + rgba015 + ' !important; }');
				}
			}
		}

		el.textContent = rules.join('\n');

		checkContrast();
	}

	function removePreview() {
		if (styleEl) {
			styleEl.remove();
			styleEl = null;
		}
	}

	// --- Derivation ---

	function deriveColor(key) {
		var defs = config.colors;
		var sourceKey = defs[key].source;
		if (!sourceKey || state.overrides[key]) return;

		var sourceHsl = hexToHsl(state.colors[sourceKey]);
		var delta = state.deltas[key];
		var newS, newL;

		if (state.derivationMode === 'relative') {
			// Relative mode: preserves proportional position between source and white/black
			if (delta.lighterS) {
				newS = sourceHsl.s + (100 - sourceHsl.s) * delta.flS;
			} else {
				newS = sourceHsl.s * (delta.rs !== null ? delta.rs : 1);
			}

			if (delta.lighterL) {
				newL = sourceHsl.l + (100 - sourceHsl.l) * delta.flL;
			} else {
				newL = sourceHsl.l * (delta.rl !== null ? delta.rl : 1);
			}
		} else if (state.derivationMode === 'dark') {
			// Dark mode: compute relative position, then invert lightness
			// Saturation: use relative formula (unchanged)
			if (delta.lighterS) {
				newS = sourceHsl.s + (100 - sourceHsl.s) * delta.flS;
			} else {
				newS = sourceHsl.s * (delta.rs !== null ? delta.rs : 1);
			}
			// Lightness: compute relative position, then invert
			var relL;
			if (delta.lighterL) {
				relL = sourceHsl.l + (100 - sourceHsl.l) * delta.flL;
			} else {
				relL = sourceHsl.l * (delta.rl !== null ? delta.rl : 1);
			}
			var invertedL = 100 - relL;
			var role = defs[key].role;
			if (role === 'bg') {
				newL = Math.min(invertedL, 20);
			} else if (role === 'text') {
				newL = Math.max(invertedL, 75);
			} else {
				newL = invertedL;
			}
		} else {
			// Delta mode: absolute offsets with proportional fallback on overflow
			newS = sourceHsl.s + delta.ds;
			newL = sourceHsl.l + delta.dl;

			// If lightness overflows, fall back to relative positioning
			if (newL > 100) {
				if (delta.lighterL) {
					newL = sourceHsl.l + (100 - sourceHsl.l) * delta.flL;
				} else {
					newL = 100;
				}
			}
			if (newL < 0) {
				if (!delta.lighterL) {
					newL = sourceHsl.l * (delta.rl !== null ? delta.rl : 0);
				} else {
					newL = 0;
				}
			}
			// Same for saturation
			if (newS > 100) {
				if (delta.lighterS) {
					newS = sourceHsl.s + (100 - sourceHsl.s) * delta.flS;
				} else {
					newS = 100;
				}
			}
			if (newS < 0) {
				if (!delta.lighterS) {
					newS = sourceHsl.s * (delta.rs !== null ? delta.rs : 0);
				} else {
					newS = 0;
				}
			}
		}

		var newHex = hslToHex(
			sourceHsl.h + delta.dh,
			newS,
			newL
		);

		state.colors[key] = newHex;
	}

	function deriveAllFromSource(sourceKey) {
		var defs = config.colors;
		for (var key in defs) {
			if (defs[key].source === sourceKey) {
				deriveColor(key);
			}
		}
	}

	// --- Contrast checking ---

	function resolveContrastColor(pair, side) {
		var val = pair[side];
		if (val.charAt(0) === '#') return val;
		return state.colors[val];
	}

	function checkContrast() {
		// Clear all previous contrast states
		var allRows = document.querySelectorAll('.mdw-cp-row');
		allRows.forEach(function (row) {
			row.classList.remove('mdw-cp-contrast-fail');
			var ratioEl = row.querySelector('.mdw-cp-contrast-ratio');
			if (ratioEl) ratioEl.textContent = '';
		});

		// Track worst ratio per color key for display
		var worstRatios = {};

		for (var i = 0; i < CONTRAST_PAIRS.length; i++) {
			var pair = CONTRAST_PAIRS[i];
			var textColor = resolveContrastColor(pair, 'text');
			var bgColor = resolveContrastColor(pair, 'bg');
			if (!textColor || !bgColor) continue;

			var ratio = contrastRatio(textColor, bgColor);
			var pass = ratio >= AA_THRESHOLD;

			// Determine which row to flag (the non-fixed, adjustable color)
			var flagKey = null;
			if (pair.fixed === 'text') {
				// Text is fixed (#ffffff), flag the background color
				flagKey = pair.bg;
			} else {
				// Flag the text color row
				flagKey = pair.text.charAt(0) === '#' ? null : pair.text;
			}

			if (flagKey) {
				if (!worstRatios[flagKey] || ratio < worstRatios[flagKey].ratio) {
					worstRatios[flagKey] = { ratio: ratio, pass: pass, where: pair.where };
				}
			}
		}

		// Apply classes and ratio text
		for (var key in worstRatios) {
			var info = worstRatios[key];
			var row = document.querySelector('.mdw-cp-row[data-key="' + key + '"]');
			if (!row) continue;

			var ratioEl = row.querySelector('.mdw-cp-contrast-ratio');
			if (ratioEl) {
				ratioEl.textContent = info.ratio.toFixed(1) + ':1';
				ratioEl.classList.toggle('mdw-cp-contrast-pass', info.pass);
				ratioEl.classList.toggle('mdw-cp-contrast-fail-text', !info.pass);
			}

			if (!info.pass) {
				row.classList.add('mdw-cp-contrast-fail');
				row.setAttribute('data-contrast-ratio', info.ratio.toFixed(2));
			}
		}
	}

	function autoFixContrast() {
		var defs = config.colors;
		var changed = false;

		for (var i = 0; i < CONTRAST_PAIRS.length; i++) {
			var pair = CONTRAST_PAIRS[i];
			var textColor = resolveContrastColor(pair, 'text');
			var bgColor = resolveContrastColor(pair, 'bg');
			if (!textColor || !bgColor) continue;

			var ratio = contrastRatio(textColor, bgColor);
			if (ratio >= AA_THRESHOLD) continue;

			// Determine which color to adjust
			var adjustKey = null;
			if (pair.fixed === 'text') {
				// Can't adjust fixed text (#ffffff), adjust the background instead
				adjustKey = pair.bg;
			} else if (pair.text.charAt(0) !== '#') {
				adjustKey = pair.text;
			}

			if (!adjustKey) continue;

			// Skip source colors (primary/secondary) — only adjust derivatives
			if (!defs[adjustKey] || !defs[adjustKey].source) continue;
			// Skip manually overridden colors
			if (state.overrides[adjustKey]) continue;

			// Nudge lightness until contrast passes
			var adjustHsl = hexToHsl(state.colors[adjustKey]);
			var bgLum = relativeLuminance(bgColor);
			var isTextOnLightBg = (pair.fixed !== 'text' && adjustKey === pair.text);

			// If adjusting text on light bg, go darker; if adjusting bg under white text, go darker
			var direction = -1; // darker by default
			if (isTextOnLightBg) {
				// Text is on a light bg — make text darker
				direction = -1;
			} else {
				// Adjusting background — make it darker so white text has more contrast
				direction = -1;
			}

			var step = 0.5;
			var maxIterations = 200;
			var currentL = adjustHsl.l;

			for (var iter = 0; iter < maxIterations; iter++) {
				currentL += direction * step;
				if (currentL < 0) { currentL = 0; break; }
				if (currentL > 100) { currentL = 100; break; }

				var testHex = hslToHex(adjustHsl.h, adjustHsl.s, currentL);
				var newTextColor = isTextOnLightBg ? testHex : resolveContrastColor(pair, 'text');
				var newBgColor = isTextOnLightBg ? resolveContrastColor(pair, 'bg') : testHex;
				var newRatio = contrastRatio(newTextColor, newBgColor);

				if (newRatio >= AA_THRESHOLD) {
					state.colors[adjustKey] = testHex;
					// Mark as overridden so derivation doesn't overwrite the fix
					state.overrides[adjustKey] = true;
					changed = true;
					break;
				}
			}
		}

		if (changed) {
			syncInputs();
			rebuildPreview();
			showNotification('Contrast auto-fixed for WCAG AA');
		} else {
			showNotification('All pairs already pass WCAG AA');
		}
	}

	// --- UI sync ---

	function syncInputs() {
		var rows = document.querySelectorAll('.mdw-cp-row');
		rows.forEach(function (row) {
			var key = row.getAttribute('data-key');
			var picker = row.querySelector('.mdw-cp-picker');
			var hex = row.querySelector('.mdw-cp-hex');
			var badge = row.querySelector('.mdw-cp-badge-type');

			if (picker) picker.value = state.colors[key];
			if (hex) hex.value = state.colors[key];

			if (badge && config.colors[key].source) {
				if (state.overrides[key]) {
					badge.textContent = 'manual';
					badge.className = 'mdw-cp-badge-type mdw-cp-manual';
					row.classList.add('mdw-cp-overridden');
				} else {
					badge.textContent = 'auto';
					badge.className = 'mdw-cp-badge-type mdw-cp-auto';
					row.classList.remove('mdw-cp-overridden');
				}
			}
		});
	}

	// --- Event handling ---

	function onColorChange(key, value) {
		// Normalize
		value = value.toLowerCase();
		if (!/^#[0-9a-f]{6}$/.test(value)) return;

		state.colors[key] = value;

		var defs = config.colors;
		if (!defs[key].source) {
			// Source color changed: re-derive all children
			deriveAllFromSource(key);
		} else {
			// Derivative changed manually
			state.overrides[key] = true;
		}

		syncInputs();
		rebuildPreview();
	}

	function onLockToggle(key) {
		if (state.overrides[key]) {
			// Unlock: re-derive from source
			delete state.overrides[key];
			deriveColor(key);
		} else {
			// Lock: mark as overridden
			state.overrides[key] = true;
		}
		syncInputs();
		rebuildPreview();
	}

	function onSwap() {
		var tmp = state.colors.primary;
		state.colors.primary = state.colors.secondary;
		state.colors.secondary = tmp;

		deriveAllFromSource('primary');
		deriveAllFromSource('secondary');
		syncInputs();
		rebuildPreview();
	}

	function onModeChange(mode) {
		state.derivationMode = mode;

		// Update toggle button active states
		var btns = document.querySelectorAll('.mdw-cp-mode-btn');
		btns.forEach(function (btn) {
			btn.classList.toggle('mdw-cp-mode-active', btn.getAttribute('data-mode') === mode);
		});

		// Re-derive all non-overridden derivatives
		var defs = config.colors;
		for (var key in defs) {
			if (defs[key].source) {
				deriveColor(key);
			}
		}

		syncInputs();
		rebuildPreview();
	}

	function onReset() {
		for (var key in state.originals) {
			state.colors[key] = state.originals[key];
		}
		state.overrides = {};
		state.derivationMode = 'delta';

		// Reset mode toggle UI
		var btns = document.querySelectorAll('.mdw-cp-mode-btn');
		btns.forEach(function (btn) {
			btn.classList.toggle('mdw-cp-mode-active', btn.getAttribute('data-mode') === 'delta');
		});

		syncInputs();
		removePreview();
	}

	function onExport() {
		var data = {
			colors: {},
			shellCommands: {}
		};

		for (var key in state.colors) {
			data.colors[key] = state.colors[key];
		}

		// Generate shell command hints
		data.shellCommands.rebrandFiles = './rebrand-files.sh --primary "' + state.colors.primary + '" --secondary "' + state.colors.secondary + '"';
		data.shellCommands.rebrandColors = './rebrand-colors.sh --primary "' + state.colors.primary + '" --secondary "' + state.colors.secondary + '"';

		var modal = document.getElementById('mdw-cp-export-modal');
		var textarea = document.getElementById('mdw-cp-export-text');
		textarea.value = JSON.stringify(data, null, 2);
		modal.style.display = 'flex';
	}

	function onApply() {
		var btn = document.getElementById('mdw-cp-apply');

		if (!btn.classList.contains('mdw-cp-confirm')) {
			btn.textContent = 'Confirm Apply?';
			btn.classList.add('mdw-cp-confirm');
			setTimeout(function () {
				btn.textContent = 'Apply to Theme';
				btn.classList.remove('mdw-cp-confirm');
			}, 3000);
			return;
		}

		btn.textContent = 'Applying...';
		btn.disabled = true;

		var formData = new FormData();
		formData.append('action', 'mdw_cp_apply_colors');
		formData.append('nonce', config.nonce);
		for (var key in state.colors) {
			formData.append(key, state.colors[key]);
		}

		fetch(config.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			if (data.success) {
				btn.textContent = 'Applied!';
				btn.classList.remove('mdw-cp-confirm');
				btn.classList.add('mdw-cp-success');
				showNotification('Colors applied to style.css');
				setTimeout(function () { location.reload(); }, 1500);
			} else {
				btn.textContent = 'Error!';
				btn.disabled = false;
				showNotification('Error: ' + (data.data || 'Unknown error'), true);
				setTimeout(function () {
					btn.textContent = 'Apply to Theme';
					btn.classList.remove('mdw-cp-confirm');
				}, 2000);
			}
		})
		.catch(function () {
			btn.textContent = 'Error!';
			btn.disabled = false;
			showNotification('Network error', true);
			setTimeout(function () {
				btn.textContent = 'Apply to Theme';
				btn.classList.remove('mdw-cp-confirm');
			}, 2000);
		});
	}

	function showNotification(msg, isError) {
		var el = document.createElement('div');
		el.className = 'mdw-cp-notification';
		if (isError) el.style.background = '#d9534f';
		el.textContent = msg;
		document.body.appendChild(el);
		setTimeout(function () { el.remove(); }, 3000);
	}

	// --- Bind events ---

	function bindEvents() {
		// Toggle panel
		document.getElementById('mdw-cp-toggle').addEventListener('click', function () {
			document.getElementById('mdw-cp-panel').classList.toggle('mdw-cp-closed');
		});

		// Color pickers
		document.querySelectorAll('.mdw-cp-picker').forEach(function (el) {
			el.addEventListener('input', function () {
				onColorChange(this.getAttribute('data-key'), this.value);
			});
		});

		// Hex inputs
		document.querySelectorAll('.mdw-cp-hex').forEach(function (el) {
			el.addEventListener('change', function () {
				var val = this.value.trim();
				if (val.length === 6 && val[0] !== '#') val = '#' + val;
				onColorChange(this.getAttribute('data-key'), val);
			});
		});

		// Lock toggles
		document.querySelectorAll('.mdw-cp-lock').forEach(function (el) {
			el.addEventListener('click', function () {
				onLockToggle(this.getAttribute('data-key'));
			});
		});

		// Swap primary/secondary
		document.getElementById('mdw-cp-swap').addEventListener('click', onSwap);

		// Derivation mode toggle
		document.querySelectorAll('.mdw-cp-mode-btn').forEach(function (el) {
			el.addEventListener('click', function () {
				onModeChange(this.getAttribute('data-mode'));
			});
		});

		// Reset
		document.getElementById('mdw-cp-reset').addEventListener('click', onReset);

		// Export
		document.getElementById('mdw-cp-export').addEventListener('click', onExport);

		// Auto-fix contrast
		document.getElementById('mdw-cp-autofix').addEventListener('click', autoFixContrast);

		// Apply
		document.getElementById('mdw-cp-apply').addEventListener('click', onApply);

		// Modal close
		document.getElementById('mdw-cp-modal-close').addEventListener('click', function () {
			document.getElementById('mdw-cp-export-modal').style.display = 'none';
		});

		// Copy
		document.getElementById('mdw-cp-copy').addEventListener('click', function () {
			var textarea = document.getElementById('mdw-cp-export-text');
			textarea.select();
			navigator.clipboard.writeText(textarea.value).then(function () {
				showNotification('Copied to clipboard');
			});
		});

		// Close modal on backdrop click
		document.getElementById('mdw-cp-export-modal').addEventListener('click', function (e) {
			if (e.target === this) this.style.display = 'none';
		});
	}

	// --- Init ---

	document.addEventListener('DOMContentLoaded', function () {
		initState();
		bindEvents();
		syncInputs();
		checkContrast();
	});

})();
