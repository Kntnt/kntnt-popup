#!/usr/bin/env node

const fs = require('fs-extra')
const path = require('path')

async function devBuild () {

  console.log('🔧 Setting up development dependencies...')

  try {
    // Ensure vendor directory exists
    const vendorDir = path.join('vendor', 'micromodal')
    await fs.ensureDir(vendorDir)

    // Copy Micromodal from node_modules to vendor
    const micromodalSrc = path.join('node_modules', 'micromodal', 'dist', 'micromodal.min.js')
    const micromodalDest = path.join(vendorDir, 'micromodal.min.js')

    if (await fs.pathExists(micromodalSrc)) {
      await fs.copy(micromodalSrc, micromodalDest)
      console.log('   ✓ Copied micromodal.min.js to vendor/micromodal/')

      // Get version info
      const packageJsonPath = path.join('node_modules', 'micromodal', 'package.json')
      if (await fs.pathExists(packageJsonPath)) {
        const packageJson = await fs.readJson(packageJsonPath)
        console.log(`   ✓ Micromodal version: ${packageJson.version}`)
      }
    } else {
      console.error('   ❌ micromodal.min.js not found in node_modules')
      console.log('   💡 Run "npm install" first')
      process.exit(1)
    }

    console.log('\n✅ Development setup completed!')
    console.log('📁 You can now use the plugin directly from this directory in WordPress')

  } catch (error) {
    console.error('\n❌ Development build failed:', error.message)
    process.exit(1)
  }

}

// Run the dev build
devBuild()