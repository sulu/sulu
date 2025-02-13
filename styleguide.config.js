/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
const path = require('path');
const fs = require('fs');
const webpack = require('webpack');
const glob = require('glob');
const webpackConfig = require('./webpack.config.js');

const firstLetterIsUppercase = (string) => {
    const first = string.charAt(0);
    return first === first.toUpperCase();
};

const compareFolderName = (folderA, folderB) => {
    folderA = path.basename(folderA).toUpperCase();
    folderB = path.basename(folderB).toUpperCase();

    if (folderA < folderB) {
        return -1;
    }

    if (folderA > folderB) {
        return 1;
    }

    return 0;
};

const javaScriptFileExists = (path, fileName) => {
    return fs.existsSync(`${path}/${fileName}.js`);
};

module.exports = { // eslint-disable-line
    title: 'Sulu Javascript Docs',
    require: [
        './src/Sulu/Bundle/AdminBundle/Resources/js/containers/Application/global.scss',
        './src/Sulu/Bundle/AdminBundle/Resources/js/containers/Application/styleguidist.scss',
    ],
    styles: {
        Playground: {
            preview: {
                background: '#f5f5f5',
            },
        },
    },
    sections: [
        {
            name: 'Views',
            sections: (function() {
                const folders = glob.sync('./{src/Sulu/Bundle/*/Resources,packages/*/assets}/js/views/*');

                return folders
                    .filter((folder) => path.basename(folder) !== 'index.js')
                    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
                    .map((folder) => {
                        const component = path.basename(folder);
                        return {name: component, content: folder + '/README.md'};
                    });
            })(),
        },
        {
            name: 'Services',
            sections: (function() {
                const folders = glob.sync('./{src/Sulu/Bundle/*/Resources,packages/*/assets}/js/services/*');

                return folders
                    .filter((folder) => path.basename(folder) !== 'index.js')
                    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
                    .sort(compareFolderName)
                    .map((folder) => {
                        const component = path.basename(folder);

                        return {name: component, content: folder + '/README.md'};
                    });
            })(),
        },
        {
            name: 'Containers',
            components() {
                let folders = glob.sync('./{src/Sulu/Bundle/*/Resources,packages/*/assets}/js/containers/*');
                // filter out containers
                folders = folders
                    .filter((folder) => firstLetterIsUppercase(path.basename(folder)))
                    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
                    .sort(compareFolderName);

                return folders.map((folder) => {
                    const component = path.basename(folder);

                    return path.join(folder, component + '.js');
                });
            },
        },
        {
            name: 'Higher-Order components',
            sections: (function() {
                let folders = glob.sync('./{src/Sulu/Bundle/*/Resources,packages/*/assets}/js/components/*');
                folders = folders.filter((folder) => !firstLetterIsUppercase(path.basename(folder)));

                return folders
                    .filter((folder) => path.basename(folder) !== 'index.js')
                    .sort(compareFolderName)
                    .map((folder) => {
                        const component = path.basename(folder);

                        return {name: component, content: folder + '/README.md'};
                    });
            })(),
        },
        {
            name: 'Components',
            components() {
                let folders = glob.sync('./{src/Sulu/Bundle/*/Resources,packages/*/assets}/js/components/*');
                // filter out higher order components
                folders = folders
                    .filter((folder) => firstLetterIsUppercase(path.basename(folder)))
                    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
                    .sort(compareFolderName);

                return folders.map((folder) => {
                    const component = path.basename(folder);

                    return path.join(folder, component + '.js');
                });
            },
        },
    ],
    webpackConfig: (env, argv) => {
        const config = webpackConfig(env, argv);

        config.plugins.push(
            new webpack.DefinePlugin({
                SULU_CONFIG: {},
            })
        );

        // set alias for "fos-jsrouting" package to allow for building styleguide without composer dependencies
        delete config.resolve.alias['fos-jsrouting'];
        // eslint-disable-next-line no-undef
        config.resolve.alias['fos-jsrouting/router'] = path.resolve(__dirname, 'tests/js/mocks/empty.js');

        return config;
    },
};
