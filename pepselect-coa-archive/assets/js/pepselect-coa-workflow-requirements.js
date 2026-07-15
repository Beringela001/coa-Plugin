(function ($) {
	'use strict';
	var config = window.PepSelectCOAWorkflowRequirements || {};
	var icons = { complete: '\u2713', missing: '!', 'not-required': '\u2014', optional: '\u25cb' };

	function value(key) {
		var field = $('[data-key="' + key + '"]').first();
		return field.length ? String(field.find('select, input').first().val() || '') : '';
	}

	function modelKey() {
		var stage = value('field_ps_coa_test_workflow_stage') || 'vendor-vetting';
		var status = value('field_ps_coa_test_status') || 'pending';
		var laboratory = value('field_ps_coa_test_testing_lab');
		var scope = laboratory === 'ils-labs' ? 'ils-labs' : (laboratory === 'other' ? 'other' : 'default');
		return stage + '|' + status + '|' + scope;
	}

	function render() {
		var root = $('[data-ps-coa-requirements]').first();
		var model = config.models && config.models[modelKey()];
		if (!root.length || !model) { return; }
		root.find('[data-ps-coa-requirements-stage]').text(model.stage);
		root.find('[data-ps-coa-requirements-status]').text(model.status);
		root.find('[data-ps-coa-requirements-guidance]').text(model.guidance);
		var list = root.find('[data-ps-coa-requirements-list]').empty();
		(model.items || []).forEach(function (item) {
			var row = $('<li>').addClass('ps-coa-workflow-requirements__item ps-coa-workflow-requirements__item--' + item.state);
			$('<span>').addClass('ps-coa-workflow-requirements__icon').attr('aria-hidden', 'true').text(icons[item.state] || '').appendTo(row);
			$('<span>').addClass('ps-coa-workflow-requirements__name').text(item.label).appendTo(row);
			$('<span>').addClass('ps-coa-workflow-requirements__state').text((config.states && config.states[item.state]) || item.state).appendTo(row);
			row.appendTo(list);
		});
	}

	$(document).on('change', '[data-key="field_ps_coa_test_workflow_stage"] select, [data-key="field_ps_coa_test_status"] select, [data-key="field_ps_coa_test_testing_lab"] select', render);
	$(render);
	if (window.acf && window.acf.addAction) { window.acf.addAction('ready append', render); }
}(jQuery));
