/* eslint-disable */

module.exports = function (api) {
    api.cache(true);

    const presets = [
        '@babel/preset-env',
        '@babel/preset-react',
        '@babel/preset-flow'
    ];

    const plugins = [
        ['@babel/plugin-proposal-decorators', {'legacy': true}],
        ['@babel/plugin-proposal-object-rest-spread', {}],
        ['@babel/plugin-proposal-class-properties', {'loose': true}],
    ];

    return {
        presets,
        plugins
    };
};
