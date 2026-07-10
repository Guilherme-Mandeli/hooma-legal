(function(wp) {
	var el = wp.element.createElement;
	var registerPlugin = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;

	function HoomaLegalDocumentSettingsPanel() {
		// Get current post type
		var postType = wp.data.useSelect(function(select) {
			return select('core/editor').getCurrentPostType();
		}, []);

		// Render only on hooma_legal_doc CPT
		if (postType !== 'hooma_legal_doc') {
			return null;
		}

		// Retrieve post metadata
		var meta = wp.data.useSelect(function(select) {
			return select('core/editor').getEditedPostAttribute('meta') || {};
		}, []);

		var editPost = wp.data.useDispatch('core/editor').editPost;

		var version      = meta._hooma_legal_version || '';
		var revisionDate = meta._hooma_legal_revision_date || '';
		var changelog    = meta._hooma_legal_changelog || '';
		var documentType = meta._hooma_legal_document_type || '';

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'hooma-legal-settings-panel',
				title: 'Opciones de Hooma Legal',
				className: 'hooma-legal-settings-panel'
			},
			el(TextControl, {
				label: 'Tipo de Documento (Slug para API)',
				help: 'Identificador único para el API (ej: privacy_policy, terms_conditions)',
				value: documentType,
				placeholder: 'ej: privacy_policy',
				onChange: function(newVal) {
					var newMeta = Object.assign({}, meta, { _hooma_legal_document_type: newVal });
					editPost({ meta: newMeta });
				}
			}),
			el(TextControl, {
				label: 'Versión del Documento',
				value: version,
				placeholder: 'Ej: 1.0.0',
				onChange: function(newVal) {
					var newMeta = Object.assign({}, meta, { _hooma_legal_version: newVal });
					editPost({ meta: newMeta });
				}
			}),
			el(TextControl, {
				label: 'Fecha de Última Revisión',
				value: revisionDate,
				placeholder: 'YYYY-MM-DD',
				onChange: function(newVal) {
					var newMeta = Object.assign({}, meta, { _hooma_legal_revision_date: newVal });
					editPost({ meta: newMeta });
				}
			}),
			el(TextareaControl, {
				label: 'Notas de Cambio (Changelog)',
				help: 'Describe los cambios introducidos. Se guardará al publicar.',
				value: changelog,
				onChange: function(newVal) {
					var newMeta = Object.assign({}, meta, { _hooma_legal_changelog: newVal });
					editPost({ meta: newMeta });
				}
			})
		);
	}

	registerPlugin('hooma-legal-settings-plugin', {
		render: HoomaLegalDocumentSettingsPanel,
		icon: 'shield'
	});
})(window.wp);
