'use strict';

const assert = require( 'assert' );
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const script = fs.readFileSync( path.join( root, 'assets/js/pepselect-coa-test-form.js' ), 'utf8' );
const style = fs.readFileSync( path.join( root, 'assets/css/pepselect-coa-test-form.css' ), 'utf8' );
const controller = fs.readFileSync( path.join( root, 'includes/class-coa-test-form.php' ), 'utf8' );

assert.ok( script.includes( 'workflow_stage' ) );
assert.ok( script.includes( 'partial_results_available' ) );
assert.ok( script.includes( 'aria-disabled' ) );
assert.ok( script.includes( 'input.disabled = disabled' ) );
assert.ok( script.includes( 'legacyStatus' ) );
assert.ok( script.includes( 'applyFentanyl' ) );
assert.ok( script.includes( "'Immunoassay, 50 ng/mL cutoff'" ) );
assert.ok( script.includes( "'pass' === status ? 'Not detected'" ) );
assert.ok( script.includes( "'fail' === status ? 'Detected'" ) );
assert.ok( script.includes( '[data-name="fentanyl_status"] :input' ) );
assert.ok( style.includes( '.post-type-ps_coa_test' ) );
assert.ok( style.includes( '.ps-coa-stage-disabled' ) );
assert.ok( controller.includes( "array( 'post.php', 'post-new.php' )" ) );
assert.ok( controller.includes( 'Post_Types::COA_TEST !== $screen->post_type' ) );
assert.ok( controller.includes( "add_meta_box( 'postimagediv'" ) );
assert.ok( ! controller.includes( 'woocommerce_single_product' ) );
assert.ok( ! script.toLowerCase().includes( 'qr' ) );

console.log( 'COA_TEST_FORM_STATIC_TESTS=PASS' );
