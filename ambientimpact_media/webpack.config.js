'use strict';

const autoprefixer = require('autoprefixer');
const componentPaths = require('drupal-ambientimpact-core/componentPaths');
const Encore = require('@symfony/webpack-encore');
const glob = require('glob');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const SVGSpritemapPlugin = require('svg-spritemap-webpack-plugin');

const distPath = '.webpack-dist';

/**
 * Whether to output to the paths where the source files are found.
 *
 * If this is true, compiled Sass files, source maps, etc. will be placed
 * alongside their source files. If this is false, built files will be placed in
 * the dist directory defined by distPath.
 *
 * @type {Boolean}
 */
const outputToSourcePaths = true;

/**
 * Get globbed entry points.
 *
 * This uses the 'glob' package to automagically build the array of entry
 * points, as there are a lot of them spread out over many components.
 *
 * @return {Array}
 *
 * @see https://www.npmjs.com/package/glob
 */
function getGlobbedEntries() {

  return glob.sync(
    // This specifically only searches for SCSS files that aren't partials, i.e.
    // do not start with '_'.
    `./!(${distPath})/**/!(_)*.scss`
  ).reduce(function(entries, currentPath) {

      const parsed = path.parse(currentPath);

      entries[`${parsed.dir}/${parsed.name}`] = currentPath;

      return entries;

  }, {});

};

/**
 * Icon bundles objects built from icon directories found via globbing.
 *
 * @type {Array}
 *
 * @see https://github.com/cascornelissen/svg-spritemap-webpack-plugin/issues/195
 *   Don't use path.resolve() in any of these as it'll result in incorrect paths
 *   on Windows.
 */
const iconBundles = glob.sync(
  './**/icons/*/'
).filter(function(bundlePath) {

  // Only include this entry if it actually has one or more SVG files in it.
  return glob.sync(`${bundlePath}/*.svg`).length > 0;

}).map(function(bundlePath) {

  let bundleName = path.basename(bundlePath);

  return {
    sourcePath: bundlePath,
    bundleName: bundleName,
    bundleFile: path.join(bundlePath, `../${bundleName}.svg`)
  };

});

// @see https://symfony.com/doc/current/frontend/encore/installation.html#creating-the-webpack-config-js-file
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
.setOutputPath(path.resolve(__dirname, (outputToSourcePaths ? '.' : distPath)))

// Encore will complain if the public path doesn't start with a slash.
// Unfortunately, it doesn't seem Webpack's automatic public path works here.
//
// @see https://webpack.js.org/guides/public-path/#automatic-publicpath
.setPublicPath('/')
.setManifestKeyPrefix('')

// We output multiple files.
.disableSingleRuntimeChunk()

.configureFilenames({

  // Since Webpack started out primarily for building JavaScript applications,
  // it always outputs a JS files, even if empty. We place these in a temporary
  // directory by default. Note that the 'webpack-remove-empty-scripts' plug-in
  // should prevent these being output, but if there's an error while running
  // Webpack, you'll get a nice 'temp' directory you can just delete.
  js: 'temp/[name].js',

  // Assets are left at their original locations and not hashed. The [query]
  // must be left in to ensure any query string specified in the CSS is
  // preserved.
  //
  // @see https://stackoverflow.com/questions/68737296/disable-asset-bundling-in-webpack-5#68768905
  //
  // @see https://github.com/webpack-contrib/css-loader/issues/889#issuecomment-1298907914
  assets: '[file][query]',

})
.addEntries(getGlobbedEntries())

// Clean out any previously built files in case of source files being removed or
// renamed. We need to exclude the vendor directory or CSS bundled with
// libraries will get deleted.
//
// @see https://github.com/johnagan/clean-webpack-plugin
.cleanupOutputBeforeBuild(['**/*.css', '**/*.css.map', '!vendor/**'])

.enableSourceMaps(!Encore.isProduction())

// We don't use Babel so we can probably just remove all presets to speed it up.
//
// @see https://github.com/symfony/webpack-encore/issues/154#issuecomment-361277968
.configureBabel(function(config) {
  config.presets = [];
})

// Remove any empty scripts Webpack would generate as we aren't a primarily
// JavaScript-based app and only output CSS and assets.
.addPlugin(new RemoveEmptyScriptsPlugin())

.enableSassLoader(function(options) {
  options.sassOptions = {includePaths: componentPaths().all};
})
.enablePostCssLoader(function(options) {
  options.postcssOptions = {
    plugins: [
      autoprefixer(),
    ],
  };
})
// Re-enable automatic public path for paths referenced in CSS.
//
// @see https://github.com/symfony/webpack-encore/issues/915#issuecomment-1189319882
.configureMiniCssExtractPlugin(function(config) {
  config.publicPath = 'auto';
})
// Disable the Encore image rule as we don't use it at the moment and it may try
// to bundle images from the vendor directory which we also don't want.
.configureImageRule({enabled: false});

iconBundles.forEach(function(bundle) {

  Encore.addPlugin(new SVGSpritemapPlugin(`${bundle.sourcePath}/*.svg`, {
    output: {
      filename: bundle.bundleFile,
      svg: {
        sizes: false
      },
      svgo: {
        plugins: [
          {
            name: 'removeAttrs',
            params: {
              // Strip all fill attributes as icons are intended to inherit the
              // current colour of text they're displayed with.
              attrs: 'fill'
            }
          }
        ],
      },
    },
    sprite: {
      prefix: 'icon-',
      gutter: 0,
      generate: {
        title:  false,
        symbol: true,
        use:    true,
      }
    },
  }));

});

module.exports = Encore.getWebpackConfig();
