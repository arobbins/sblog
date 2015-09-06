/*

	gulpfile.js

	The sole purpose of this file is to bootstrap our build
	process. Rather than manage one giant configuration file,
	each task has been seperated into its own file in gulp/tasks.
	Any files in that directory get automatically required below.

*/

'use strict';

// Speeds up the build
require('cache-require-paths');

var requireDir = require('require-dir');

// Require all tasks in gulp/tasks recursively
requireDir('./gulp/tasks', { recurse: true });