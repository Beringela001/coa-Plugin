'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');
const dashboard = read('includes/class-dashboard-workflow.php');
const shared = read('includes/class-coa-admin-workflow.php');
const dashboardTemplate = read('templates/admin/dashboard-workflow.php');
const dashboardCss = read('assets/css/pepselect-coa-dashboard-workflow.css');
const list = read('includes/class-coa-test-admin.php');
const listCss = read('assets/css/pepselect-coa-test-list.css');
const validation = read('includes/class-coa-test-validation.php');
const requirements = read('includes/class-coa-workflow-requirements.php');
const requirementsTemplate = read('templates/admin/workflow-requirements.php');
const requirementsCss = read('assets/css/pepselect-coa-workflow-requirements.css');
const requirementsJs = read('assets/js/pepselect-coa-workflow-requirements.js');
const compoundFields = read('includes/class-compound-fields.php');
const productAdmin = read('includes/class-product-matching-admin.php');
const productCss = read('assets/css/pepselect-coa-admin-product-matching.css');
const plugin = read('includes/class-plugin.php');
const main = read('pepselect-coa-archive.php');

// Dashboard readability and unchanged operational behavior.
assert.ok(!dashboardCss.includes('overflow-wrap: anywhere'));
assert.match(dashboardCss, /container-type: inline-size/);
assert.match(dashboardCss, /@container ps-coa-workflow \(max-width: 620px\)/);
assert.match(dashboardCss, /table-layout: fixed/);
assert.match(dashboardCss, /__col--compound \{ width: 26%/);
assert.match(dashboardCss, /__col--stage \{ width: 23%/);
assert.match(dashboardCss, /__col--expected \{ width: 20%/);
assert.match(dashboardCss, /__col--batch \{ width: 18%/);
assert.match(dashboardCss, /__col--action \{ width: 76px/);
assert.match(dashboardCss, /__action-heading[^}]*white-space: nowrap/);
assert.match(dashboardCss, /__action \.button[^}]*min-width: 52px[^}]*white-space: nowrap/);
assert.match(dashboardCss, /__badge[^}]*overflow-wrap: normal[^}]*word-break: normal/);
assert.match(dashboardCss, /__batch[^}]*overflow-wrap: break-word[^}]*word-break: normal/);
assert.match(dashboardCss, /grid-template-areas: "compound stage" "expected expected" "batch action"/);
assert.match(dashboardCss, /grid-template-columns: minmax\(0, 1fr\) auto/);
assert.match(dashboardCss, /td:nth-child\(1\)[^}]*grid-area: compound/);
assert.match(dashboardCss, /td:nth-child\(2\)[^}]*grid-area: stage[^}]*justify-self: end/);
assert.match(dashboardCss, /td:nth-child\(3\)[^}]*grid-area: expected[^}]*grid-template-columns: minmax\(0, 1fr\) auto/);
assert.match(dashboardCss, /td:nth-child\(4\)[^}]*grid-area: batch/);
assert.match(dashboardCss, /td:nth-child\(5\)[^}]*grid-area: action[^}]*justify-self: end/);
assert.match(dashboardCss, /__urgency, \.ps-coa-dashboard-workflow__due-soon[^}]*margin-top: 0[^}]*white-space: nowrap/);
assert.match(dashboardCss, /__action \.button[^}]*justify-self: end[^}]*min-height: 30px/);
assert.match(dashboardCss, /__table td::before \{ content: none/);
assert.ok(!dashboardCss.includes('@media (max-width: 782px)'));
assert.ok(!dashboardCss.includes('overflow-x:'));
['Compound', 'Stage', 'Expected COA', 'Batch', 'Action'].forEach(value => assert.ok(dashboardTemplate.includes(`<th>${dashboardTemplate.includes(`'${value}'`) ? '' : value}`) || dashboardTemplate.includes(`'${value}'`)));
assert.ok(dashboardTemplate.includes('data-label'));
assert.ok(dashboardTemplate.includes('ps-coa-dashboard-workflow__action-heading'));
assert.ok(dashboardTemplate.includes('ps-coa-dashboard-workflow__action'));
assert.ok(dashboardTemplate.includes('Verification in Progress') || shared.includes('Verification in Progress'));
assert.ok(shared.includes('Submitted to Laboratory'));
assert.ok(dashboard.includes('COA_Admin_Workflow::timing'));
assert.ok(dashboard.includes('usort( $rows'));

// One shared, site-local timing classifier drives Dashboard and COA Tests.
assert.ok(shared.includes('wp_timezone()'));
assert.ok(shared.includes('current_datetime()->setTime( 0, 0, 0 )'));
assert.ok(shared.includes("array( 'submitted-to-lab', 'in-testing' )"));
assert.ok(shared.includes("'pending' !== $outcome"));
assert.ok(shared.includes("$date < $today"));
assert.ok(shared.includes("$result['days'] <= 3"));
assert.ok(list.includes('COA_Admin_Workflow::timing'));
assert.ok(list.includes('Overdue by %d day'));
assert.ok(list.includes('Due Soon'));
assert.ok(listCss.includes('ps-coa-list-timing--overdue'));
assert.ok(listCss.includes('ps-coa-list-timing--due-soon'));

// Filters, composability, sanitization, scoping, and sortable columns.
['ps_workflow_stage', 'ps_coa_status', 'ps_compound', 'ps_lab', 'ps_timing'].forEach(value => assert.ok(list.includes(value)));
['All Workflow Stages', 'All COA Statuses', 'All Laboratories', 'All Timing Statuses', 'No Expected Date'].forEach(value => assert.ok(list.includes(value)));
['expected_coa_date', 'test_date', 'compound_id', 'workflow_stage', 'testing_lab', 'purity_percentage', 'coa_status', 'is_current'].forEach(value => assert.ok(list.includes(value)));
assert.ok(list.includes('Post_Types::COA_TEST !== $query->get'));
assert.ok(list.includes('! $query->is_main_query()'));
assert.ok(list.includes('sanitize_text_field'));
assert.ok(list.includes("'post__in'"));
assert.ok(list.includes('array_intersect'));
assert.ok(list.includes("REPLACE({$alias}.meta_value, '-', '')"));
assert.ok(list.includes("REGEXP '^[0-9]{8}$'"));
assert.ok(list.includes('ps_coa_sort_compound.post_title'));
assert.ok(list.includes("'edit.php' !== $hook"));
assert.ok(list.includes("current_user_can( 'edit_ps_coas' )"));

// Checklist is read-only guidance sourced from validation conditions.
assert.ok(plugin.includes('$this->coa_workflow_requirements->register_hooks()'));
assert.ok(requirements.includes("add_meta_box( 'pepselect-coa-workflow-requirements'"));
assert.ok(requirements.includes("current_user_can( 'edit_post', $post->ID )"));
assert.ok(requirements.includes('COA_Test_Validation::workflow_requirements'));
assert.ok(requirements.includes("'post.php', 'post-new.php'"));
assert.ok(requirementsTemplate.includes('Workflow Requirements') || requirements.includes('Workflow Requirements'));
['Complete', 'Missing', 'Not required yet', 'Optional'].forEach(value => assert.ok(requirementsTemplate.includes(value)));
assert.ok(requirementsTemplate.includes('Completion states reflect saved evidence'));
assert.ok(requirementsTemplate.includes('esc_html'));
assert.ok(requirementsCss.includes('ps-coa-workflow-requirements__item--missing'));
assert.ok(requirements.includes("wp_enqueue_script( 'pepselect-coa-workflow-requirements'"));
assert.ok(requirements.includes("'jquery', 'acf-input'"));
assert.ok(requirementsJs.includes('field_ps_coa_test_workflow_stage'));
assert.ok(requirementsJs.includes('field_ps_coa_test_status'));
assert.ok(requirementsJs.includes('field_ps_coa_test_testing_lab'));
assert.ok(requirementsJs.includes('.text(item.label)'));
['Batch Number', 'Testing Laboratory', 'Cap Color', 'Crimp Color', 'Batch Vial Photo', 'Expected COA Date', 'Original COA PDF', 'Certificate Page Images', 'Lab Report URL', 'Release Decision Note'].forEach(value => assert.ok(validation.includes(value)));
assert.ok(validation.includes("'submitted-to-lab', 'in-testing', 'complete'"));
assert.ok(!validation.includes("'waiting-on-vendor', 'submitted-to-lab', 'in-testing', 'complete'"));
assert.ok(validation.includes('Do not guess physical-batch or laboratory data'));
assert.ok(validation.includes('remain protected from public display until Verification in Progress'));
assert.ok(validation.includes('Lab Report URL is required before saving an Approved completed report.'));
assert.ok(validation.includes('Batch Vial Photo is required before moving this test to Verification in Progress.'));
assert.ok(validation.includes('Cap Color is required once the physical vendor batch has arrived'));
assert.ok(validation.includes('Crimp Color is required once the physical vendor batch has arrived'));

// Exact Compound explanations and existing visibility contract.
assert.ok(compoundFields.includes('Controls whether this compound is eligible to appear publicly in the COA Archive and Vetting History pages. Turning it off does not delete its tests or reports.'));
assert.ok(compoundFields.includes('Active controls public eligibility.'));
assert.ok(compoundFields.includes('Gives this compound priority placement in supported archive or promotional sections. Featured does not control whether the compound is publicly visible.'));
assert.ok(compoundFields.includes('Featured controls priority or emphasis.'));

// Product sidebar geometry only; Product Matching implementation is untouched.
assert.ok(productAdmin.includes('ps-coa-product-facts__status'));
assert.ok(productAdmin.includes('ps-coa-product-sidebar-actions'));
assert.match(productCss, /__status[^}]*grid-column: 1 \/ -1[^}]*overflow: hidden[^}]*overflow-wrap: break-word/);
assert.match(productCss, /sidebar-actions[^}]*grid-column: 1 \/ -1/);
assert.match(productCss, /sidebar-actions \.button[^}]*width: 100%/);
assert.ok(!productCss.includes('overflow-wrap: anywhere'));

// No unsafe COA Test workflow bulk/Quick Edit system or public/commerce expansion.
['bulk_actions-edit-ps_coa_test', 'quick_edit_custom_box', 'bulk Approve', 'bulk Complete'].forEach(value => assert.ok(!list.includes(value)));
const adminOnly = [dashboard, shared, list, validation, requirements, requirementsTemplate].join('\n').toLowerCase();
['wp_enqueue_scripts', 'woocommerce_single_product', 'elementor', 'checkout', 'shipping', 'inventory', 'qrcode', 'wp_cron', 'wp_mail'].forEach(value => assert.ok(!adminOnly.includes(value)));
['update_post_meta', 'delete_post_meta', 'wp_update_post', 'wp_insert_post', 'wp_delete_post'].forEach(value => assert.ok(!dashboard.includes(value) && !shared.includes(value) && !requirements.includes(value)));
assert.match(main, /Version:\s+0\.4\.0-rc\.1/);
assert.ok(main.includes("PEPSELECT_COA_ARCHIVE_VERSION', '0.4.0-rc.1'"));
console.log('COA_5D_ADMIN_WORKFLOW_STATIC_TESTS=PASS');
