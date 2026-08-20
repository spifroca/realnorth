<?php
/**
 * Textkorrekturen im Frontend.
 *
 * Notlösung mit Ansage: Die Inhalte dieser Seite liegen in der Datenbank
 * (Pagelayer), nicht im Repo. Der richtige Ort für eine Textänderung ist
 * der Page-Builder im wp-admin. Dieser Filter existiert, weil eine
 * Korrektur über den Deploy-Weg gehen soll — er entfernt den Begriff bei
 * der Ausgabe, der Datenbestand bleibt unverändert.
 *
 * Folgen, die man kennen muss:
 * - Wer die Seite im wp-admin öffnet, sieht den Begriff weiterhin. Der
 *   Filter greift nur im Frontend (siehe is_admin()-Guard).
 * - Sobald der Text in Pagelayer selbst korrigiert ist, gehört der
 *   Eintrag hier wieder heraus. Sonst bleibt eine Regel stehen, die
 *   niemand mehr versteht.
 *
 * @package RealNorth
 */

declare( strict_types=1 );

namespace RealNorth;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Begriffe, die bei der Ausgabe entfernt werden.
 *
 * Ein Eintrag pro Zeile. Mehrteilige Begriffe werden tolerant behandelt:
 * Leerzeichen, geschützte Leerzeichen und Bindestriche zwischen den
 * Wörtern passen alle.
 *
 * @return string[]
 */
function content_removals(): array {
	return array(
		'Winterthur Seen',
	);
}

/**
 * Baut das Suchmuster für einen Begriff.
 *
 * Entfernt zusätzlich ein angrenzendes Trennzeichen, damit aus
 * «Berikon - Rudolfstetten - Winterthur Seen» ein sauberes
 * «Berikon - Rudolfstetten» wird und kein hängender Gedankenstrich.
 */
function build_removal_pattern( string $term ): string {
	$words = preg_split( '/\s+/', trim( $term ) );

	if ( empty( $words ) ) {
		return '';
	}

	$gap  = '(?:\s|&nbsp;|&#160;|\x{00a0}|-)+';
	$term_pattern = implode(
		$gap,
		array_map(
			static fn( string $word ): string => preg_quote( $word, '~' ),
			$words
		)
	);

	// Trennzeichen zwischen Aufzählungsgliedern, inklusive HTML-Entities.
	$sep   = '(?:[-–—]|&#8211;|&#8212;|&ndash;|&mdash;)';
	$space = '(?:\s|&nbsp;|&#160;|\x{00a0})*';

	// Delimiter ist ~, nicht # - die Trennzeichen-Alternativen enthalten
	// HTML-Entities wie &#8211;, deren # das Muster sonst vorzeitig beendet.
	//
	// Reihenfolge ist wichtig: erst Trennzeichen davor, dann danach,
	// zuletzt der Begriff allein.
	// Die dritte Alternative schluckt auch führenden Leerraum, damit aus
	// «Nur Winterthur Seen» ein «Nur» wird und kein «Nur ». $space matcht
	// auch leer und deckt damit den nackten Begriff mit ab.
	return '~'
		. $space . $sep . $space . $term_pattern
		. '|' . $term_pattern . $space . $sep . $space
		. '|' . $space . $term_pattern
		. '~u';
}

/**
 * Entfernt die konfigurierten Begriffe aus einem Ausgabetext.
 *
 * @param mixed $text Text aus dem Filter; wird unverändert zurückgegeben,
 *                    wenn es kein String ist.
 * @return mixed
 */
function remove_from_output( $text ) {
	if ( ! is_string( $text ) || '' === $text || is_admin() ) {
		return $text;
	}

	foreach ( content_removals() as $term ) {
		$pattern = build_removal_pattern( $term );

		if ( '' === $pattern ) {
			continue;
		}

		$result = preg_replace( $pattern, '', $text );

		// Bei einem Regex-Fehler liefert preg_replace null - dann bleibt
		// der Originaltext stehen, statt die Seite leer auszugeben.
		if ( null !== $result ) {
			$text = $result;
		}
	}

	return $text;
}

add_filter( 'the_content', __NAMESPACE__ . '\remove_from_output', 20 );
add_filter( 'widget_text', __NAMESPACE__ . '\remove_from_output', 20 );
