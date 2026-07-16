'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const checks = [];
const check = (number, name, condition) => {
	assert.ok(condition, `${number}. ${name}`);
	checks.push(name);
};

const main = read('pepselect-coa-archive.php');
const activator = read('includes/class-activator.php');
const deactivator = read('includes/class-deactivator.php');
const uninstall = read('uninstall.php');
const upgrade = read('includes/class-upgrade.php');
const plugin = read('includes/class-plugin.php');
const postTypes = read('includes/class-post-types.php');
const capabilities = read('includes/class-capabilities.php');
const visibility = read('includes/class-frontend-visibility.php');
const matching = read('includes/class-product-matching.php');
const matchingAdmin = read('includes/class-product-matching-admin.php');
const testsRepository = read('includes/class-coa-test-repository.php');
const productCarousel = read('includes/class-product-coa-carousel.php');
const productButton = read('includes/class-product-coa-button.php');
const viewModel = read('includes/class-frontend-view-model.php');
const router = read('includes/class-frontend-router.php');
const templateLoader = read('includes/class-frontend-template-loader.php');
const compoundFields = read('includes/class-compound-fields.php');
const testFields = read('includes/class-coa-test-fields.php');
const validation = read('includes/class-coa-test-validation.php');
const testService = read('includes/class-coa-test-service.php');
const testAdmin = read('includes/class-coa-test-admin.php');
const dashboard = read('includes/class-dashboard-workflow.php');
const adminWorkflow = read('includes/class-coa-admin-workflow.php');
const importer = read('includes/class-coa-test-importer.php');
const archiveCache = read('includes/class-archive-cache.php');
const lightbox = read('assets/js/pepselect-coa-lightbox.js');
const carouselJs = read('assets/js/pepselect-coa-product-carousel.js');
const dashboardCss = read('assets/css/pepselect-coa-dashboard-workflow.css');
const reportTemplate = read('templates/single-coa-report.php');
const resultsTemplate = read('templates/partials/full-qc-results-table.php');
const compoundRest = compoundFields.slice(compoundFields.indexOf('public function register_rest_meta'), compoundFields.indexOf('/** Sanitizes REST meta'));
const testRest = testFields.slice(testFields.indexOf('public function register_rest_meta'), testFields.indexOf('/** Restricts REST writes'));

// Upgrade and persistence contracts.
check(1, 'existing compounds are never recreated during activation', !activator.includes('wp_insert_post'));
check(2, 'existing COA Tests are never recreated during activation', !activator.includes('wp_insert_post') && !activator.includes('wp_delete_post'));
check(3, 'existing relationships are backfilled only when their SKU snapshot is blank', matching.includes("'' === trim( (string) get_post_meta( $compound_id, self::SKU_SNAPSHOT_META, true ) )"));
check(4, 'Design and Copy migration replaces only exact untouched legacy defaults', upgrade.includes("$copy[0] === $settings[ $key ]"));
check(5, 'activation is data-idempotent', activator.includes('Upgrade::mark_current()') && !activator.includes('delete_option'));
check(6, 'runtime upgrades are version-gated', upgrade.includes("PEPSELECT_COA_ARCHIVE_VERSION === get_option( self::VERSION_OPTION )"));

// Public visibility and privacy.
check(7, 'draft reports remain hidden', visibility.includes("'publish' !== $post->post_status"));
check(8, 'private reports remain hidden', visibility.includes("'_ps_coa_private'") && visibility.includes("'private' === get_post_meta"));
check(9, 'inactive compounds remain hidden', visibility.includes("'is_active'") && visibility.includes('is_compound_public'));
check(10, 'failed reports are excluded from product cards', testsRepository.includes("$this->visibility->is_approved( $test )") && !productCarousel.includes("'failed'"));
check(11, 'failed reports remain classifiable for Vetting History', testsRepository.includes("$result['failed'][] = $test"));
check(12, 'early-stage privacy is centralized', visibility.includes('COA_Workflow::is_incoming_stage') && viewModel.includes('public_status_copy'));

// Canonical Product ID isolation.
check(13, 'Product ID is the canonical relationship', matching.includes("const PRODUCT_ID_META       = 'woocommerce_product_id'"));
check(14, 'title similarity is not a public relationship fallback', productCarousel.includes('compounds_for_product( $product_id )'));
check(15, 'SKU similarity is not a public relationship fallback', productButton.includes('compounds_for_product( $product_id )'));
check(16, 'unconnected products render no borrowed records', productCarousel.includes('if ( 1 !== count( $compound_ids ) ) { return'));

// Current, Incoming, and Previous hierarchy.
check(17, 'documented lead appears first', productCarousel.includes('$lead = array_shift( $documented )'));
check(18, 'at most one Incoming record is selected', testsRepository.includes("'incoming' => $incoming ? $incoming[0] : null"));
check(19, 'Previous records fill remaining positions', productCarousel.includes("$previous['role'] = 'previous'"));
check(20, 'product cards are capped at four', productCarousel.includes('if ( 4 === count( $reports ) ) { break; }'));
check(21, 'failed records never enter the product-card projection', testsRepository.includes("if ( $this->visibility->is_approved( $test )"));
check(22, 'Incoming links to Vetting History', productButton.includes("'kind' => 'incoming'") && productButton.includes("'View Vetting Status'"));
check(23, 'documented cards link to exact reports', viewModel.includes('test_url( $compound, $test )'));

// Truthful report claims.
check(24, 'Full-QC wording depends on recorded categories', viewModel.includes('qc_all_reported_successful'));
check(25, 'partial panels use a distinct truthful label', viewModel.includes('QC Testing Passed'));
check(26, 'untested categories remain neutral', viewModel.includes("if ( ! $reported ) { $row['status'] = $this->status( '' ); $row['detail'] = '--'; }"));
check(27, 'failed categories cannot become successful', viewModel.includes("in_array( $value, array( 'pass', 'approved' ), true )"));
check(28, 'Fentanyl uses its dedicated saved status', viewModel.includes("'fentanyl_status'"));
check(29, 'missing purity is not fabricated', viewModel.includes('purity_percentage') && resultsTemplate.includes('&mdash;'));

// Admin workflow and validation.
check(30, 'Dashboard counters use the shared active source set', dashboard.includes('COA_Admin_Workflow'));
check(31, 'Due Soon uses shared site-calendar timing', adminWorkflow.includes("'due-soon'"));
check(32, 'Overdue uses shared site-calendar timing', adminWorkflow.includes("'overdue'"));
check(33, 'COA Test filters are composable and post-type scoped', testAdmin.includes('apply_query_controls') && testAdmin.includes('Post_Types::COA_TEST'));
check(34, 'Waiting on Vendor does not require physical identity', validation.includes('Do not guess physical-batch or laboratory data'));
check(35, 'Approved completion requires evidence', validation.includes('Lab Report URL is required before saving an Approved completed report.'));
check(36, 'current-report uniqueness is enforced', testService.includes('clear_other_current_tests') && testService.includes("'is_current', 'value' => '1'"));
check(37, 'unsafe workflow bulk actions are absent', !testAdmin.includes('bulk_actions-edit-ps_coa_test'));

// Lightbox interaction.
check(38, 'lightbox is portaled under document.body', lightbox.includes('doc.body.appendChild(lightbox)'));
check(39, 'close control is wired', lightbox.includes("closeButton.addEventListener('click', close)"));
check(40, 'Escape closes the lightbox', lightbox.includes("event.key === 'Escape'"));
check(41, 'previous and next controls are wired', lightbox.includes("previousButton.addEventListener('click', previous)") && lightbox.includes("nextButton.addEventListener('click', next)"));
check(42, 'scroll position is restored', lightbox.includes('view.scrollTo(scrollState.x, scrollState.y)'));
check(43, 'lightbox viewport behavior supports mobile', lightbox.includes('lockScroll()') && read('assets/css/pepselect-coa-frontend.css').includes('100dvh'));

// Security and privacy.
check(44, 'custom capabilities are mapped on both post types', (postTypes.match(/'capabilities' => Capabilities::post_type_map\(\)/g) || []).length === 2);
check(45, 'state-changing product actions require nonces', matchingAdmin.includes('check_admin_referer') && matchingAdmin.includes('check_ajax_referer'));
check(46, 'public report output uses escaping', reportTemplate.includes('esc_html') && reportTemplate.includes('esc_url'));
check(47, 'admin output uses escaping', matchingAdmin.includes('esc_html') && matchingAdmin.includes('esc_url'));
check(48, 'query parameters are normalized', read('includes/class-frontend-query.php').includes('sanitize_text_field'));
check(49, 'REST writes require record edit permission', compoundFields.includes("current_user_can( 'edit_post', $post_id )") && testFields.includes("current_user_can( 'edit_post', $post_id )"));
check(50, 'private fields are excluded from public REST schemas', !testRest.includes('internal_notes') && !compoundRest.includes('internal_notes'));

// Performance, scoping, and limits.
check(51, 'public assets are route or shortcode scoped', templateLoader.includes("if ( ! $this->router->is_route() && ! $shortcode ) { return; }"));
check(52, 'product carousel assets enqueue only after valid output', productCarousel.indexOf('$this->ensure_assets();') > productCarousel.indexOf('if ( ! $reports ) { return'));
check(53, 'Dashboard CSS is Dashboard-only', dashboard.includes("'index.php' !== $hook") && dashboardCss.includes('ps-coa-dashboard-workflow'));
check(54, 'public limits remain enforced', productCarousel.includes('4 === count') && router.includes('array_slice( $previous_all, 0, 10 )'));
check(55, 'archive cache invalidates on record lifecycle changes', archiveCache.includes('save_post_') && archiveCache.includes('before_delete_post') && archiveCache.includes('trashed_post'));

// Source/package prerequisites. ZIP CRC and extraction are verified by the release packaging command.
const requiredDirectories = ['assets', 'includes', 'languages', 'templates', 'tests'];
const topEntries = fs.readdirSync(root);
const walk = directory => fs.readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
	const full = path.join(directory, entry.name);
	return entry.isDirectory() ? walk(full) : [full];
});
const sourceFiles = walk(root);
check(56, 'all required source directories exist', requiredDirectories.every(name => fs.statSync(path.join(root, name)).isDirectory()));
check(57, 'activation file is exactly one folder deep', fs.existsSync(path.join(root, 'pepselect-coa-archive.php')));
check(58, 'no nested duplicate plugin directory exists', !fs.existsSync(path.join(root, 'pepselect-coa-archive')));
check(59, 'no filename contains a backslash character', sourceFiles.every(file => !path.basename(file).includes('\\')));
check(60, 'no ZIP is stored inside the source directory', sourceFiles.every(file => path.extname(file).toLowerCase() !== '.zip'));
check(61, 'no forbidden release artifact is present', sourceFiles.every(file => !/\.(?:log|tmp|bak|sql|env|json)$/i.test(file)));
check(62, 'stable version is consistent in active metadata', /Version:\s+0\.4\.0(?:\s|$)/.test(main) && main.includes("PEPSELECT_COA_ARCHIVE_VERSION', '0.4.0'"));

assert.strictEqual(checks.length, 62);
console.log(`COA_7_RELEASE_CANDIDATE_STATIC_TESTS=PASS (${checks.length}/62)`);
