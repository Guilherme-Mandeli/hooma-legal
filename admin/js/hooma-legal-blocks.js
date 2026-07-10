(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var el = wp.element.createElement;
	var SelectControl = wp.components.SelectControl;
	var InspectorControls = wp.blockEditor.InspectorControls || wp.editor.InspectorControls;
	var TextControl = wp.components.TextControl;
	var useSelect = wp.data.useSelect;

	// 1. Dynamic Link Block
	registerBlockType('hooma-legal/dynamic-link', {
		title: 'Enlace Legal Dinámico',
		icon: 'admin-links',
		category: 'common',
		attributes: {
			documentId: {
				type: 'string',
				default: ''
			},
			linkText: {
				type: 'string',
				default: ''
			}
		},
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			// Fetch legal documents from WP REST API
			var documents = useSelect(function(select) {
				return select('core').getEntityRecords('postType', 'hooma_legal_doc', { per_page: -1 });
			}, []);

			var options = [{ value: '', label: 'Seleccionar Documento...' }];
			if (documents) {
				documents.forEach(function(doc) {
					options.push({ value: doc.id.toString(), label: doc.title.rendered || doc.slug });
				});
			}

			// Pre-fill text with title on change if empty
			if (documents && attributes.documentId && !attributes.linkText) {
				var selectedDoc = documents.find(function(d) { return d.id.toString() === attributes.documentId; });
				if (selectedDoc) {
					setAttributes({ linkText: selectedDoc.title.rendered });
				}
			}

			return el('div', { className: 'hooma-legal-block-edit' },
				el(InspectorControls, {},
					el(wp.components.PanelBody, { title: 'Ajustes del Enlace' },
						el(SelectControl, {
							label: 'Documento Legal',
							value: attributes.documentId,
							options: options,
							onChange: function(val) {
								setAttributes({ documentId: val });
							}
						}),
						el(TextControl, {
							label: 'Texto del Enlace',
							value: attributes.linkText,
							onChange: function(val) {
								setAttributes({ linkText: val });
							}
						})
					)
				),
				el('span', { style: { textDecoration: 'underline', color: '#007cba', cursor: 'pointer' } },
					attributes.linkText || 'Seleccione un documento legal...'
				)
			);
		},
		save: function() {
			return null; // Rendered dynamically in PHP
		}
	});

	// 2. Reusable Legal Block Selector Block
	registerBlockType('hooma-legal/reusable-block', {
		title: 'Bloque Legal Reutilizable',
		icon: 'editor-table',
		category: 'common',
		attributes: {
			blockId: {
				type: 'string',
				default: ''
			}
		},
		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			// Fetch legal blocks
			var blocks = useSelect(function(select) {
				return select('core').getEntityRecords('postType', 'hooma_legal_block', { per_page: -1 });
			}, []);

			var options = [{ value: '', label: 'Seleccionar Bloque Reutilizable...' }];
			if (blocks) {
				blocks.forEach(function(b) {
					options.push({ value: b.id.toString(), label: b.title.rendered || b.slug });
				});
			}

			return el('div', { className: 'hooma-legal-block-edit', style: { border: '1px dashed #ccc', padding: '15px', background: '#f9f9f9' } },
				el(InspectorControls, {},
					el(wp.components.PanelBody, { title: 'Seleccionar Bloque' },
						el(SelectControl, {
							label: 'Bloque Reutilizable',
							value: attributes.blockId,
							options: options,
							onChange: function(val) {
								setAttributes({ blockId: val });
							}
						})
					)
				),
				el('strong', {}, 'Bloque Legal Reutilizable: '),
				attributes.blockId ? (options.find(function(o) { return o.value === attributes.blockId; })?.label || 'Bloque seleccionado') : 'Ninguno'
			);
		},
		save: function() {
			return null; // Rendered dynamically in PHP
		}
	});

})(window.wp);
