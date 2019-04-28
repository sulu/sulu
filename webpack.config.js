/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules*/
const fs = require('fs');
const path = require('path');
const webpack = require('webpack');
const CleanObsoleteChunksPlugin = require('webpack-clean-obsolete-chunks');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const {styles} = require('@ckeditor/ckeditor5-dev-utils'); // eslint-disable-line import/no-extraneous-dependencies

const babelConfig = JSON.parse(fs.readFileSync(path.resolve(__dirname, '.babelrc'))); // eslint-disable-line no-undef

module.exports = (env, argv) => { // eslint-disable-line no-undef
    let publicDir = 'public';
    const basePath = env && env.base_path ? env.base_path : 'build/admin';
    const rootPath = env && env.root_path ? env.root_path : __dirname; // eslint-disable-line no-undef

    const composerConfig = require(path.resolve('composer.json')); // eslint-disable-line import/no-dynamic-require
    if (composerConfig.extra && composerConfig.extra['public-dir']) {
        publicDir = composerConfig.extra['public-dir'];
    }

    const entries = [];

    entries.unshift('sulu-admin-bundle');
    entries.unshift('sulu-contact-bundle');
    entries.unshift('sulu-custom-url-bundle');
    entries.unshift('sulu-media-bundle');
    entries.unshift('sulu-page-bundle');
    entries.unshift('sulu-preview-bundle');
    entries.unshift('sulu-security-bundle');
    entries.unshift('sulu-snippet-bundle');
    entries.unshift('sulu-website-bundle');

    const entriesCount = entries.length;

    entries.unshift('core-js/fn/array/includes');
    entries.unshift('core-js/fn/array/find-index');
    entries.unshift('core-js/fn/array/fill');
    entries.unshift('core-js/fn/array/from');
    entries.unshift('core-js/fn/promise');
    entries.unshift('core-js/fn/symbol');
    entries.unshift('whatwg-fetch');
    entries.unshift('url-search-params-polyfill');
    entries.unshift('regenerator-runtime/runtime');

    return {
        entry: entries,
        output: {
            path: path.resolve(publicDir),
            filename: basePath + '/[name].[chunkhash].js',
        },
        devtool: argv.mode === 'development' ? 'eval-source-map' : 'source-map',
        plugins: [
            new CleanWebpackPlugin({
                cleanOnceBeforeBuildPatterns: [path.resolve(publicDir, basePath)],
            }),
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
        resolve: {
            alias: {
                'fos-jsrouting': path.resolve(
                    rootPath,
                    'vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js'
                ),
            },
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules\/(?!(sulu-(.*)-bundle|@ckeditor|lodash-es)\/)/,
                    use: {
                        loader: 'babel-loader',
                        options: babelConfig,
                    },
                },
                {
                    test: /\.css/,
                    exclude: /ckeditor5-[^/]+\/theme\/[\w-/]+\.css$/,
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
                                localIdentName: '[local]--[hash:base64:10]',
                            },
                        },
                        'postcss-loader',
                    ],
                },
                {
                    test: /ckeditor5-[^/]+\/theme\/icons\/[^/]+\.svg$/,
                    use: 'raw-loader',
                },
                {
                    test: /ckeditor5-[^/]+\/theme\/[\w-/]+\.css$/,
                    use: [
                        MiniCssExtractPlugin.loader,
                        {
                            loader: 'css-loader',
                        },
                        {
                            loader: 'postcss-loader',
                            options: styles.getPostCssConfig({
                                themeImporter: {
                                    themePath: require.resolve('@ckeditor/ckeditor5-theme-lark'),
                                },
                                minify: true,
                            }),
                        },
                    ],
                },
                {
                    test: /\.(svg|ttf|woff|woff2|eot)(\?.*$|$)/,
                    exclude: /ckeditor5-[^/]+\/theme\/icons\/[^/]+\.svg$/,
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
};
