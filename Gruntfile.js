module.exports = function(grunt){

	/*
		The build directory is not tracked by git, but PagodaBox relies on Git to host your files.
		So once you have the build. Copy it.
		Create a new orphan branch.
		Untar it into the orphan branch using git checkout branch_name --orphan
		Push that branch to the Pagodabox
	 */

	//project configuration
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'), //get the package.json to load dependencies!
		clean: {
			pre:{
				files: [
					{
						src: ['build/**'],
						dot: true
					}
				]
			},
			afterRequire:{
				src: ['build/js/**/*'],
				filter: function(filepath) {
					grunt.log.writeln(filepath);
					if (!grunt.file.isDir(filepath)) { return false; }
					return (require('fs').readdirSync(filepath).length === 0);
				},
			}
		},
		copy:{
			main:{
				files:[
					{src: ['**'], dest: 'build/', dot: true, filter: function(filepath){
					
						//directory separator
						var dir = require('path').sep;
						
						// ignoring "css/" except *.css
						var cssPattern = new RegExp('^css\\'+dir);
						var cssExtensionPattern = new RegExp('css$');
						if(cssPattern.test(filepath)){
							if(!cssExtensionPattern.test(filepath)){
								grunt.log.writeln('Not copying: ' + filepath);
								return false;
							}
						}
						
						// ignoring various directories
						if(
							/^bin/.test(filepath) || 
							/^tests/.test(filepath) || 
							/^tools/.test(filepath) || 
							/^node_modules/.test(filepath) || 
							/^\.git/.test(filepath) ||
							/^\.c9revisions/.test(filepath) ||
							/^project_design/.test(filepath)
						){
							grunt.log.writeln('Not copying: ' + filepath);
							return false;
						}
						
						//ignoring inner files
						var logPattern = new RegExp('^application\\'+dir+'logs\\'+dir);
						var cachePattern = new RegExp('^application\\'+dir+'cache\\'+dir);
						var configPattern = new RegExp('^application\\'+dir+'config\\'+dir+'development\\'+dir);
						if(logPattern.test(filepath) || cachePattern.test(filepath) || configPattern.test(filepath)){
							grunt.log.writeln('Not copying: ' + filepath);
							return false;
						}
						
						//ignoring various files
						if(
							filepath === 'README.md' || 
							filepath === 'composer.json' || 
							filepath === 'composer.lock' || 
							filepath === 'Gruntfile.js' || 
							filepath === 'package.json' || 
							filepath === 'bower.json' || 
							filepath === '.gitignore' || 
							filepath === '.gitattributes' || 
							filepath === 'pkgscript.log' || 
							filepath === 'npm_global_installer.js' || 
							filepath === 'build.js'
						){
							grunt.log.writeln('Not copying: ' + filepath);
							return false;
						}
						
						return true;
						
					}}
				]
			}
		},
		cssmin: {
			main:{
				options: {
					keepSpecialComments: 0
				},
				files: {
					'build/css/main.css': 'build/css/main.css'
				}
			}
		},
		replace:{
			main:{
				src: [
					'build/index.php', 
					'build/application/views/layouts/default_layout.php', 
					'build/js/bootstrap.js'],
				overwrite: true,
				replacements: [{
					from: /((?:[a-z][a-z]+)\(\'ENVIRONMENT\', isset\(\$_SERVER\[\'CI_ENV\'\]\) \? \$_SERVER\[\'CI_ENV\'\] : \'development\'\).)/g,
					to: "define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');"
				},{
					from: /<link rel="stylesheet" href="css\/main.css">/g,
					to: '<link rel="stylesheet" href="css/main.' + (new Date()).getTime() + '.css">'
				},{
					from: /<script src="js\/config.js"><\/script>/g,
					to: '<script src="js/config.' + (new Date()).getTime() + '.js"></script>'
				},{
					from: /urlArgs: 'bust=' \+  \(new Date\(\)\).getTime\(\)/g,
					to: "urlArgs: 'bust=" + (new Date()).getTime() + "'"
				}]
			}
		},
		requirejs:{
			main:{
				options:{
					baseUrl: 'build/js/', //base directory of source
					mainConfigFile: 'build/js/bootstrap.js', //import runtime config
					keepBuildDir: true, //build dir should not be deleted
					removeCombined: true,
					skipDirOptimize: true, //we're only optimising a single file here
					findNestedDependencies: true, //could be false?? we should find nested dependencies!
					name: 'bootstrap', //this is the file we're going to optimise
					out: 'build/js/bootstrap.js', //this is final optimised file
					paths: { //empty are for any scripts loaded from a CDN
						'sharethis': 'empty:',
						'filepicker': 'empty:'
					}
				}
			}
		}
	});
		
	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-requirejs');
	
	grunt.registerTask('default', ['clean:pre', 'copy', 'cssmin', 'replace', 'requirejs', 'clean:afterRequire']);

};