/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules*/
const path = require('path');
const glob = require('glob');
const webpack = require('webpack');
const CleanObsoleteChunksPlugin = require('webpack-clean-obsolete-chunks');
const ManifestPlugin = require('webpack-manifest-plugin');

const entries = glob.sync(
    path.resolve(__dirname, 'src/Sulu/Bundle/*/Resources/js/index.js') // eslint-disable-line no-undef
);
const entriesCount = entries.length;

entries.unshift('core-js/fn/array/includes');
entries.unshift('core-js/fn/array/find-index');
entries.unshift('core-js/fn/array/fill');
entries.unshift('core-js/fn/array/from');
entries.unshift('core-js/fn/promise');
entries.unshift('core-js/fn/symbol');
entries.unshift('whatwg-fetch');
entries.unshift('url-search-params-polyfill');

const MiniCssExtractPlugin = require('mini-css-extract-plugin');

const basePath = 'admin/build';

module.exports = { // eslint-disable-line no-undef
    entry: entries,
    output: {
        path: path.resolve('web'),
        filename: basePath + '/[name].[chunkhash].js',
    },
    plugins: [
        new webpack.DefinePlugin({
            BUNDLE_ENTRIES_COUNT: entriesCount,
        }),
        new MiniCssExtractPlugin({
            filename: basePath + '/[name].[chunkhash].css',
        }),
        new ManifestPlugin({
            fileName: basePath + '/manifest.json',
        }),
        new CleanObsoleteChunksPlugin(),
    ],
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: 'babel-loader',
            },
            {
                test: /\.css/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                ],
            },
            {
                test: /\.(scss)$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            modules: true,
                            importLoaders: 1,
                            camelCase: true,
                            localeIdentName: '[local]--[hash:base64:10]',
                        },
                    },
                    'postcss-loader',
                ],
            },
            {
                test: /\.(svg|ttf|woff|woff2|eot)(\?.*$|$)/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '/' + basePath + '/fonts/[name].[hash].[ext]',
                        },
                    },
                ],
            },
            {
                test: /\.(jpg|gif|png)(\?.*$|$)/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '/' + basePath + '/images/[name].[hash].[ext]',
                        },
                    },
                ],
            },
        ],
    },
};
