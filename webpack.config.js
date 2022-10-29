'use strict';

const autoprefixer = require('autoprefixer');
const glob = require('glob');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const SVGSpritemapPlugin = require('svg-spritemap-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

const isDev = (process.env.NODE_ENV !== 'production');

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
 * Get component paths discovered by glob.
 *
 * @return {Object}
 *   An object absolute component paths keyed by their relative, globbed paths.
 *
 * @see https://www.npmjs.com/package/glob
 */
function getComponentPaths() {

  let paths = {};

  for (const relativePath of glob.sync(
    `./!(${distPath})/**/components`
  )) {
    paths[relativePath] = path.resolve(__dirname, relativePath);
  }

  return paths;

};

const componentPaths = getComponentPaths();

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
    './!(' + distPath + ')/**/!(_)*.scss'
  ).reduce(function(entries, currentPath) {

      const parsed = path.parse(currentPath);

      entries[parsed.dir + '/' + parsed.name] = currentPath;

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

/**
 * Array of plug-in instances to pass to Webpack.
 *
 * @type {Array}
 */
let plugins = [
  new RemoveEmptyScriptsPlugin(),
  new MiniCssExtractPlugin(),
  new WebpackManifestPlugin({
    fileName: 'components.json',
    // This seeds the manifest with just the relative paths to the components.
    seed: Object.keys(componentPaths),
    // This overrides the default behaviour of the manifest plug-in where it
    // would include all of the entry items. We only want to output the
    // component directory paths for the time being.
    //
    // @todo Could we output those as well in case they're useful?
    generate: function(seed, files, entries) {
      return seed;
    }
  }),
];

iconBundles.forEach(function(bundle) {

  plugins.push(
    new SVGSpritemapPlugin(`${bundle.sourcePath}/*.svg`, {
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
                attrs: '(use|symbol|svg):fill'
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
    }),
  );

});

module.exports = {

  mode:     'development',
  devtool:  isDev ? 'eval':  false,

  entry: getGlobbedEntries,

  plugins: plugins,

  output: {

    path: path.resolve(__dirname, (outputToSourcePaths ? '.' : distPath)),

    // Be very careful with this - if outputting to the source paths, this must
    // not be true or it'll delete everything contained in the directory without
    // warning.
    clean: !outputToSourcePaths,

    // Since Webpack started out primarily for building JavaScript applications,
    // it always outputs a JS files, even if empty. We place these in a
    // temporary directory by default.
    filename: 'temp/[name].js',

  },

  module: {
    rules: [
      {
        test: /\.(scss)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
          },
          {
            loader: 'css-loader',
            options: {
              sourceMap: isDev,
              importLoaders: 2,
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: isDev,
              postcssOptions: {
                plugins: [
                  autoprefixer()
                ],
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: isDev,
              sassOptions: {
                includePaths: Object.values(componentPaths),
              }
            },
          },
        ],
      },
    ],
  }
};
