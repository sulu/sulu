/* eslint-disable */

module.exports = function (api) {
    api.cache(true);

    const presets = [
        '@babel/preset-env',
        '@babel/preset-react',
        '@babel/preset-flow'
    ];

    const plugins = [
        ["module-resolver", {
            "root": "./",
            "alias": {
                "sulu-admin-bundle": "./node_modules/sulu-admin-bundle",
                "sulu-contact-bundle": "./node_modules/sulu-contact-bundle",
                "sulu-custom-url-bundle": "./node_modules/sulu-custom-url-bundle",
                "sulu-media-bundle": "./node_modules/sulu-media-bundle",
                "sulu-page-bundle": "./node_modules/sulu-page-bundle",
                "sulu-preview-bundle": "./node_modules/sulu-preview-bundle",
                "sulu-security-bundle": "./node_modules/sulu-security-bundle",
                "sulu-snippet-bundle": "./node_modules/sulu-snippet-bundle",
                "sulu-website-bundle": "./node_modules/sulu-website-bundle"
            },
        }],
        ['@babel/plugin-proposal-decorators', {'legacy': true}],
        ['@babel/plugin-proposal-object-rest-spread', {}],
        ['@babel/plugin-proposal-class-properties', {'loose': true}],
    ];

    return {
        presets,
        plugins
    };
};
