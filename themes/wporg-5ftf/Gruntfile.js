/* global module:false, require:function, process:object */

require( 'es6-promise' ).polyfill();

module.exports = function( grunt ) {
	var isChild = 'wporg' !== grunt.file.readJSON( 'package.json' ).name;

	grunt.initConfig({
		postcss: {
			options: {
				map: 'build' !== process.argv[2],
				processors: [
					require( 'autoprefixer' )( {
						browsers: [
							'Android >= 2.1',
							'Chrome >= 21',
							'Edge >= 12',
							'Explorer >= 7',
							'Firefox >= 17',
							'Opera >= 12.1',
							'Safari >= 6.0'
						],
						cascade: false
					} ),
					require( 'pixrem' ),
					require('cssnano')({
						mergeRules: false
					})
				]
			},
			dist: {
				src: 'css/style.css'
			}
		},

		sass: {
			options: {
				implementation: require( 'node-sass' ),
				sourceMap: true,
				// Don't add source map URL in built version.
				omitSourceMapUrl: 'build' === process.argv[2],
				outputStyle: 'expanded'
			},
			dist: {
				files: {
					'css/style.css': 'css/style.scss'
				}
			}
		},

		sass_globbing: {
			itcss: {
				files: (function() {
					var files = {};

					['settings', 'tools', 'generic', 'base', 'objects', 'components', 'trumps'].forEach( function( component ) {
						var paths = ['../pub/wporg/css/' + component + '/**/*.scss', '!../pub/wporg/css/' + component + '/_' + component + '.scss'];

						if ( isChild ) {
							paths.push( 'css/' + component + '/**/*.scss' );
							paths.push( '!css/' + component + '/_' + component + '.scss' );
						}

						files[ 'css/' + component + '/_' + component + '.scss' ] = paths;
					} );

					return files;
				}())
			},
			options: { signature: false }
		},

		watch: {
			css: {
				files: ['**/*.scss', '../pub/wporg/css/**/*scss'],
				tasks: ['css']
			}
		}
	});

	if ( 'build' === process.argv[2] ) {
		grunt.config.merge( { postcss: { options : { processors: [ require( 'cssnano' ) ] } } } );
	}

	grunt.loadNpmTasks( 'grunt-sass' );
	grunt.loadNpmTasks( 'grunt-postcss' );
	grunt.loadNpmTasks( 'grunt-sass-globbing' );
	grunt.loadNpmTasks( 'grunt-contrib-watch' );

	grunt.registerTask( 'css', ['sass_globbing', 'sass', 'postcss' ] );
	grunt.registerTask( 'build', ['css'] );
};
