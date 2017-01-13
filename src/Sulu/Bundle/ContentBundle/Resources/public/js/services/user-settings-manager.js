/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

define(['config'], function(Config) {
    return {
        /**
         * Returns the the language of the content which is displayed at the beginning.
         * It first look for the last chosen language for this webspace. If no such
         * language exists, the function looks for the last chosen language over all
         * webspaces and if that language is possible in with the given webspace. If
         * not language can be found, the default language is returned.
         *
         * @param {String} webspace The webspace key
         * @returns {string} The locale of the content
         */
        getContentLocale: function(webspace) {
            // webspace language
            var language = app.sandbox.sulu.getUserSetting(webspace + '.contentLanguage');
            if (!!language) {
                return language;
            }

            // last visited language over all webspaces
            language = app.sandbox.sulu.getUserSetting('contentLanguage');
            if (!!language && !!Config.get('sulu-content').locales[webspace][language]) {
                return language;
            }

            if (!Config.get('sulu-content').locales[webspace]
                || Object.keys(Config.get('sulu-content').locales[webspace]).length === 0) {
                app.logger.error('Webspace "' + webspace + '" has no defined locale');
            }

            // the default locale of a webspace is always on the first position
            language = Object.keys(Config.get('sulu-content').locales[webspace])[0];

            return Config.get('sulu-content').locales[webspace][language].localization;
        }
    };
});
