"use strict";

const gulp = require("gulp");
const del = require("del");
const replace = require("gulp-regex-replace");
const tsc = require("gulp-typescript");
const sourcemaps = require("gulp-sourcemaps");
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
    return gulp.src("src/**/*.ts")
        .pipe(tslint())
        .pipe(tslint.report("prose"));
});

/**
 * Compile TypeScript sources and create sourcemaps in build directory.
 */
gulp.task("compile", ["tslint"], () => {
    let tsResult = gulp.src([
            "src/**/*.ts",
            "node_modules/angular2/typings/browser.d.ts"])
        .pipe(sourcemaps.init())
        .pipe(tsc(tsProject));
    return tsResult.js
        .pipe(sourcemaps.write("."))
        .pipe(replace({regex:'..\/app', replace:'app'}))
        .pipe(gulp.dest("build"));
});

/**
 * Copy all resources that are not TypeScript files into build directory.
 */
gulp.task("resources", () => {
    return gulp.src(["src/**/*", "!**/*.ts"])
        .pipe(gulp.dest("build"));
});

/**
 * Copy all required libraries into build directory.
 */
gulp.task("libs", () => {
    return gulp.src([
            "jwt-decode/build/jwt-decode.js",
            "angular2-jwt/angular2-jwt.js",
        ], {cwd: "node_modules/**"}) /* Glob required here. */
        .pipe(gulp.dest("build/lib"));
});

/**
 * Replace ../app, ../home and so on
 */
gulp.task("replace", ["resources"], () => {
    return gulp.src("build/app/app.component.js")
        .pipe(replace({regex:'..\/home', replace:'home'}, {regex:'..\/login', replace:'login'}));
});

/**
 * Watch for changes in TypeScript, HTML and CSS files.
 */
gulp.task("watch", function () {
    gulp.watch(["src/**/*.ts"], ["compile"]).on("change", function (e) {
        console.log("TypeScript file " + e.path + " has been changed. Compiling.");
    });
    gulp.watch(["src/**/*.html", "src/**/*.css"], ["resources"]).on("change", function (e) {
        console.log("Resource file " + e.path + " has been changed. Updating.");
    });
});

/**
 * Build the project.
 */
gulp.task("build", ["compile", "libs", "resources"], () => {
    console.log("Building the project ...");
});