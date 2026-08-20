<?php
/**
 * Regressionstest für die Textkorrekturen im Frontend.
 *
 * Läuft ohne WordPress: die benötigten WP-Funktionen sind unten gestubbt.
 * Aufruf über bin/test.sh oder direkt mit `php tests/content-cleanup.php`.
 *
 * @package RealNorth
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

function is_admin(): bool {
	return false;
}

function add_filter( string $hook, $callback, int $priority = 10, int $args = 1 ): bool {
	return true;
}

require dirname( __DIR__ ) . '/includes/content-cleanup.php';

/**
 * Eingabe => erwartete Ausgabe.
 *
 * Die letzten drei Fälle sind die wichtigen: sie sichern ab, dass der
 * Filter nicht zu viel entfernt.
 */
$cases = array(
	'Berikon - Rudolfstetten - Winterthur Seen'              => 'Berikon - Rudolfstetten',
	'Berikon – Rudolfstetten – Winterthur Seen'              => 'Berikon – Rudolfstetten',
	'Berikon &#8211; Rudolfstetten &#8211; Winterthur Seen'  => 'Berikon &#8211; Rudolfstetten',
	'Winterthur Seen - Berikon - Rudolfstetten'              => 'Berikon - Rudolfstetten',
	'Berikon - Winterthur-Seen - Rudolfstetten'              => 'Berikon - Rudolfstetten',
	'Berikon&nbsp;-&nbsp;Winterthur&nbsp;Seen'               => 'Berikon',
	'Nur Winterthur Seen'                                    => 'Nur',
	'Berikon - Rudolfstetten'                                => 'Berikon - Rudolfstetten',
	'Wohnen in Winterthur'                                   => 'Wohnen in Winterthur',
	'Seenweg 4, Zug'                                         => 'Seenweg 4, Zug',
);

$failed = 0;

foreach ( $cases as $input => $expected ) {
	$actual = RealNorth\remove_from_output( $input );
	$ok     = ( $actual === $expected );

	if ( ! $ok ) {
		++$failed;
	}

	printf(
		"%s  %-54s -> %s%s\n",
		$ok ? 'ok  ' : 'FAIL',
		$input,
		$actual,
		$ok ? '' : "   (erwartet: {$expected})"
	);
}

echo 0 === $failed
	? "\ncontent-cleanup: alle " . count( $cases ) . " Fälle ok.\n"
	: "\ncontent-cleanup: {$failed} Fall/Fälle fehlgeschlagen.\n";

exit( 0 === $failed ? 0 : 1 );
