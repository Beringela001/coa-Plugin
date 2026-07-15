const assert = require( 'assert' );

const inputs = {
	field_batch_number: {
		type: 'text',
		value: 'EXISTING',
		dispatchEvent: () => {}
	},
	field_fentanyl_status: { type: 'text', value: '', dispatchEvent: () => {} },
	field_fentanyl_result: { type: 'text', value: '', dispatchEvent: () => {} },
	field_fentanyl_method: { type: 'text', value: '', dispatchEvent: () => {} },
	field_fentanyl_specification: { type: 'text', value: '', dispatchEvent: () => {} },
	field_fentanyl_notes: { type: 'text', value: '', dispatchEvent: () => {} }
};

global.window = global;
global.document = {
	getElementById: () => null,
	querySelector: ( selector ) => {
		const match = selector.match( /\[data-key="([^"]+)"\]/ );
		const input = match ? inputs[ match[ 1 ] ] || null : null;
		return input ? { querySelector: () => input } : null;
	}
};
global.Event = class Event {};
let confirmations = 0;
global.confirm = () => { confirmations++; return true; };
global.jQuery = ( callback ) => { if ( 'function' === typeof callback ) { callback(); } };
global.PepSelectCOAImporterConfig = {
	fields: {
		batch_number: 'field_batch_number',
		purity_percentage: 'field_purity_percentage',
		testing_lab: 'field_testing_lab',
		other_testing_lab: 'field_other_testing_lab',
		lab_report_url: 'field_lab_report_url',
		fentanyl_status: 'field_fentanyl_status',
		fentanyl_result: 'field_fentanyl_result',
		fentanyl_method: 'field_fentanyl_method',
		fentanyl_specification: 'field_fentanyl_specification',
		fentanyl_notes: 'field_fentanyl_notes'
	},
	compounds: [
		{ id: 1, slug: 'reta-30', displayName: 'Retatrutide 30mg', title: 'Reta A' },
		{ id: 2, slug: 'reta-20', displayName: 'Retatrutide 20mg', title: 'Reta B' },
		{ id: 3, slug: 'other-reta', displayName: 'Retatrutide 20mg', title: 'Reta C' }
	],
	messages: { oneRow: 'one row only', confirmReplace: 'replace?' }
};

require( '../assets/js/coa-test-importer.js' );
const api = global.PepSelectCOAImporter;

assert.deepStrictEqual( api.parseCsv( 'batch_number,purity_percentage\n"LOT, 7",99.8\n\n' ), [ [ 'batch_number', 'purity_percentage' ], [ 'LOT, 7', '99.8' ] ] );
assert.throws( () => api.previewText( 'batch_number\nA\nB' ), /one row only/ );
assert.strictEqual( api.normalizeDate( '2026-07-13' ), '20260713' );
assert.strictEqual( api.normalizeDate( '20260713' ), '20260713' );
assert.strictEqual( api.normalizeDate( '07/13/2026' ), '20260713' );
assert.strictEqual( api.normalizeDate( '02/30/2026' ), null );
assert.strictEqual( api.normalizeBoolean( 'YES' ), '1' );
assert.strictEqual( api.normalizeBoolean( 'off' ), '0' );
assert.strictEqual( api.normalizeBoolean( 'maybe' ), null );
assert.strictEqual( api.normalizeStatus( 'Not Applicable', new Set( [ 'not-applicable' ] ) ), 'not-applicable' );
assert.strictEqual( api.normalizeLab( 'ILS Labs' ).value, 'ils-labs' );
assert.strictEqual( api.normalizeLab( 'Novel Lab' ).value, 'other' );
assert.strictEqual( api.normalizeValue( 'purity_percentage', '99.8' ).value, '99.8' );
assert.strictEqual( api.normalizeValue( 'purity_percentage', '101' ).valid, false );
assert.strictEqual( api.normalizeValue( 'lab_report_url', 'https://lab.example/report' ).valid, true );
assert.strictEqual( api.normalizeValue( 'lab_report_url', 'javascript:alert(1)' ).valid, false );
assert.strictEqual( api.normalizeValue( 'pending_lab_url', 'https://lab.example/progress' ).valid, true );
assert.strictEqual( api.normalizeValue( 'expected_coa_date', '2026-07-30' ).value, '20260730' );
assert.strictEqual( api.normalizeValue( 'coa_status', 'Vendor Vetting' ).valid, false );
assert.strictEqual( api.normalizeValue( 'workflow_stage', 'COA Pending' ).value, 'in-testing' );
assert.strictEqual( api.normalizeValue( 'workflow_stage', 'Sample Received' ).value, 'submitted-to-lab' );
assert.strictEqual( api.normalizeValue( 'workflow_stage', 'not-real' ).valid, false );
assert.strictEqual( api.normalizeValue( 'fentanyl_status', 'reported' ).valid, false );
assert.strictEqual( api.normalizeValue( 'fentanyl_status', 'pass' ).value, 'pass' );
assert.strictEqual( api.normalizeValue( 'fentanyl_status', 'not-applicable' ).valid, false );
assert.strictEqual( api.normalizeValue( 'fentanyl_status', '' ).value, '' );
assert.strictEqual( api.normalizeFentanylResult( 'ND' ), 'Not detected' );
assert.strictEqual( api.normalizeFentanylResult( 'Below detection limit' ), 'Below detection limit' );
assert.strictEqual( api.matchCompound( { compound_id: '1', compound_slug: 'reta-20' } ).match.id, 1 );
assert.strictEqual( api.matchCompound( { compound_id: '999', compound_slug: 'reta-30' } ).match.id, 1 );
assert.ok( api.matchCompound( { compound_display_name: 'Retatrutide 20mg' } ).error );
assert.ok( api.matchCompound( { compound_slug: 'missing' } ).warning );

const unknown = api.previewText( 'testing_lab,unknown_column\nNovel Lab,ignored' );
assert.ok( unknown.some( ( item ) => 'other_testing_lab' === item.column && 'Novel Lab' === item.imported ) );
assert.ok( unknown.some( ( item ) => 'unknown_column' === item.column && 'Unknown Column' === item.result ) );

const media = api.previewText( 'coa_pdf_id,coa_page_images,batch_vial_photo,batch_identity_photos\n7,8,9,10' );
assert.ok( media.every( ( item ) => 'No Matching Field' === item.result ) );

const replacement = api.previewText( 'batch_number\nIMPORTED' );
assert.strictEqual( replacement.find( ( item ) => 'batch_number' === item.column ).result, 'Will Replace' );
assert.strictEqual( api.applyPreview( replacement ), true );
assert.strictEqual( confirmations, 1 );
assert.strictEqual( inputs.field_batch_number.value, 'IMPORTED' );
api.clearImportedValues();
assert.strictEqual( inputs.field_batch_number.value, 'EXISTING' );

const blankFentanyl = api.previewText( 'fentanyl_status,fentanyl_result\n,ND' );
assert.strictEqual( blankFentanyl.find( item => 'fentanyl_status' === item.column ).value, '' );
assert.strictEqual( blankFentanyl.find( item => 'fentanyl_result' === item.column ).value, '' );

const fentanyl = api.previewText( 'fentanyl_status,fentanyl_result,fentanyl_method,fentanyl_specification,fentanyl_notes\npass,ND,Immunoassay,"Immunoassay, 50 ng/mL cutoff",Independent screen' );
assert.strictEqual( fentanyl.find( item => 'fentanyl_result' === item.column ).value, 'Not detected' );
assert.strictEqual( api.applyPreview( fentanyl ), true );
assert.strictEqual( inputs.field_fentanyl_status.value, 'pass' );
assert.strictEqual( inputs.field_fentanyl_result.value, 'Not detected' );
assert.strictEqual( inputs.field_fentanyl_method.value, 'Immunoassay' );
assert.strictEqual( inputs.field_fentanyl_specification.value, 'Immunoassay, 50 ng/mL cutoff' );
assert.strictEqual( inputs.field_fentanyl_notes.value, 'Independent screen' );

console.log( 'CSV_IMPORTER_JS_TESTS=PASS' );
