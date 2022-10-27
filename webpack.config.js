'use strict';

const autoprefixer = require('autoprefixer');
const globEntry = require('webpack-glob-entry');
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const isDev = (process.env.NODE_ENV !== 'production');

const glob = require('glob');

const componentPaths = [
  'ambientimpact_block/components',
  'ambientimpact_core/components',
  'ambientimpact_icon/components',
  'ambientimpact_media/components',
  'ambientimpact_ux/components',
  'ambientimpact_web/ambientimpact_web_components/components',
];

console.log(glob.sync('./ambientimpact_ux/components/**/!(_)*.scss').reduce(function(acc, path) {
    const entry = path.replace('/index.js', '');
    acc[entry] = path;
    return acc;
}, {}))

module.exports = {
  mode: 'development',
  entry: globEntry(
    './ambientimpact_block/components/**/!(_)*.scss',
    './ambientimpact_core/components/**/!(_)*.scss',
    './ambientimpact_icon/components/**/!(_)*.scss',
    './ambientimpact_media/components/**/!(_)*.scss',
    './ambientimpact_ux/components/**/!(_)*.scss',
    './ambientimpact_web/ambientimpact_web_components/components/**/!(_)*.scss',
  ),
  plugins: [
    new MiniCssExtractPlugin({
      // // Options similar to the same options in webpackOptions.output
      // // all options are optional
      // filename: "[name].css",
      // chunkFilename: "[id].css",
      // ignoreOrder: false, // Enable to remove warnings about conflicting order
    }),
  ],
  output: {
    clean: true,
    // filename: 'js/[name].js',
    // chunkFilename: 'js/async/[name].chunk.js',
    path: path.resolve(__dirname, 'dist'),
    // pathinfo: true,
    // publicPath: '../../',
  },
  module: {
    rules: [
      {
        test: /\.(css|scss)$/,
        // test: /\/[^_][^\/]+\.(css|scss)$/,
        use: [
          {
            loader: MiniCssExtractPlugin.loader,
            // options: {
            //   name: '[name].[ext]?[hash]',
            // }
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
                includePaths: [
                  path.resolve(__dirname, 'ambientimpact_block/components'),
                  path.resolve(__dirname, 'ambientimpact_core/components'),
                  path.resolve(__dirname, 'ambientimpact_icon/components'),
                  path.resolve(__dirname, 'ambientimpact_media/components'),
                  path.resolve(__dirname, 'ambientimpact_ux/components'),
                  path.resolve(__dirname, 'ambientimpact_web/ambientimpact_web_components/components'),
                ],
              }
            },
          },
        ],
      },
    ],
  }
};
