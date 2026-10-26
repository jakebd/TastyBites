<?php
/**
 * ROADMAP 2.8 Spelling fixer. The AI only points at misspelled words; PHP decides what happens to each:
 *
 *   fix  – applied automatically (later, on publish): the AI is sure, the change is small and spelling-only;
 *   ask  – left as written and asked of Jacob/Shawn: the AI is unsure, or it might be a name, or it's a real
 *          word typed for another and appears more than once in that paragraph;
 *   skip – ignored, with the reason: not found, a Canadian/US variant, swearing, on the site word list, too
 *          big a change to be a typo, etc.
 *
 * Only text between tags changes (never markup, links, image alt text or block settings), and a words check
 * confirms every other word is identical. Step 5a is report-only: `wp tba spelling [<id>...] [--store]`, with
 * results on the Tidy-ups → Spelling report screen.
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'tba_features', function ( $features ) {
	$features['spelling'] = array( 'label' => 'Spelling fixer (fixes typos after publish, asks when unsure)', 'ai' => true, 'default' => false );
	return $features;
} );

// Tags that start a new paragraph-like chunk of text. Inline tags (strong, em, a, span) don't.
const TBA_SPELL_BLOCK_TAGS = 'p|h[1-6]|li|ul|ol|figcaption|figure|div|td|th|tr|table|blockquote|pre|dt|dd|section|cite';
const TBA_SPELL_WORD_CHARS = "\p{L}\p{N}'’";

/**
 * The post's text as chunks (one per paragraph, heading, list item, caption...), each with the raw text
 * pieces between tags and their byte offsets in the content, so a fix can be put back exactly in place.
 */
function tba_text_chunks( $content ) {
	$parts  = preg_split( '#(<!--.*?-->|<[^>]*>)#s', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE );
	$chunks = array();
	$cur    = array( 'text' => '', 'segs' => array() );
	$skip   = false;
	$flush  = function () use ( &$chunks, &$cur ) {
		$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $cur['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		if ( preg_match( '/\p{L}/u', $text ) ) {
			$chunks[] = array( 'text' => $text, 'segs' => $cur['segs'] );
		}
		$cur = array( 'text' => '', 'segs' => array() );
	};
	foreach ( $parts as list( $piece, $offset ) ) {
		if ( '' === $piece ) {
			continue;
		}
		if ( '<' === $piece[0] ) {
			if ( preg_match( '#^<(/?)(script|style)\b#i', $piece, $m ) ) {
				$skip = '' === $m[1];
			}
			if ( 0 === strpos( $piece, '<!--' ) || preg_match( '#^</?(' . TBA_SPELL_BLOCK_TAGS . ')\b#i', $piece ) ) {
				$flush();
			} elseif ( preg_match( '#^<br\b#i', $piece ) ) {
				$cur['text'] .= ' ';
			}
			continue;
		}
		if ( ! $skip ) {
			$cur['segs'][] = array( $offset, $piece );
			$cur['text']  .= $piece;
		}
	}
	$flush();
	return $chunks;
}

function tba_word_pattern( $word ) {
	// A word Shawn split in two ("cheese cake") matches across any spacing.
	$quoted = str_replace( '\ ', ' ', preg_quote( $word, '/' ) );
	return '/(?<![' . TBA_SPELL_WORD_CHARS . '])' . str_replace( ' ', '\s+', $quoted ) . '(?![' . TBA_SPELL_WORD_CHARS . '])/u';
}

/** Words of a text, for the words check (apostrophes kept inside words). */
function tba_spell_tokens( $text ) {
	preg_match_all( '/[' . TBA_SPELL_WORD_CHARS . ']+/u', $text, $m );
	return $m[0];
}

/** Same letters, ignoring case and apostrophes. */
function tba_spell_key( $word ) {
	return str_replace( array( "'", '’' ), '', mb_strtolower( $word ) );
}

/** colour/color, centre/center, travelled/traveled, organise/organize...: both are right, leave them. */
function tba_is_spelling_variant( $a, $b ) {
	$norm = function ( $w ) {
		$w = tba_spell_key( $w );
		$w = preg_replace( array( '/our/u', '/re(s?)$/u', '/is(e|ed|es|ing|ation)$/u', '/ys(e|ed|es|ing)$/u', '/ll(ed|ing|er)$/u', '/ogue$/u', '/ence$/u', '/ae/u' ),
			array( 'or', 'er$1', 'iz$1', 'yz$1', 'l$1', 'og', 'ense', 'e' ), $w );
		return $w;
	};
	// Short words (or/our, are/re) are never spelling variants.
	return min( mb_strlen( $a ), mb_strlen( $b ) ) >= 5 && tba_spell_key( $a ) !== tba_spell_key( $b ) && $norm( $a ) === $norm( $b );
}

/** Never touch swearing, either way. */
function tba_is_swearing( $word ) {
	return (bool) preg_match( '/fuck|shit|bitch|bastard|asshole|b(damn(ed|it)?|crap(py)?|piss(ed)?|ass(es)?|hell|bloody|bugger)b/iu', $word );
}

/** The site word list: names and words confirmed as written (lower case). */
function tba_site_words() {
	return array_map( 'mb_strtolower', (array) get_option( 'tba_site_words', array() ) );
}

/** Copies the original's capitalisation onto the fix ("Restaraunt" → "Restaurant", "BBQS" → "BBQS"). */
function tba_match_case( $word, $fix ) {
	if ( mb_strtoupper( $word ) === $word && mb_strlen( $word ) > 1 ) {
		return mb_strtoupper( $fix );
	}
	if ( mb_strtoupper( mb_substr( $word, 0, 1 ) ) === mb_substr( $word, 0, 1 ) ) {
		return mb_strtoupper( mb_substr( $fix, 0, 1 ) ) . mb_substr( $fix, 1 );
	}
	return $fix;
}

/** Edit distance on letters (ignoring case and apostrophes), small enough for a typo? */
function tba_is_typo_sized( $word, $fix ) {
	$a = tba_spell_key( str_replace( ' ', '', $word ) );
	$b = tba_spell_key( str_replace( ' ', '', $fix ) );
	if ( $a === $b ) {
		return true; // Only an apostrophe, a split or a join ("dont" → "don't", "alot" → "a lot", "with out" → "without").
	}
	$allowed = mb_strlen( $a ) >= 8 ? 3 : 2;
	// levenshtein() counts bytes; swap multi-byte letters for single bytes first.
	$map = array();
	$enc = function ( $s ) use ( &$map ) {
		return preg_replace_callback( '/[^\x00-\x7F]/u', function ( $m ) use ( &$map ) {
			if ( ! isset( $map[ $m[0] ] ) ) {
				$map[ $m[0] ] = chr( 128 + count( $map ) % 127 );
			}
			return $map[ $m[0] ];
		}, $s );
	};
	return levenshtein( $enc( $a ), $enc( $b ) ) <= $allowed;
}

/**
 * Ask the AI for spelling mistakes in a post. Returns array( 'chunks' => ..., 'items' => [...] ) with a
 * decision on each item, or a WP_Error.
 */
function tba_spelling_check( $post, $model = 'sonnet' ) {
	$chunks = tba_text_chunks( $post->post_content );
	$lines  = array( '[0] ' . html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
	foreach ( $chunks as $i => $chunk ) {
		$lines[] = '[' . ( $i + 1 ) . '] ' . $chunk['text'];
	}

	$system = <<<TXT
You check spelling for Gengie's Tasty Bites, a Canadian food and travel blog written by Shawn Genge, a retired chef with 40 years in the business. He writes casually, the way he talks. Your only job is to point out misspelled words so they can be corrected. You never rewrite anything.

Report a word only if it is misspelled:
- typos: wrong, missing, extra or swapped letters (restaraunt, recieve, delicous);
- two words run together (alot -> a lot);
- one word split in two (cheese cake -> cheesecake, with out -> without, sight seeing -> sightseeing): give both parts as the word, with the space;
- a contraction missing its apostrophe, when the version without it isn't a word (dont -> don't, didnt -> didn't);
- a real word clearly typed by mistake for a different word (desert for dessert, loose for lose, their for there): set real_word to true.

Never report:
- Canadian or British spellings (colour, flavour, centre, travelled, favourite, cheque) or their US forms;
- slang, swearing or casual spellings he means (gonna, yum, fuckery);
- names of people, restaurants, dishes, places, brands or products, however unusual;
- foreign or regional food words (spätzle, jambalaya, poutine, schnitzel);
- abbreviations, numbers, units, hashtags, web addresses;
- grammar, punctuation, capital letters, word choice or style.

For each misspelled word:
- line: the [number] of the line it's in;
- word: copied exactly as written, a single word (or the two parts of a split word) with no punctuation around it;
- fix: the correct spelling (same capitalisation);
- sure: true only when it is certainly a typo with one obvious correction. If it might be a name or an intended word, or there's more than one reasonable correction, set sure to false;
- why: a few words.
List each misspelled word once per line it appears in. If nothing is misspelled, return an empty list.
TXT;

	$answer = tba_ai( 'spelling', array(
		'model'      => $model,
		'system'     => $system,
		'content'    => "The post, one numbered line per title, paragraph, heading, list item or caption:\n\n" . implode( "\n", $lines ),
		'schema'     => array(
			'type'       => 'object',
			'properties' => array(
				'fixes' => array(
					'type'  => 'array',
					'items' => array(
						'type'                 => 'object',
						'properties'           => array(
							'line'      => array( 'type' => 'integer' ),
							'word'      => array( 'type' => 'string' ),
							'fix'       => array( 'type' => 'string' ),
							'sure'      => array( 'type' => 'boolean' ),
							'real_word' => array( 'type' => 'boolean' ),
							'why'       => array( 'type' => 'string' ),
						),
						'required'             => array( 'line', 'word', 'fix', 'sure', 'real_word', 'why' ),
						'additionalProperties' => false,
					),
				),
			),
			'required'   => array( 'fixes' ),
		),
		'max_tokens' => 8000,
		'object_id'  => $post->ID,
	) );
	if ( is_wp_error( $answer ) ) {
		return $answer;
	}

	$site_words = tba_site_words();
	$items      = array();
	foreach ( $answer['fixes'] as $f ) {
		$line = (int) $f['line'];
		$word = trim( (string) $f['word'] );
		$fix  = trim( (string) $f['fix'] );
		$text = 0 === $line ? $lines[0] : ( $chunks[ $line - 1 ]['text'] ?? '' );
		// The AI sometimes gives only the first half of a split word ("cheese" → "cheesecake" for "cheese cake").
		// Take the next word along too, or the fix would come out as "cheesecake cake".
		if ( '' !== $word && ! preg_match( '/\s/u', $word . $fix ) && mb_strlen( $fix ) > mb_strlen( $word ) + 1
			&& preg_match( '/(?<![' . TBA_SPELL_WORD_CHARS . '])' . preg_quote( $word, '/' ) . '\s+([\p{L}]+)/u', $text, $next )
			&& tba_spell_key( $word . $next[1] ) === tba_spell_key( $fix ) ) {
			$word .= ' ' . $next[1];
		}
		$item = array(
			'line'      => $line,
			'word'      => $word,
			'fix'       => $fix,
			'sure'      => (bool) $f['sure'],
			'real_word' => (bool) $f['real_word'],
			'why'       => (string) $f['why'],
			'context'   => tba_spell_context( $text, $word ),
		);
		$found = '' === $word ? 0 : preg_match_all( tba_word_pattern( $word ), $text );
		// Capitalised in the middle of a sentence: probably a name (a restaurant, a town), so ask, never fix.
		$mid_caps = preg_match( '/^\p{Lu}/u', $word ) && preg_match( '/[\p{L}\p{N},;)]\s+' . substr( tba_word_pattern( $word ), 1 ), $text );

		if ( ! $found ) {
			$item += array( 'decision' => 'skip', 'reason' => 'Not found in that line' );
		} elseif ( '' === $fix || preg_match_all( '/\s+/u', $word ) + preg_match_all( '/\s+/u', $fix ) > 1 ) {
			$item += array( 'decision' => 'skip', 'reason' => 'Not a single-word fix' );
		} elseif ( tba_spell_key( $word ) === tba_spell_key( $fix ) && false === strpbrk( $fix, "'’" ) ) {
			$item += array( 'decision' => 'skip', 'reason' => 'Only capitalisation differs' );
		} elseif ( preg_match( '/\d/u', $word ) ) {
			$item += array( 'decision' => 'skip', 'reason' => 'Has numbers' );
		} elseif ( tba_is_swearing( $word ) || tba_is_swearing( $fix ) ) {
			$item += array( 'decision' => 'skip', 'reason' => 'Swearing is left alone' );
		} elseif ( tba_is_spelling_variant( $word, $fix ) ) {
			$item += array( 'decision' => 'skip', 'reason' => 'Canadian/US spelling variant' );
		} elseif ( in_array( mb_strtolower( $word ), $site_words, true ) ) {
			$item += array( 'decision' => 'skip', 'reason' => 'On the site word list' );
		} elseif ( ! tba_is_typo_sized( $word, $fix ) ) {
			$item += array( 'decision' => 'ask', 'reason' => 'Too big a change to be sure it is a typo' );
		} elseif ( ! $item['sure'] ) {
			$item += array( 'decision' => 'ask', 'reason' => 'The AI is not sure' );
		} elseif ( $mid_caps ) {
			$item += array( 'decision' => 'ask', 'reason' => 'Capitalised mid-sentence: might be a name' );
		} elseif ( $item['real_word'] && $found > 1 ) {
			$item += array( 'decision' => 'ask', 'reason' => 'A real word that appears more than once in the line' );
		} else {
			$item += array( 'decision' => 'fix', 'reason' => $item['real_word'] ? 'Wrong word, once in the line' : 'Typo' );
		}
		$item['fix'] = tba_match_case( $word, $fix );
		$items[]     = $item;
	}
	return array( 'chunks' => $chunks, 'items' => $items );
}

function tba_spell_context( $text, $word ) {
	if ( '' === $word || ! preg_match( tba_word_pattern( $word ), $text, $m, PREG_OFFSET_CAPTURE ) ) {
		return mb_substr( $text, 0, 120 );
	}
	$at    = mb_strlen( substr( $text, 0, $m[0][1] ) ); // Byte offset → letter offset.
	$start = max( 0, $at - 60 );
	$ctx   = mb_substr( $text, $start, mb_strlen( $word ) + 120 );
	return ( $start ? '…' : '' ) . trim( $ctx ) . ( $start + mb_strlen( $ctx ) < mb_strlen( $text ) ? '…' : '' );
}

/** Report-only run: store the result on the post for the Spelling report screen. */
function tba_spelling_store( $post, $result, $model ) {
	update_post_meta( $post->ID, '_tba_spelling', array(
		'model'   => $model,
		'created' => time(),
		'base'    => md5( $post->post_title . "\n" . $post->post_content ),
		'items'   => $result['items'],
	) );
}
