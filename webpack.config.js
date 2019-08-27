/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules*/
const fs = require('fs');
const path = require('path');
const CleanObsoleteChunksPlugin = require('webpack-clean-obsolete-chunks');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCssAssetsPlugin = require('optimize-css-assets-webpack-plugin');
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

    return {
        entry: [path.resolve(__dirname, 'assets/admin/index.js')], // eslint-disable-line no-undef
        output: {
            path: path.resolve(publicDir),
            filename: basePath + '/[name].[chunkhash].js',
        },
        devtool: argv.mode === 'development' ? 'eval-source-map' : 'source-map',
        plugins: [
            new CleanWebpackPlugin({
                cleanOnceBeforeBuildPatterns: [path.resolve(publicDir, basePath)],
            }),
            new MiniCssExtractPlugin({
                filename: basePath + '/[name].[chunkhash].css',
            }),
            new OptimizeCssAssetsPlugin(),
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
                    // eslint-disable-next-line max-len
                    exclude: /node_modules\/(?!(sulu-(.*)-bundle|@ckeditor|lodash-es|jexl|isemail|query-string|strict-uri-encode|split-on-first)\/)/,
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
