<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Compare visible wording across fields without discarding distinct disclosures.
$ps_note_candidates = array();
if ( 'failed' === $test['coa_status'] && trim( $test['release_decision_note'] ) ) {
	$ps_note_candidates[] = esc_html( $test['release_decision_note'] );
}
$ps_note_candidates[] = $test['public_notes'];
$ps_note_candidates[] = $test['report_notes'];
$ps_notes = array();
foreach ( $ps_note_candidates as $ps_note ) {
	$ps_note = trim( $ps_note );
	$ps_note_key = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( strip_tags( $ps_note ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	if ( '' !== $ps_note_key && ! isset( $ps_notes[ $ps_note_key ] ) ) {
		$ps_notes[ $ps_note_key ] = $ps_note;
	}
}
?>
<?php if ( $ps_notes ) : ?>
<aside class="ps-coa-outcome-notes" id="ps-coa-batch-note" aria-labelledby="ps-coa-batch-note-title">
	<h2 class="ps-coa-outcome-notes__title" id="ps-coa-batch-note-title"><span aria-hidden="true">ⓘ</span> <?php esc_html_e( 'Important batch note', 'pepselect-coa-archive' ); ?></h2>
	<?php foreach ( $ps_notes as $ps_note ) { echo wp_kses_post( wpautop( $ps_note ) ); } ?>
</aside>
<?php endif; ?>
