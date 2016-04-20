"use strict";

const gulp = require("gulp");
const del = require("del");
const tsc = require("gulp-typescript");
const sourcemaps = require("gulp-sourcemaps");
const replace = require("gulp-replace");
const tsProject = tsc.createProject("tsconfig.json");
const tslint = require("gulp-tslint");

/**
 * Remove build directory.
 */
gulp.task("clean", (cb) => {
    return del(["build"], cb);
});

/**
 * Lint all custom TypeScript files.
 */
gulp.task("tslint", () => {
    return gulp.src("src/*.ts")
        .pipe(tslint())
        .pipe(tslint.report("prose"));
});

/**
 * Compile TypeScript sources and create sourcemaps in build directory.
 */
gulp.task("compile", ["tslint"], () => {
    let tsResult = gulp.src([
            "src/*.ts",
            "node_modules/angular2/typings/browser.d.ts"])
        .pipe(sourcemaps.init())
        .pipe(tsc(tsProject));
    return tsResult.js
        .pipe(sourcemaps.write("."))
        .pipe(replace(/\.\/app/g, 'app'))
        .pipe(replace(/\.\/http/g, 'http'))
        .pipe(replace(/\.\/jwt/g, 'jwt'))
        .pipe(replace(/\.\/cast/g, 'cast'))
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
 * WE DON'T NEED IT ANYMORE
 */
gulp.task("libs", () => {
    return gulp.src([
            "jwt-decode/build/jwt-decode.js",
            "ng2-jwt/src/services/ng2-jwt.js"
        ], {cwd: "node_modules/**"}) /* Glob required here. */
        .pipe(gulp.dest("build/lib"));
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
 * Build the project.
 */
gulp.task("build", ["clean", "compile", "resources"], () => {
    console.log("Building the project ...");
});

/**
 * Clear the project for PROD:
 * Remove node_modules directory.
 */
gulp.task("prod", (cb) => {
    console.log("Remove all unnecessary folders/files (node_modules) ...");
    return del(["node_modules"], cb);
});