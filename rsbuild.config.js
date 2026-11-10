/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
/* eslint-disable import/no-dynamic-require */
const fs = require('fs');
const path = require('path');

const createAdminConfig = (options = {}) => {
    const projectRootPath = options.projectRootPath || __dirname; // eslint-disable-line no-undef
    const nodeModulesPath = options.nodeModulesPath || path.resolve(projectRootPath, 'node_modules');
    const outputPath = (options.outputPath || 'build/admin').replace(/\\/g, '/').replace(/^\/+|\/+$/g, '');
    const composerConfig = JSON.parse(fs.readFileSync(path.resolve(projectRootPath, 'composer.json'), 'utf8'));
    const publicDir = composerConfig.extra && composerConfig.extra['public-dir'] || 'public';

    // The default must match SuluVersionPass.php.
    let suluVersion = '_._._';
    const composerLockPath = path.resolve(projectRootPath, 'composer.lock');
    if (fs.existsSync(composerLockPath)) {
        const composerLock = JSON.parse(fs.readFileSync(composerLockPath, 'utf8'));
        const suluPackage = composerLock.packages.find((packageItem) => packageItem.name === 'sulu/sulu');
        suluVersion = suluPackage ? suluPackage.version : suluVersion;
    }

    const {pluginBabel} = require(require.resolve('@rsbuild/plugin-babel', {paths: [nodeModulesPath]}));

    return {
        root: path.dirname(nodeModulesPath),
        plugins: [
            pluginBabel({
                // Strip Flow and transform legacy MobX decorators before SWC parses the source.
                babelLoaderOptions: (babelOptions) => ({
                    ...babelOptions,
                    configFile: path.resolve(path.dirname(nodeModulesPath), 'babel.config.json'),
                    presets: [],
                    plugins: [],
                }),
            }),
        ],
        source: {
            entry: {main: options.entry || path.resolve(__dirname, 'index.js')}, // eslint-disable-line no-undef
            include: [/node_modules[/\\](sulu-.*-bundle|@ckeditor|array-move|lodash-es|vanilla-colorful)[/\\]/],
            exclude: [/friendsofsymfony[/\\]jsrouting-bundle/],
            define: {
                SULU_ADMIN_BUILD_VERSION: JSON.stringify(suluVersion),
            },
        },
        resolve: {
            alias: {
                'fos-jsrouting': path.resolve(
                    projectRootPath,
                    'vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js'
                ),
            },
        },
        // Twig loads exactly one JS and one CSS entry from the Symfony asset manifest.
        splitChunks: false,
        dev: {assetPrefix: 'auto', hmr: false},
        server: {publicDir: false},
        output: {
            cleanDistPath: true,
            assetPrefix: 'auto',
            distPath: {
                root: path.resolve(projectRootPath, publicDir, outputPath),
                js: '',
                css: '',
                image: 'images',
                font: 'fonts',
            },
            filename: {
                js: '[name].[contenthash].js',
                css: '[name].[contenthash].css',
            },
            dataUriLimit: 0,
            polyfill: 'off', // Babel's preset-env retains the existing core-js usage polyfills.
            sourceMap: {js: 'source-map', css: true},
            cssModules: {
                auto: /\.scss$/,
                localIdentName: '[local]--[hash:base64:10]',
                exportLocalsConvention: 'camelCase',
                namedExport: false,
            },
            manifest: {
                prefix: false,
                generate: ({files}) => Object.fromEntries(files.map((file) => [
                    file.name,
                    '/' + outputPath + '/' + file.path,
                ])),
            },
        },
        tools: {
            htmlPlugin: false,
            bundlerChain: (chain, {CHAIN_ID}) => {
                // These files use PostCSS variables and nesting, rather than Sass.
                chain.module.rule(CHAIN_ID.RULE.CSS).test(/\.(css|scss)$/);
                // CKEditor imports SVG icons as strings; font SVGs in CSS remain emitted assets.
                chain.module.rule(CHAIN_ID.RULE.SVG).oneOf('sulu-icons').before(CHAIN_ID.ONE_OF.SVG_ASSET)
                    .issuer(/\.[cm]?jsx?$/).type('asset/source');
            },
            rspack: {
                resolve: {symlinks: false},
                resolveLoader: {symlinks: false},
                snapshot: {managedPaths: []},
                // Rspack 2 ignores node_modules by default, including the linked Sulu source packages.
                watchOptions: {ignored: /[/\\]\.git[/\\]/},
            },
        },
    };
};

module.exports = () => createAdminConfig(); // eslint-disable-line no-undef
module.exports.createAdminConfig = createAdminConfig; // eslint-disable-line no-undef
