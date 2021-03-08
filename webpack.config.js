/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
/* eslint-disable import/no-dynamic-require */
const fs = require('fs');
const path = require('path');

module.exports = (env, argv) => { // eslint-disable-line no-undef
    let publicDir = 'public';
    const outputPath = env && env.output_path ? env.output_path : path.join('build', 'admin');
    // eslint-disable-next-line no-undef
    const projectRootPath = env && env.project_root_path ? env.project_root_path : __dirname;
    const nodeModulesPath = env && env.node_modules_path
        ? env.node_modules_path
        : path.resolve(projectRootPath, 'node_modules');

    const composerConfig = require(path.resolve(projectRootPath, 'composer.json'));
    if (composerConfig.extra && composerConfig.extra['public-dir']) {
        publicDir = composerConfig.extra['public-dir'];
    }

    // default value for version must match default value in SuluVersionPass.php
    let suluVersion = '_._._';
    if (fs.existsSync(path.resolve(projectRootPath, 'composer.lock'))) {
        const composerLock = JSON.parse(fs.readFileSync(path.resolve(projectRootPath, 'composer.lock')));
        const suluPackage = composerLock.packages.find((packageItem) => packageItem.name === 'sulu/sulu');
        suluVersion = suluPackage ? suluPackage.version : suluVersion;
    }

    const webpack = require(path.resolve(nodeModulesPath, 'webpack'));
    const CleanObsoleteChunksPlugin = require(path.resolve(nodeModulesPath, 'webpack-clean-obsolete-chunks'));
    const CleanWebpackPlugin = require(path.resolve(nodeModulesPath, 'clean-webpack-plugin')).CleanWebpackPlugin;
    const ManifestPlugin = require(path.resolve(nodeModulesPath, 'webpack-manifest-plugin'));
    const MiniCssExtractPlugin = require(path.resolve(nodeModulesPath, 'mini-css-extract-plugin'));
    const OptimizeCssAssetsPlugin = require(path.resolve(nodeModulesPath, 'optimize-css-assets-webpack-plugin'));
    const {styles} = require(path.resolve(nodeModulesPath, '@ckeditor/ckeditor5-dev-utils'));

    return {
        entry: [path.resolve(__dirname, 'index.js')], // eslint-disable-line no-undef
        output: {
            path: path.resolve(projectRootPath, publicDir),
            filename: outputPath + '/[name].[chunkhash].js',
        },
        devtool: argv.mode === 'development' ? 'eval-source-map' : 'source-map',
        plugins: [
            new CleanWebpackPlugin({
                cleanOnceBeforeBuildPatterns: [path.resolve(projectRootPath, publicDir, outputPath)],
                dangerouslyAllowCleanPatternsOutsideProject: true,
                dry: false,
            }),
            new MiniCssExtractPlugin({
                filename: outputPath + '/[name].[chunkhash].css',
            }),
            new OptimizeCssAssetsPlugin(),
            new ManifestPlugin({
                fileName: outputPath + '/manifest.json',
            }),
            new CleanObsoleteChunksPlugin(),
            new webpack.DefinePlugin({
                SULU_ADMIN_BUILD_VERSION: JSON.stringify(suluVersion),
            }),
        ],
        resolve: {
            alias: {
                'fos-jsrouting': path.resolve(
                    projectRootPath,
                    'vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js'
                ),
            },
            modules: ['node_modules', nodeModulesPath],
        },
        resolveLoader: {
            modules: ['node_modules', nodeModulesPath],
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules\/(?!(sulu-(.*)-bundle|@ckeditor|lodash-es)\/)/,
                    use: {
                        loader: 'babel-loader',
                        options: {
                            "cacheDirectory": true,
                            "cacheCompression": false,
                        }
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
                                modules: {
                                    localIdentName: '[local]--[hash:base64:10]',
                                },
                                importLoaders: 1,
                                localsConvention: 'camelCase',
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
                                    themePath: require.resolve(
                                        path.resolve(nodeModulesPath, '@ckeditor/ckeditor5-theme-lark')
                                    ),
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
                                name: '/' + outputPath + '/fonts/[name].[hash].[ext]',
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
                                name: '/' + outputPath + '/images/[name].[hash].[ext]',
                            },
                        },
                    ],
                },
            ],
        },
    };
};
