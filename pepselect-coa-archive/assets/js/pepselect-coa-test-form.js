( function ( window, document, $ ) {
	'use strict';

	const config = window.PepSelectCOATestForm || { available: {}, resultFields: [], guidance: {} };
	const selector = '#acf-group_ps_coa_test_details';

	function field( name ) { return document.querySelector( `${ selector } .acf-field[data-name="${ name }"]` ); }
	function value( name ) {
		const container = field( name );
		if ( ! container ) { return ''; }
		const checked = container.querySelector( 'input[type="radio"]:checked, input[type="checkbox"]:checked' );
		const input = checked || container.querySelector( 'select, textarea, input:not([type="hidden"]):not([type="radio"]):not([type="checkbox"])' );
		return input ? String( 'checkbox' === input.type ? ( input.checked ? '1' : '0' ) : input.value || '' ) : '';
	}
	function input( name ) { const container = field( name ); return container ? container.querySelector( 'select, textarea, input:not([type="hidden"])' ) : null; }
	function setValue( name, next ) { const control = input( name ); if ( control ) { control.value = next; } }
	function applyFentanyl() {
		const status = value( 'fentanyl_status' );
		setValue( 'fentanyl_method', 'Immunoassay' );
		setValue( 'fentanyl_specification', 'Immunoassay, 50 ng/mL cutoff' );
		setValue( 'fentanyl_result', 'pass' === status ? 'Not detected' : ( 'fail' === status ? 'Detected' : '' ) );
	}

	function setDisabled( container, disabled ) {
		if ( ! container ) { return; }
		container.classList.toggle( 'ps-coa-stage-disabled', disabled );
		container.setAttribute( 'aria-disabled', disabled ? 'true' : 'false' );
		container.querySelectorAll( 'input, select, textarea, button' ).forEach( input => {
			if ( input.closest( '.acf-tab-wrap' ) ) { return; }
			input.disabled = disabled;
		} );
	}

	function setBatchPhotoRequirement( stage ) {
		const container = field( 'batch_vial_photo' );
		if ( ! container ) { return; }
		const required = 'in-testing' === stage || 'complete' === stage;
		container.classList.toggle( 'ps-coa-stage-required', required );
		container.querySelectorAll( 'input' ).forEach( input => input.setAttribute( 'aria-required', required ? 'true' : 'false' ) );
		let notice = container.querySelector( '.ps-coa-batch-photo-requirement' );
		if ( required && ! notice ) {
			notice = document.createElement( 'p' ); notice.className = 'ps-coa-batch-photo-requirement'; notice.setAttribute( 'role', 'note' ); notice.textContent = config.batchPhotoRequired || '';
			const label = container.querySelector( '.acf-label' ); if ( label ) { label.appendChild( notice ); }
		}
		if ( notice ) { notice.hidden = ! required; }
	}

	function guidance( stage ) {
		const group = document.querySelector( `${ selector } .acf-fields` );
		if ( ! group ) { return; }
		let notice = group.querySelector( '.ps-coa-stage-guidance' );
		if ( ! notice ) { notice = document.createElement( 'div' ); notice.className = 'ps-coa-stage-guidance'; notice.setAttribute( 'role', 'status' ); group.prepend( notice ); }
		const label = config.stages && config.stages[ stage ] ? config.stages[ stage ] : stage;
		notice.innerHTML = '';
		const strong = document.createElement( 'strong' ); strong.textContent = label || 'Workflow stage';
		const span = document.createElement( 'span' ); span.textContent = config.guidance[ stage ] || '';
		notice.append( strong, span );
	}

	function legacyNotice() {
		if ( ! config.legacyStatus ) { return; }
		const statusField = field( 'coa_status' );
		if ( ! statusField || statusField.querySelector( '.ps-coa-legacy-notice' ) ) { return; }
		const notice = document.createElement( 'p' ); notice.className = 'ps-coa-legacy-notice'; notice.setAttribute( 'role', 'note' ); notice.textContent = `This record retains the legacy ${ config.legacyStatus } status. It is read only and will not be converted automatically.`;
		statusField.appendChild( notice );
		setDisabled( statusField, true );
	}

	function applyStage() {
		const stage = value( 'workflow_stage' ) || 'vendor-vetting';
		const partial = '1' === value( 'partial_results_available' );
		const status = value( 'coa_status' );
		const allowed = config.available[ stage ] || [];
		document.querySelectorAll( `${ selector } .acf-field[data-name]` ).forEach( container => {
			const name = container.dataset.name || '';
			if ( name.startsWith( 'tab_' ) ) { return; }
			let enabled = allowed.includes( '*' ) || allowed.includes( name );
			if ( 'in-testing' === stage && partial && config.resultFields.includes( name ) ) { enabled = true; }
			if ( 'release_decision_note' === name && 'failed' === status ) { enabled = true; }
			if ( 'coa_status' === name && config.legacyStatus ) { enabled = false; }
			setDisabled( container, ! enabled );
		} );
		setBatchPhotoRequirement( stage ); guidance( stage ); legacyNotice();
	}

	function bind() {
		if ( ! document.querySelector( selector ) ) { return; }
		$( document ).off( '.pepSelectCOAStage' ).on( 'change.pepSelectCOAStage', `${ selector } [data-name="workflow_stage"] :input, ${ selector } [data-name="coa_status"] :input, ${ selector } [data-name="partial_results_available"] :input`, applyStage );
		$( document ).on( 'change.pepSelectCOAStage', `${ selector } [data-name="fentanyl_status"] :input`, applyFentanyl );
		applyStage(); applyFentanyl();
	}

	$( bind );
	if ( window.acf && window.acf.addAction ) { window.acf.addAction( 'ready', bind ); window.acf.addAction( 'append', () => { applyStage(); applyFentanyl(); } ); }
	window.PepSelectCOATestFormController = { applyStage, applyFentanyl, setDisabled, value };
} )( window, document, jQuery );
