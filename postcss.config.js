/* eslint-disable flowtype/require-valid-file-annotation */
module.exports = { //eslint-disable-line no-undef
    plugins: {
        'postcss-import': {
            root: path.resolve(process.cwd(), 'node_modules'),
            path: [path.resolve(process.cwd(), 'node_modules')],
        },
        'postcss-nested': {},
        'postcss-simple-vars': {},
        'postcss-calc': {},
        'postcss-hexrgba': {},
        'autoprefixer': {},
    },
};
