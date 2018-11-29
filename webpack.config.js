/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules*/
const path = require('path');
const glob = require('glob');
const rimraf = require('rimraf');
const webpack = require('webpack');
const CleanObsoleteChunksPlugin = require('webpack-clean-obsolete-chunks');
const ManifestPlugin = require('webpack-manifest-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const {styles} = require('@ckeditor/ckeditor5-dev-utils'); // eslint-disable-line import/no-extraneous-dependencies

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

module.exports = (env, argv) => { // eslint-disable-line no-undef
    let publicDir = 'public';
    const basePath = env && env.base_path ? env.base_path : 'build/admin';

    const composerConfig = require(path.resolve('composer.json')); // eslint-disable-line import/no-dynamic-require
    if (composerConfig.extra && composerConfig.extra['public-dir']) {
        publicDir = composerConfig.extra['public-dir'];
    }

    if (publicDir.startsWith('/')) {
        const errorMessage = [
            '\x1b[31mUsing absolute path for \x1b[0m"public-dir"\x1b[31m detected',
            '       This can end up in accidentally remove of files outside your project dir.',
            '       Build was cancelled!\x1b[0m',
            ''
        ].join('\n');

        throw new Error(errorMessage);
    }

    // Remove old build files
    rimraf.sync(path.resolve(publicDir, basePath));

    return {
        entry: entries,
        output: {
            path: path.resolve(publicDir),
            filename: basePath + '/[name].[chunkhash].js',
        },
        devtool: argv.mode === 'development' ? 'eval-source-map' : 'source-map',
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
