// @flow
import {initializer} from 'sulu-admin-bundle/services';
import {sidebarRegistry} from 'sulu-admin-bundle/containers';
import Preview, {PreviewApplication, PreviewSidebar, PreviewStore} from './containers';
import React from 'react';
import Config from 'sulu-admin-bundle/services/Config';
import {webspaceStore} from 'sulu-page-bundle/stores';
import Requester from 'sulu-admin-bundle/services/Requester';
import {setTranslations} from 'sulu-admin-bundle/utils/Translator';
import {render} from 'react-dom';

initializer.addUpdateConfigHook('sulu_preview', (config: Object) => {
    PreviewStore.endpoints = config.endpoints;
    PreviewSidebar.mode = config.mode;
    PreviewSidebar.debounceDelay = config.debounceDelay;
    PreviewApplication.debounceDelay = config.debounceDelay;
    Preview.audienceTargeting = config.audienceTargeting;

    if (config.mode === 'off') {
        sidebarRegistry.disable('sulu_preview.preview');
    }
});

sidebarRegistry.add('sulu_preview.preview', PreviewSidebar);

function getBrowserLanguage() {
    // detect browser locale (ie, ff, chrome fallbacks)
    const language = window.navigator.languages ? window.navigator.languages[0] : null;

    return language || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;
}

function getDefaultLocale() {
    const browserLanguage = getBrowserLanguage();

    // select only language
    const locale = browserLanguage.slice(0, 2).toLowerCase();
    if (Config.translations.indexOf(locale) === -1) {
        return Config.fallbackLocale;
    }

    return locale;
}

function startPublicPreview() {
    const id = 'preview';
    const previewContainer = document.getElementById(id);
    const locale = getDefaultLocale();

    if (!previewContainer) {
        return false;
    }

    webspaceStore.setWebspaces(Object.values(SULU_CONFIG.webspaces));

    Requester.get(SULU_CONFIG.endpoints.translations + '?locale=' + locale).then((translations) => {
        setTranslations(translations, locale);
    }).then(() => {
        render(
            <PreviewApplication/>,
            previewContainer
        );
    });

    return true;
}

export {startPublicPreview};
