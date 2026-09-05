<?php
define( 'ABSPATH', __DIR__ );
function home_url( $path ) { return 'https://pepselect.com' . $path; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function untrailingslashit( $path ) { return rtrim( $path, '/' ); }
require dirname( __DIR__ ) . '/includes/class-qr-redirects.php';
$redirects = new PepSelect\COAArchive\QR_Redirects();
$target = home_url( '/testing/nad-500-mg/nd50026205js/' );
foreach ( array( '/testing/nad-500-mg/nd50026205jp', '/testing/nad-500-mg/nd50026205jp/', '/testing/nad-500-mg/progress-1269/' ) as $path ) {
    if ( $target !== $redirects->destination_for_path( $path ) ) { throw new RuntimeException( 'Legacy QR failed: ' . $path ); }
}
foreach ( array( '/testing/nad-500-mg/nd50026205js/', '/testing/nad-500-mg/nd50026205jpx/', '/testing/other/nd50026205jp/', '/testing/nad-500-mg/progress-1270/', '', null ) as $path ) {
    if ( '' !== $redirects->destination_for_path( $path ) ) { throw new RuntimeException( 'Unapproved redirect or loop.' ); }
}
if ( home_url( '/testing/retatrutide-10mg/' ) !== $redirects->destination_for_path( '/testing/961/' ) ) { throw new RuntimeException( 'Adjacent legacy route changed.' ); }
echo "NAD QR correction contract: OK\n";
