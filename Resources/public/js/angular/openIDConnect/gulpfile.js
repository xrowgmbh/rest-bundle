"use strict";

const gulp = require("gulp");
const del = require("del");
const tsc = require("gulp-typescript");
const replace = require("gulp-replace");
const uglify = require("gulp-uglify");
const concat = require("gulp-concat");
const tsProject = tsc.createProject("tsconfig.json", {
        typescript: require("typescript"),
        outFile: "main.js"});
const tslint = require("gulp-tslint");
const rename = require("gulp-rename");
const fileExists = require('file-exists');

/**
 * Remove build directory.
 */
gulp.task("clean", (cb) => {
    return del(["build/*", "build"], cb);
});

/**
 * Remove build node_modules for PROD.
 */
gulp.task("create-prod", ["compile"], (cb) => {
    console.log("Remove all unnecessary folders/files (node_modules) ...");
    return del(["node_modules"], cb);
});

/**
 * Lint all custom TypeScript files.
 */
gulp.task("tslint", ["clean"], () => {
    return gulp.src("src/*.ts")
        .pipe(tslint())
        .pipe(tslint.report("prose"));
});

/**
 * Compile TypeScript sources and create sourcemaps in build directory.
 */
gulp.task("compile", ["tslint", "vendor-bundle"], () => {
    let tsResult = gulp.src([
            "src/*.ts",
            "node_modules/angular2/typings/browser.d.ts"])
        .pipe(tsc(tsProject));
    return tsResult.js
        .pipe(replace(/\.\/app/g, "app"))
        .pipe(replace(/\.\/api/g, "api"))
        .pipe(replace(/\.\/jwt/g, "jwt"))
        .pipe(replace(/\.\/error/g, "error"))
        .pipe(gulp.dest("build"));
});

/**
 * Compile TypeScript sources and create sourcemaps in build directory.
 */
gulp.task("compile-for-watch", ["tslint"], () => {
    let tsResult = gulp.src([
            "src/*.ts",
            "node_modules/angular2/typings/browser.d.ts"])
        .pipe(tsc(tsProject));
    return tsResult.js
        .pipe(replace(/\.\/app/g, "app"))
        .pipe(replace(/\.\/api/g, "api"))
        .pipe(replace(/\.\/jwt/g, "jwt"))
        .pipe(replace(/\.\/error/g, "error"))
        .pipe(gulp.dest("build"));
});

/**
 * Copy all resources that are not TypeScript files into build directory.
 */
gulp.task("resources", () => {
    return gulp.src(["src/*", "!**/*.ts"])
        .pipe(gulp.dest("build"));
});

/**
 * Copy all required libraries into build directory.
 */
gulp.task("vendor-bundle", ["resources"], function() {
    console.log("Create vendor file for necessary node_modules ...");
    console.log("Workaroung: Packing causes errors in IE11. Please check es6-shim and systemJS and remove them in the following file, when error is fixed. See: vendor/xrow/rest-bundle/Resources/views/page_head_script.html.twig");
    gulp.src([
        "node_modules/es6-shim/es6-shim.min.js",
        "node_modules/systemjs/dist/system-polyfills.js",
        "node_modules/angular2/es6/dev/src/testing/shims_for_IE.js",
        "node_modules/angular2/bundles/angular2-polyfills.js",
        "node_modules/systemjs/dist/system.src.js",
        "node_modules/rxjs/bundles/Rx.js",
        "node_modules/angular2/bundles/angular2.dev.js",
        "node_modules/angular2/bundles/http.dev.js"
    ])
    // https://github.com/paulmillr/es6-shim/issues/392:
    .pipe(replace(/if\(s\)\{Object/g, "if(s && Object.isExtensible(e[t])){Object"))
    .pipe(concat("vendors.js"))
    .pipe(gulp.dest("build/lib"));
});

/**
 * Rename .htaccess
 */
gulp.task("rename-prod", (cb) => {
    if (fileExists(".htaccess")) {
        gulp.src(".htaccess")
            .pipe(rename(".htaccessDISABLED"))
            .pipe(gulp.dest("."));
        return del([".htaccess"], cb);
    }
    else {
        console.log(".htaccess does not exist.");
    }
});


/**
 * Rename .htaccessDISABLED
 */
gulp.task("rename-dev", (cb) => {
    if (fileExists(".htaccessDISABLED")) {
        console.log("RENAME .htaccessDISABLED.");
        gulp.src(".htaccessDISABLED")
            .pipe(rename(".htaccess"))
            .pipe(gulp.dest("."));
        return del([".htaccessDISABLED"], cb);
    }
    else {
        console.log(".htaccessDISABLED does not exist.");
    }
});

/**
 * Watch for changes in TypeScript, HTML and CSS files.
 */
gulp.task("watch", ["compile"], function () {
    gulp.watch(["src/*.ts"], ["compile"]).on("change", function (e) {
        console.log("TypeScript file " + e.path + " has been changed. Compiling.");
    });
    gulp.watch(["src/*.html"], ["resources"]).on("change", function (e) {
        console.log("Resource file " + e.path + " has been changed. Updating.");
    });
});

/**
 * Build the project for DEV.
 */
gulp.task("build-dev", ["compile", "rename-dev"], () => {
    console.log("Building the project for DEV environment...");
});

/**
 * Build the project for PROD.
 */
gulp.task("build-prod", ["create-prod", "resources", "rename-prod"], () => {
    console.log("Building the project for PROD environment...");
});