<?php
/**
 * Builds _playground/blueprint.json from _playground/seed.php.
 *
 * The seed lives in a real PHP file so it can be linted and tested; this
 * script embeds it into the blueprint's runPHP step.
 *
 * Usage: php bin/build-blueprint.php
 *
 * @package Duplicate_Post_Button
 */

$root = dirname( __DIR__ );
$seed = file_get_contents( $root . '/_playground/seed.php' );

$blueprint = array(
	'$schema'           => 'https://playground.wordpress.net/blueprint-schema.json',
	'meta'              => array(
		'title'       => 'Duplicate Post Button demo',
		'description' => 'Creates posts with categories, tags, featured images and custom fields, then opens the Posts screen where each row has a Duplicate action.',
		'author'      => 'jdyach',
		'categories'  => array( 'Plugin' ),
	),
	'landingPage'       => '/wp-admin/edit.php',
	'preferredVersions' => array(
		'php' => '8.3',
		'wp'  => 'latest',
	),
	'features'          => array( 'networking' => true ),
	'login'             => true,
	'steps'             => array(
		array(
			'step'      => 'installTheme',
			'themeData' => array(
				'resource' => 'git:directory',
				'url'      => 'https://github.com/jdyach/fieldnote',
				'ref'      => 'main',
			),
			'options'   => array( 'activate' => true ),
		),
		array(
			'step'       => 'installPlugin',
			'pluginData' => array(
				'resource' => 'git:directory',
				'url'      => 'https://github.com/jdyach/duplicate-post-button',
				'ref'      => 'main',
			),
			'options'    => array( 'activate' => true ),
		),
		array(
			'step' => 'runPHP',
			'code' => $seed,
		),
	),
);

file_put_contents(
	$root . '/_playground/blueprint.json',
	json_encode( $blueprint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"
);
echo "Wrote _playground/blueprint.json\n";
