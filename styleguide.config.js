/* eslint-disable flowtype/require-valid-file-annotation */
/* eslint-disable import/no-nodejs-modules */
const path = require('path');
const fs = require('fs');
const fg = require('fast-glob');

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

const isSection = (section) => (folderPath) => {
    return path.basename(path.dirname(folderPath)) === section;
};

const folders = fg.sync(
    ['./src/Sulu/Bundle/*/Resources/js/*/*', '!**/vendor', '!**/node_modules'],
    {onlyDirectories: true }
);

const componentFolders = folders
    .filter(isSection('components'))
    .filter((folder) => firstLetterIsUppercase(path.basename(folder)))
    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
    .sort(compareFolderName)
    .map((folder) => {
        const component = path.basename(folder);

        return path.join(folder, component + '.js');
    });
const containerFolders = folders
    .filter(isSection('containers'))
    .filter((folder) => firstLetterIsUppercase(path.basename(folder)))
    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
    .sort(compareFolderName)
    .map((folder) => {
        const component = path.basename(folder);

        return path.join(folder, component + '.js');
    });
const serviceSections = folders
    .filter(isSection('services'))
    .filter((folder) => path.basename(folder) !== 'index.js')
    .filter((folder) => javaScriptFileExists(folder, path.basename(folder)))
    .sort(compareFolderName)
    .map((folder) => {
        const component = path.basename(folder);

        return {name: component, content: folder + '/README.md'};
    });
const viewSections = folders
    .filter(isSection('views'))
    .map((folder) => {
        const component = path.basename(folder);
        return {name: component, content: folder + '/README.md'};
    });
const highOrderComponentSections = folders
    .filter((folder) => !firstLetterIsUppercase(path.basename(folder)))
    .filter(isSection('components'))
    .filter((folder) => path.basename(folder) !== 'index.js')
    .sort(compareFolderName)
    .map((folder) => {
        const component = path.basename(folder);

        return {name: component, content: folder + '/README.md'};
    });

module.exports = { // eslint-disable-line
    require: [
        'core-js/fn/array/includes',
        'core-js/fn/array/from',
        'core-js/fn/array/fill',
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
            name: 'Components',
            components: function() {
                return componentFolders;
            },
        },
        {
            name: 'Containers',
            components: function() {
                return containerFolders;
            },
        },
        {
            name: 'Services',
            sections: serviceSections,
        },
        {
            name: 'Views',
            sections: viewSections,
        },
        {
            name: 'Higher-Order components',
            sections: highOrderComponentSections,
        },
    ],
    webpackConfig: {
        devServer: {
            disableHostCheck: true,
        },
        devtool: 'source-map',
        module: {
            loaders: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    loader: 'babel-loader',
                },
                {
                    test: /\.css/,
                    use: [
                        'style-loader',
                        {
                            loader: 'css-loader',
                            options: {
                                modules: false,
                            },
                        },
                    ],
                },
                {
                    test: /\.(scss)$/,
                    use: [
                        'style-loader',
                        {
                            loader: 'css-loader',
                            options: {
                                modules: true,
                                camelCase: true,
                                importLoaders: 1,
                                localIdentName: '[local]--[hash:base64:10]',
                            },
                        },
                        'postcss-loader',
                    ],
                },
                {
                    test:/\.(jpg|gif|png)(\?.*$|$)/,
                    use: [
                        {
                            loader: 'file-loader',
                        },
                    ],
                },
                {
                    test:/\.(svg|ttf|woff|woff2|eot)(\?.*$|$)/,
                    use: [
                        {
                            loader: 'file-loader',
                        },
                    ],
                },
            ],
        },
    },
};
