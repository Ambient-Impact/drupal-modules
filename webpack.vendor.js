'use strict';

const Encore = require('@symfony/webpack-encore');
const path = require('path');

const vendorDir = './vendor';

const packageNames = [
  'ally.js', 'autosize', 'chillout', 'fastdom', 'fontfaceobserver',
  'fr-offcanvas', 'headroom.js', 'jquery-hoverintent', 'js-cookie',
  'photoswipe', 'popper.js', 'tippy.js',
];

let copyConfig = [];

for (let i = 0; i < packageNames.length; i++) {

  try {

    let packagePath = path.dirname(require.resolve(
      `${packageNames[i]}/package.json`
    ));

    copyConfig.push({
      from: packagePath,
      to:   `${packageNames[i]}/[path][name].[ext]`
    });

  } catch (error) {
    console.log(error);
  }
}

// console.log(copyConfig);

// @see https://symfony.com/doc/current/frontend/encore/installation.html#creating-the-webpack-config-js-file
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
.setOutputPath(path.resolve(__dirname, vendorDir))

// Encore will complain if the public path doesn't start with a slash.
// Unfortunately, it doesn't seem Webpack's automatic public path works here.
//
// @see https://webpack.js.org/guides/public-path/#automatic-publicpath
.setPublicPath('/')
.setManifestKeyPrefix('')

// We output multiple files.
.disableSingleRuntimeChunk()

.copyFiles(copyConfig)

module.exports = Encore.getWebpackConfig();
