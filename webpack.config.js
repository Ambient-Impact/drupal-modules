'use strict';

const autoprefixer = require('autoprefixer');
const glob = require('glob');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');

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
const outputToSourcePaths = false;

/**
 * Get component paths discovered by glob.
 *
 * @return {String[]}
 *   An array of string absolute paths.
 *
 * @see https://www.npmjs.com/package/glob
 *
 * @todo Should we keep these as relative paths?
 */
function getComponentPaths() {

  return glob.sync(
    './!(' + distPath + ')/**/components'
  ).map(function(componentPath) {
    return path.resolve(__dirname, componentPath);
  });

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

module.exports = {

  mode:     'development',
  devtool:  isDev ? 'eval':  false,

  entry: getGlobbedEntries,

  plugins: [
    new MiniCssExtractPlugin(),
  ],

  output: {

    path: path.resolve(__dirname, distPath),
    // path: path.resolve(__dirname),

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
              url: {
                filter: function(url, resourcePath) {

                  if (url.includes('sprite.svg')) {
                    return false;
                  }

                  return true;

                },
              },
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
                includePaths: componentPaths,
              }
            },
          },
        ],
      },
    ],
  }
};
