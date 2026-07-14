( function ( window, document, $ ) {
	'use strict';

	const config = window.PepSelectCOAImporterConfig || { fields: {}, compounds: [], messages: {} };
	const dateFields = new Set( [ 'test_date', 'expected_coa_date', 'date_received' ] );
	const booleanFields = new Set( [ 'is_current' ] );
	const integerFields = new Set( [ 'compound_id', 'vials_submitted', 'vials_tested' ] );
	const numberFields = new Set( [ 'claimed_content', 'vials_submitted', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'purity_percentage' ] );
	const resultFields = new Set( [ 'purity_status', 'identity_status', 'endotoxin_status', 'heavy_metals_status', 'sterility_status' ] );
	const urlFields = new Set( [ 'lab_report_url', 'pending_lab_url', 'lab_verification_url' ] );
	const ignoredFields = new Set( [ 'coa_pdf_id', 'coa_page_images' ] );
	const compoundAliases = new Set( [ 'compound_slug', 'compound_display_name' ] );
	const coaStatuses = new Set( [ 'pending', 'in-testing', 'vendor-vetting', 'approved', 'failed', 'archived', 'superseded' ] );
	const resultStatuses = new Set( [ 'pass', 'fail', 'pending', 'not-tested', 'not-applicable', 'reported' ] );
	let preview = null;
	let snapshot = null;

	function parseCsv( text ) {
		const rows = [];
		let row = [], value = '', quoted = false;
		for ( let index = 0; index < text.length; index++ ) {
			const char = text[ index ];
			if ( quoted ) {
				if ( '"' === char && '"' === text[ index + 1 ] ) { value += '"'; index++; }
				else if ( '"' === char ) { quoted = false; }
				else { value += char; }
			} else if ( '"' === char ) { quoted = true; }
			else if ( ',' === char ) { row.push( value ); value = ''; }
			else if ( '\n' === char || '\r' === char ) {
				if ( '\r' === char && '\n' === text[ index + 1 ] ) { index++; }
				row.push( value ); rows.push( row ); row = []; value = '';
			} else { value += char; }
		}
		if ( quoted ) { throw new Error( 'CSV contains an unterminated quoted value.' ); }
		if ( value.length || row.length ) { row.push( value ); rows.push( row ); }
		return rows.filter( cells => cells.some( cell => String( cell ).trim() !== '' ) );
	}

	function normalizeDate( value ) {
		const raw = String( value ).trim();
		let year, month, day;
		if ( /^\d{8}$/.test( raw ) ) { year = raw.slice( 0, 4 ); month = raw.slice( 4, 6 ); day = raw.slice( 6, 8 ); }
		else if ( /^\d{4}-\d{2}-\d{2}$/.test( raw ) ) { [ year, month, day ] = raw.split( '-' ); }
		else if ( /^\d{2}\/\d{2}\/\d{4}$/.test( raw ) ) { [ month, day, year ] = raw.split( '/' ); }
		else { return null; }
		const date = new Date( Date.UTC( Number( year ), Number( month ) - 1, Number( day ) ) );
		if ( date.getUTCFullYear() !== Number( year ) || date.getUTCMonth() + 1 !== Number( month ) || date.getUTCDate() !== Number( day ) ) { return null; }
		return `${ year }${ month }${ day }`;
	}

	function normalizeBoolean( value ) {
		const normalized = String( value ).trim().toLowerCase();
		if ( [ '1', 'true', 'yes', 'on' ].includes( normalized ) ) { return '1'; }
		if ( [ '0', 'false', 'no', 'off' ].includes( normalized ) ) { return '0'; }
		return null;
	}

	function normalizeStatus( value, allowed ) {
		const normalized = String( value ).trim().toLowerCase().replace( /[ _]+/g, '-' );
		return allowed.has( normalized ) ? normalized : null;
	}

	function normalizeLab( value ) {
		const raw = String( value ).trim();
		const normalized = raw.toLowerCase().replace( /[ _]+/g, '-' );
		const labels = { 'ils-labs': 'ils-labs', 'ils-laboratories': 'ils-labs', 'janoshik': 'janoshik', 'janoshik-analytical': 'janoshik', 'mz-biotech': 'mz-biotech', 'mz-biolabs': 'mz-biotech', 'other': 'other' };
		return labels[ normalized ] ? { value: labels[ normalized ], other: '' } : { value: 'other', other: raw };
	}

	function normalizeValue( name, value ) {
		const raw = String( value ).trim();
		if ( '' === raw ) { return { valid: true, value: '' }; }
		if ( dateFields.has( name ) ) { const date = normalizeDate( raw ); return { valid: null !== date, value: date || raw, error: 'Invalid date' }; }
		if ( booleanFields.has( name ) ) { const bool = normalizeBoolean( raw ); return { valid: null !== bool, value: bool || raw, error: 'Invalid boolean' }; }
		if ( 'coa_status' === name ) { const status = normalizeStatus( raw, coaStatuses ); return { valid: null !== status, value: status || raw, error: 'Invalid COA status' }; }
		if ( resultFields.has( name ) ) { const status = normalizeStatus( raw, resultStatuses ); return { valid: null !== status, value: status || raw, error: 'Invalid result status' }; }
		if ( 'testing_lab' === name ) { const lab = normalizeLab( raw ); return { valid: true, value: lab.value, other: lab.other }; }
		if ( integerFields.has( name ) && ! /^-?\d+$/.test( raw ) ) { return { valid: false, value: raw, error: 'Invalid integer' }; }
		if ( numberFields.has( name ) && ! Number.isFinite( Number( raw ) ) ) { return { valid: false, value: raw, error: 'Invalid number' }; }
		if ( 'purity_percentage' === name && ( Number( raw ) < 0 || Number( raw ) > 100 ) ) { return { valid: false, value: raw, error: 'Purity must be between 0 and 100' }; }
		if ( 'vials_tested' === name && Number( raw ) < 1 ) { return { valid: false, value: raw, error: 'Vials tested must be at least 1' }; }
		if ( urlFields.has( name ) ) {
			try { const url = new URL( raw ); if ( ! [ 'http:', 'https:' ].includes( url.protocol ) ) { throw new Error(); } }
			catch ( error ) { return { valid: false, value: raw, error: 'Invalid HTTP or HTTPS URL' }; }
		}
		return { valid: true, value: raw };
	}

	function matchCompound( row ) {
		const criteria = [ [ 'compound_id', 'id' ], [ 'compound_slug', 'slug' ], [ 'compound_display_name', 'displayName' ] ];
		let attempted = '';
		for ( const [ column, property ] of criteria ) {
			if ( ! row[ column ] ) { continue; }
			attempted = column;
			const needle = String( row[ column ] ).trim().toLowerCase();
			const matches = config.compounds.filter( compound => String( compound[ property ] ).trim().toLowerCase() === needle );
			if ( 1 === matches.length ) { return { match: matches[ 0 ] }; }
			if ( matches.length > 1 ) { return { error: `Multiple compounds match ${ column }. Select the compound manually.` }; }
		}
		return attempted ? { warning: `No compound matches the supplied identifiers. Select the compound manually.` } : { warning: 'No compound identifier was provided. Select the compound manually.' };
	}

	function fieldObject( name ) {
		const key = config.fields[ name ];
		return key && window.acf && window.acf.getField ? window.acf.getField( key ) : null;
	}

	function getFieldValue( name ) {
		const field = fieldObject( name );
		if ( field && 'function' === typeof field.val ) { const value = field.val(); return Array.isArray( value ) ? value.join( ',' ) : String( value || '' ); }
		const container = document.querySelector( `[data-key="${ config.fields[ name ] || '' }"]` );
		const input = container && container.querySelector( 'input:not([type="hidden"]), select, textarea' );
		return input ? ( 'checkbox' === input.type ? ( input.checked ? '1' : '0' ) : String( input.value || '' ) ) : '';
	}

	function setFieldValue( name, value, compound ) {
		const field = fieldObject( name );
		if ( field ) {
			if ( 'compound_id' === name && compound && field.$input ) { const select = field.$input(); if ( ! select.find( `option[value="${ value }"]` ).length ) { select.append( new Option( compound.displayName || compound.title, value, true, true ) ); } }
			if ( 'function' === typeof field.val ) { field.val( value ); }
			if ( field.$input ) { field.$input().trigger( 'change' ); }
			return;
		}
		const container = document.querySelector( `[data-key="${ config.fields[ name ] || '' }"]` );
		const input = container && container.querySelector( 'input:not([type="hidden"]), select, textarea' );
		if ( ! input ) { return; }
		if ( 'checkbox' === input.type ) { input.checked = '1' === String( value ); } else { input.value = value; }
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	function buildPreview( headers, values ) {
		const row = {}; headers.forEach( ( header, index ) => { row[ header ] = values[ index ] === undefined ? '' : values[ index ]; } );
		const compoundResult = matchCompound( row );
		const items = [];
		for ( const header of headers ) {
			if ( compoundAliases.has( header ) ) { items.push( { column: header, imported: row[ header ], current: getFieldValue( 'compound_id' ), result: compoundResult.error ? 'Invalid' : ( compoundResult.match ? 'Will Populate' : 'No Matching Field' ), error: compoundResult.error || compoundResult.warning } ); continue; }
			if ( ignoredFields.has( header ) ) { items.push( { column: header, imported: row[ header ], current: '', result: 'No Matching Field', error: 'PDF and gallery fields must be uploaded manually.' } ); continue; }
			if ( ! config.fields[ header ] ) { items.push( { column: header, imported: row[ header ], current: '', result: 'Unknown Column' } ); continue; }
			const normalized = normalizeValue( header, row[ header ] ); const current = getFieldValue( header );
			items.push( { column: header, imported: row[ header ], value: normalized.value, current, result: normalized.valid ? ( String( current ) === String( normalized.value ) ? 'Unchanged' : ( current ? 'Will Replace' : 'Will Populate' ) ) : 'Invalid', error: normalized.error, other: normalized.other } );
		}
		const labItem = items.find( item => 'testing_lab' === item.column && item.other );
		if ( labItem ) {
			let otherItem = items.find( item => 'other_testing_lab' === item.column );
			if ( ! otherItem ) { otherItem = { column: 'other_testing_lab', imported: labItem.other, current: getFieldValue( 'other_testing_lab' ) }; items.push( otherItem ); }
			if ( ! String( otherItem.imported || '' ).trim() ) { otherItem.imported = labItem.other; }
			otherItem.value = otherItem.imported; otherItem.result = otherItem.current === otherItem.value ? 'Unchanged' : ( otherItem.current ? 'Will Replace' : 'Will Populate' );
		}
		if ( compoundResult.match ) {
			const existing = items.find( item => 'compound_id' === item.column );
			if ( existing ) { existing.value = String( compoundResult.match.id ); existing.compound = compoundResult.match; existing.result = existing.current && existing.current !== existing.value ? 'Will Replace' : ( existing.current === existing.value ? 'Unchanged' : 'Will Populate' ); }
			else { items.unshift( { column: 'compound_id', imported: String( compoundResult.match.id ), value: String( compoundResult.match.id ), current: getFieldValue( 'compound_id' ), result: 'Will Populate', compound: compoundResult.match } ); }
		}
		else {
			const compoundItem = items.find( item => 'compound_id' === item.column );
			if ( compoundItem ) { compoundItem.result = compoundResult.error ? 'Invalid' : 'No Matching Field'; compoundItem.error = compoundResult.error || compoundResult.warning; }
		}
		if ( compoundResult.error || compoundResult.warning ) { items.unshift( { column: 'compound_match', imported: '', current: getFieldValue( 'compound_id' ), result: compoundResult.error ? 'Invalid' : 'No Matching Field', error: compoundResult.error || compoundResult.warning } ); }
		return items;
	}

	function renderPreview( items ) {
		const host = document.getElementById( 'ps-coa-csv-preview-table' ); host.textContent = '';
		const table = document.createElement( 'table' ); table.className = 'widefat striped';
		const head = table.createTHead().insertRow(); [ 'CSV Column', 'Imported Value', 'Current Form Value', 'Result' ].forEach( label => { const th = document.createElement( 'th' ); th.textContent = label; head.appendChild( th ); } );
		const body = table.createTBody(); items.forEach( item => { const tr = body.insertRow(); [ item.column, item.imported, item.current, item.error ? `${ item.result }: ${ item.error }` : item.result ].forEach( value => { const td = tr.insertCell(); td.textContent = value || ''; } ); } ); host.appendChild( table );
	}

	function previewText( text ) {
		const rows = parseCsv( text );
		if ( 2 !== rows.length ) { throw new Error( config.messages.oneRow || 'CSV must contain one header and one data row.' ); }
		const headers = rows[ 0 ].map( value => String( value ).trim().toLowerCase() );
		if ( headers.some( ( value, index ) => ! value || headers.indexOf( value ) !== index ) ) { throw new Error( 'CSV headers must be non-empty and unique.' ); }
		return buildPreview( headers, rows[ 1 ] );
	}

	function applyPreview( items ) {
		if ( items.some( item => 'Invalid' === item.result ) ) { throw new Error( 'Resolve invalid imported values before applying the CSV.' ); }
		if ( items.some( item => 'Will Replace' === item.result ) && ! window.confirm( config.messages.confirmReplace || 'Some existing values will be replaced. Continue?' ) ) { return false; }
		snapshot = {};
		items.forEach( item => { if ( config.fields[ item.column ] && ! [ 'Unknown Column', 'No Matching Field', 'Unchanged' ].includes( item.result ) ) { snapshot[ item.column ] = getFieldValue( item.column ); setFieldValue( item.column, item.value, item.compound ); } } );
		return true;
	}

	function clearImportedValues() {
		if ( ! snapshot ) { return; }
		Object.keys( snapshot ).forEach( name => setFieldValue( name, snapshot[ name ] ) ); snapshot = null;
	}

	function message( text, error ) { const host = document.getElementById( 'ps-coa-csv-message' ); host.textContent = text || ''; host.className = error ? 'notice notice-error inline' : 'notice notice-info inline'; }

	$( function () {
		const file = document.getElementById( 'ps-coa-csv-file' );
		if ( ! file ) { return; }
		const previewButton = document.getElementById( 'ps-coa-csv-preview' ); const applyButton = document.getElementById( 'ps-coa-csv-apply' ); const clearButton = document.getElementById( 'ps-coa-csv-clear' );
		previewButton.addEventListener( 'click', function () {
			if ( ! file.files.length ) { message( 'Choose a CSV file first.', true ); return; }
			if ( file.files[ 0 ].size > Number( file.dataset.maxSize ) ) { message( config.messages.tooLarge, true ); return; }
			const reader = new FileReader(); reader.onload = function () { try { preview = previewText( String( reader.result || '' ).replace( /^\uFEFF/, '' ) ); renderPreview( preview ); applyButton.disabled = preview.some( item => 'Invalid' === item.result ); message( 'CSV preview is ready. Review every value before applying.', false ); } catch ( error ) { preview = null; applyButton.disabled = true; message( error.message, true ); } }; reader.onerror = function () { message( 'The CSV file could not be read.', true ); }; reader.readAsText( file.files[ 0 ] );
		} );
		applyButton.addEventListener( 'click', function () { try { if ( preview && applyPreview( preview ) ) { clearButton.disabled = false; message( 'Imported values were applied to this form. Review and save manually.', false ); } } catch ( error ) { message( error.message, true ); } } );
		clearButton.addEventListener( 'click', function () { clearImportedValues(); clearButton.disabled = true; message( 'Imported values were restored to their pre-import state.', false ); } );
	} );

	window.PepSelectCOAImporter = { parseCsv, normalizeDate, normalizeBoolean, normalizeStatus, normalizeLab, normalizeValue, matchCompound, previewText, applyPreview, clearImportedValues };
} )( window, document, jQuery );
