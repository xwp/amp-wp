/* eslint-env node */

module.exports = function( grunt ) {
	'use strict';

	// Root paths to include in the plugin build ZIP when running `npm run build:prod`.
	const productionIncludedRootFiles = [
		'LICENSE',
		'amp.php',
		'assets',
		'back-compat',
		'includes',
		'readme.txt',
		'src',
		'templates',
		'vendor',
	];

	// These patterns paths will be excluded from among the above directory.
	const productionExcludedPathPatterns = [
		/.*\/src\/.*/,
	];

	// These will be removed from the vendor directory after installing but prior to creating a ZIP.
	// ⚠️ Warning: These paths are passed straight to rm command in the shell, without any escaping.
	const productionVendorExcludedFilePatterns = [
		'composer.*',
		'patches',
		'lib',
		'vendor/*/*/.editorconfig',
		'vendor/*/*/.git',
		'vendor/*/*/.gitignore',
		'vendor/*/*/composer.*',
		'vendor/*/*/Doxyfile',
		'vendor/*/*/LICENSE',
		'vendor/*/*/phpunit.*',
		'vendor/*/*/*.md',
		'vendor/*/*/*.txt',
		'vendor/*/*/*.yml',
		'vendor/*/*/.*.yml',
		'vendor/*/*/tests',
		'vendor/ampproject/common/phpstan.neon.dist',
		'vendor/ampproject/optimizer/bin',
		'vendor/ampproject/optimizer/conceptual-diagram.svg',
		'vendor/ampproject/optimizer/phpstan.neon.dist',
		'vendor/bin',
		'vendor/php-parallel-lint',
	];

	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		// Clean up the build.
		clean: {
			compiled: {
				src: [ 'assets/js/*.js', '!assets/js/amp-service-worker-runtime-precaching.js', 'assets/js/*.asset.php' ],
			},
			build: {
				src: [ 'build' ],
			},
		},

		// Shell actions.
		shell: {
			options: {
				stdout: true,
				stderr: true,
			},
			readme: {
				command: './vendor/xwp/wp-dev-lib/scripts/generate-markdown-readme', // Generate the readme.md.
			},
			verify_matching_versions: {
				command: 'php bin/verify-version-consistency.php',
			},
			composer_install: {
				command: [
					'if [ ! -e build ]; then echo "Run grunt build first."; exit 1; fi',
					'cd build',
					'composer install --no-dev -o',
					'for symlinksource in $(find vendor/ampproject -type l); do symlinktarget=$(readlink "$symlinksource") && rm "$symlinksource" && cp -r "vendor/ampproject/$symlinktarget" "$symlinksource"; done',
					'composer remove cweagans/composer-patches --update-no-dev -o',
					'rm -r ' + productionVendorExcludedFilePatterns.join( ' ' ),
					'if [ -d vendor/ampproject/common/vendor ]; then rm -r vendor/ampproject/common/vendor; fi',
					'if [ -d vendor/ampproject/optimizer/vendor ]; then rm -r vendor/ampproject/optimizer/vendor; fi',
				].join( ' && ' ),
			},
			create_build_zip: {
				command: 'if [ ! -e build ]; then echo "Run grunt build first."; exit 1; fi; if [ -e amp.zip ]; then rm amp.zip; fi; cd build; zip -r ../amp.zip .; cd ..; echo; echo "ZIP of build: $(pwd)/amp.zip"',
			},
		},

		// Deploys a git Repo to the WordPress SVN repo.
		wp_deploy: {
			deploy: {
				options: {
					plugin_slug: 'amp',
					build_dir: 'build',
					assets_dir: 'wp-assets',
				},
			},
		},
	} );

	// Load tasks.
	grunt.loadNpmTasks( 'grunt-contrib-clean' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-shell' );
	grunt.loadNpmTasks( 'grunt-wp-deploy' );

	// Register tasks.
	grunt.registerTask( 'default', [
		'build',
	] );

	grunt.registerTask( 'readme', [
		'shell:readme',
	] );

	grunt.registerTask( 'build', function() {
		const done = this.async();
		const spawnQueue = [];
		const stdout = [];

		spawnQueue.push(
			{
				cmd: 'git',
				args: [ '--no-pager', 'log', '-1', '--format=%h', '--date=short' ],
			},
			{
				cmd: 'git',
				args: [ 'ls-files' ],
			},
		);

		function finalize() {
			const commitHash = stdout.shift();
			const lsOutput = stdout.shift();
			const versionAppend = new Date().toISOString().replace( /\.\d+/, '' ).replace( /-|:/g, '' ) + '-' + commitHash;

			const paths = lsOutput.trim().split( /\n/ ).filter( function( file ) {
				const topSegment = file.replace( /\/.*/, '' );
				if ( ! productionIncludedRootFiles.includes( topSegment ) ) {
					return false;
				}

				for ( const productionExcludedPathPattern of productionExcludedPathPatterns ) {
					if ( productionExcludedPathPattern.test( file ) ) {
						return false;
					}
				}

				return true;
			} );

			paths.push( 'composer.*' ); // Copy in order to be able to do run composer_install.
			paths.push( 'lib/**' );
			paths.push( 'assets/js/*.js' ); // @todo Also include *.map files?
			paths.push( 'assets/js/*.asset.php' );
			paths.push( 'assets/css/*.css' );
			paths.push( 'patches/*.patch' );

			grunt.config.set( 'copy', {
				build: {
					src: paths,
					dest: 'build',
					expand: true,
					options: {
						noProcess: [ '*/**', 'LICENSE' ], // That is, only process amp.php and readme.txt.
						process( content, srcpath ) {
							let matches, version, versionRegex;
							if ( /amp\.php$/.test( srcpath ) ) {
								versionRegex = /(\*\s+Version:\s+)(\d+(\.\d+)+-\w+)/;

								// If not a stable build (e.g. 0.7.0-beta), amend the version with the git commit and current timestamp.
								matches = content.match( versionRegex );
								if ( matches ) {
									version = matches[ 2 ] + '-' + versionAppend;
									console.log( 'Updating version in amp.php to ' + version ); // eslint-disable-line no-console
									content = content.replace( versionRegex, '$1' + version );
									content = content.replace( /(define\(\s*'AMP__VERSION',\s*')(.+?)(?=')/, '$1' + version );
								}

								// Remove dev mode code blocks.
								content = content.replace( /\n\/\/\s*DEV_CODE.+?\n}\n/s, '' );
							}
							return content;
						},
					},
				},
			} );
			grunt.task.run( 'readme' );
			grunt.task.run( 'copy' );
			grunt.task.run( 'shell:composer_install' );

			done();
		}

		function doNext() {
			const nextSpawnArgs = spawnQueue.shift();
			if ( ! nextSpawnArgs ) {
				finalize();
			} else {
				grunt.util.spawn(
					nextSpawnArgs,
					function( err, res ) {
						if ( err ) {
							throw new Error( err.message );
						}
						stdout.push( res.stdout );
						doNext();
					},
				);
			}
		}

		doNext();
	} );

	grunt.registerTask( 'create-build-zip', [
		'shell:create_build_zip',
	] );

	grunt.registerTask( 'deploy', [
		'shell:verify_matching_versions',
		'wp_deploy',
	] );
};
