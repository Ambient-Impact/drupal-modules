/**
 * @file
 * Copies Yarn dependendencies to a publicly accessible vendor directory.
 *
 * Note that this specifically only handles "dependencies" and not
 * "devDependencies", as the former is used for run-time dependencies while the
 * latter are for building the front-end assets but not intended to be publicly
 * accessible themselves.
 *
 * @todo Refactor this script so it can dynamically fetch "dependencies" from
 *   whatever the current package is calling it, so that it can be reused.
 *
 * @todo Have this script also update *.libraries.yml versions when pulling in
 *   updated packages so that version strings change, forcing clients to
 *   download the correct versions?
 *
 * @see https://git.drupalcode.org/project/drupal/-/blob/9.5.x/core/scripts/js/vendor-update.js
 *   Drupal core script as a loose inspiration for this. Copies a list of
 *   Node/Yarn package files to core/assets/vendor and updates their versions in
 *   core.libraries.yml.
 */

'use strict';

const Encore = require('@symfony/webpack-encore');
const path = require('path');

/**
 * The vendor directory, relative to the current script location.
 *
 * @type {String}
 *
 * @todo Make this relative to the calling package and not the script.
 *
 * @todo Make this configurable?
 */
const vendorDir = './vendor';

/**
 * Array of package names to be copied to the public vendor directory.
 *
 * Note that all of these must be declared as direct dependencies of this
 * package or Yarn will throw an error when trying to access them. This means
 * any dependencies of these dependencies need to be listed in the package.json.
 *
 * @type {String[]}
 */
const packageNames = ['photoswipe'];

/**
 * Configuration array for Encore.copyFiles().
 *
 * This contains objects with 'from' and 'to' keys defining the Yarn virtual
 * filesystem paths and their corresponding output paths under the vendor
 * directory to be copied to so that they're publicly accessible.
 *
 * @type {Object[]}
 *
 * @see https://github.com/symfony/webpack-encore/blob/main/index.js
 *   Documents the API.
 */
let copyConfig = [];

for (let i = 0; i < packageNames.length; i++) {

  try {

    // The resolved virtual filesystem path to the root directory of this
    // package. Note that Node will throw an error if we can't access the
    // package.json due to the package only using the more modern "exports"
    // field without a "./package.json" key.
    //
    // @see https://nodejs.org/api/packages.html#exports
    //
    // @todo Use the Yarn PnP API to access this, removing the chance Node will
    //   throw this error?
    let packagePath = path.dirname(require.resolve(
      `${packageNames[i]}/package.json`
    ));

    copyConfig.push({
      from: packagePath,
      to:   `${packageNames[i]}/[path][name].[ext]`
    });

  } catch (error) {
    console.error(
      `Could not access ${packageNames[i]}/package.json; this can occur if the package defines an "exports" field that doesn't contain a "./package.json" key. See: https://nodejs.org/api/packages.html#exports
      Error was:`, error);
  }

}

// @see https://symfony.com/doc/current/frontend/encore/installation.html#creating-the-webpack-config-js-file
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
.setOutputPath(path.resolve(__dirname, vendorDir))

// Encore will complain if the public path doesn't start with a slash.
.setPublicPath('/')
.setManifestKeyPrefix('')

// We need to set either this or Encore.enableSingleRuntimeChunk() otherwise
// Encore will refuse to run.
.disableSingleRuntimeChunk()

.copyFiles(copyConfig)

module.exports = Encore.getWebpackConfig();
