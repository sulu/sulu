/* eslint-disable flowtype/require-valid-file-annotation */
const glob = require('glob');
const path = require('path');

module.exports = { // eslint-disable-line
    components: function() {
        const folders = glob.sync('./src/Sulu/Bundle/*/Resources/js/components/*');

        return folders.map((folder) => {
            const component = path.basename(folder);
            return path.join(folder, component + '.js');
        });
    },
    webpackConfig: {
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
                        {
                            loader: 'css-loader',
                            options: {
                                modules: false,
                            },
                        },
                    ],
                },
                {
                    test:/\.(svg|ttf|woff|woff2|eot)(\?.*$|$)/,
                    use: [
                        {
                            loader: 'null-loader',
                        },
                    ],
                },
            ],
        },
    },
};
