#!/usr/bin/env node

/**
 * SCSS Compilation Script
 * Compiles SCSS to CSS and optionally watches for changes
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const SCSS_INPUT = 'assets/scss/main.scss';
const CSS_OUTPUT = 'assets/css/styles.css';

function compileSCSS() {
    try {
        console.log('🔄 Compiling SCSS...');

        // Compile SCSS to CSS
        execSync(`sass ${SCSS_INPUT} ${CSS_OUTPUT} --style=expanded`, {
            stdio: 'inherit'
        });

        console.log('✅ SCSS compiled successfully!');
        console.log(`📁 Output: ${CSS_OUTPUT}`);

    } catch (error) {
        console.error('❌ SCSS compilation failed:', error.message);
        process.exit(1);
    }
}

function watchSCSS() {
    console.log('👀 Watching SCSS files for changes...');
    console.log('Press Ctrl+C to stop watching');

    const scssDir = path.dirname(SCSS_INPUT);

    fs.watch(scssDir, { recursive: true }, (eventType, filename) => {
        if (filename && filename.endsWith('.scss')) {
            console.log(`📝 ${filename} changed, recompiling...`);
            compileSCSS();
        }
    });
}

// Parse command line arguments
const args = process.argv.slice(2);
const shouldWatch = args.includes('--watch') || args.includes('-w');

// Compile SCSS
compileSCSS();

// Watch for changes if requested
if (shouldWatch) {
    watchSCSS();
}