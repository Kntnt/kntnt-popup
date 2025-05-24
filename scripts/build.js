#!/usr/bin/env node

const fs = require('fs-extra')
const path = require('path')
const { minify } = require('terser')
const postcss = require('postcss')
const cssnano = require('cssnano')
const archiver = require('archiver')

const PLUGIN_NAME = 'kntnt-popup'
const DIST_DIR = 'dist'
const PLUGIN_DIST_DIR = path.join(DIST_DIR, PLUGIN_NAME)

// Files and directories to copy
const COPY_ITEMS = [
  'README.md',
  'classes',
  'css',
  'js',
  'vendor',
  'kntnt-popup.php',
  'templates',
  'languages'
]

async function build () {
  console.log('🚀 Starting build process...')

  try {
    // Step 1: Clean dist directory
    console.log('🧹 Cleaning dist directory...')
    await fs.remove(DIST_DIR)
    await fs.ensureDir(PLUGIN_DIST_DIR)

    // Step 2: Copy files and directories
    console.log('📁 Copying files...')
    for (const item of COPY_ITEMS) {
      const srcPath = path.join('.', item)
      const destPath = path.join(PLUGIN_DIST_DIR, item)

      if (await fs.pathExists(srcPath)) {
        await fs.copy(srcPath, destPath)
        console.log(`   ✓ Copied ${item}`)
      } else {
        console.log(`   ⚠ Skipped ${item} (not found)`)
      }
    }

    // Step 3: Minify CSS
    console.log('🎨 Minifying CSS...')
    const cssPath = path.join(PLUGIN_DIST_DIR, 'css', 'kntnt-popup.css')
    if (await fs.pathExists(cssPath)) {
      const cssContent = await fs.readFile(cssPath, 'utf8')
      const result = await postcss([cssnano({ preset: 'default' })]).process(cssContent, { from: undefined })
      await fs.writeFile(cssPath, result.css)
      console.log('   ✓ CSS minified')
    } else {
      console.log('   ⚠ CSS file not found')
    }

    // Step 4: Minify JavaScript
    console.log('🔧 Minifying JavaScript...')
    const jsPath = path.join(PLUGIN_DIST_DIR, 'js', 'kntnt-popup.js')
    if (await fs.pathExists(jsPath)) {
      const jsContent = await fs.readFile(jsPath, 'utf8')
      const minified = await minify(jsContent, {
        compress: {
          drop_console: false, // Keep console for debugging
          drop_debugger: true,
          pure_funcs: ['console.log'] // Remove console.log in production
        },
        mangle: true,
        format: {
          comments: /^!/
        }
      })

      if (minified.error) {
        throw new Error(`JavaScript minification failed: ${minified.error}`)
      }

      await fs.writeFile(jsPath, minified.code)
      console.log('   ✓ JavaScript minified')
    } else {
      console.log('   ⚠ JavaScript file not found')
    }

    // Step 5: Create ZIP file
    console.log('📦 Creating ZIP archive...')
    const zipPath = path.join(DIST_DIR, `${PLUGIN_NAME}.zip`)

    await new Promise((resolve, reject) => {
      const output = fs.createWriteStream(zipPath)
      const archive = archiver('zip', {
        zlib: { level: 9 } // Maximum compression
      })

      output.on('close', () => {
        console.log(`   ✓ ZIP created: ${zipPath}`)
        resolve()
      })

      archive.on('error', (err) => {
        reject(err)
      })

      archive.pipe(output)
      archive.directory(PLUGIN_DIST_DIR, PLUGIN_NAME)
      archive.finalize()
    })

    // Show build summary
    const stats = await fs.stat(zipPath)
    const sizeMB = (stats.size / 1024 / 1024).toFixed(2)

    console.log('\n✅ Build completed successfully!')
    console.log(`📊 Build summary:`)
    console.log(`   • Plugin directory: ${PLUGIN_DIST_DIR}`)
    console.log(`   • ZIP file: ${zipPath}`)
    console.log(`   • ZIP size: ${sizeMB} MB`)

  } catch (error) {
    console.error('\n❌ Build failed:', error.message)
    process.exit(1)
  }
}

// Run the build
build()