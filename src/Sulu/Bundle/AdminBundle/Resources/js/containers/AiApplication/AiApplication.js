// @flow

import React, {Component} from 'react';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import symfonyRouting from 'fos-jsrouting/router';
import {translate} from '../../utils';
import localizationStore from '../../stores/localizationStore';
import {FormInspector} from '../../containers';
import WritingAssistant from '../WritingAssistant';
import Translator from '../Translator';
import FeatureBadge from './FeatureBadge';
import ActionOverlay from './ActionOverlay';
import type {ExpertType} from '../WritingAssistant/types';

/* what a resource records about itself rather than about its content, across pages, articles and snippets */
const SYSTEM_FIELDS = [
    'author', 'authored', 'availableLocales', 'changed', 'changer', 'contentLocales', 'created', 'creator',
    'hasSub', 'id', 'internal', 'lastModified', 'lastModifiedEnabled', 'linked', 'localizedTemplate',
    'navContexts', 'nodeState', 'nodeType', 'order', 'originTemplate', 'parentUuid', 'path', 'published',
    'publishedState', 'shadowBaseLanguage', 'shadowLocales', 'shadowOn', 'template', 'webspace',
];

type Props = {|
    feedback: ?{
        enabled: boolean,
        formKey: string,
        route: string,
    },
    htmlFieldTypes: Array<string>,
    textFieldTypes: Array<string>,
    translation: {
        enabled: boolean,
        route: string,
        sourceLanguages: Array<{|
            label: string,
            locale: string,
        |}>,
        targetLanguages: Array<{|
            label: string,
            locale: string,
        |}>,
    },
    writingAssistant: {
        contactEmail?: ?string,
        enabled: boolean,
        experts: {
            [string]: ExpertType,
        },
        route: string,
    },
|};

/**
 * @internal
 */
@observer
export default class AiApplication extends Component<Props> {
    static defaultProps = {
        htmlFieldTypes: ['text_editor'],
        textFieldTypes: ['text_line', 'text_area'],
    };

    @observable selectedComponent: {
        dataPath: string,
        formInspector: FormInspector,
        getValue: () => string,
        isInsideBlock: boolean,
        name: string,
        schemaType: string,
        setValue: (value: string) => void,
    };
    @observable selectedText: string;
    @observable selectedRect: ClientRect;
    @observable selectedElement: HTMLElement;
    @observable writingAssistantOpen: boolean = false;
    @observable translateOpen: boolean = false;
    @observable hasFocus: boolean = false;
    @observable writingAssistantIdentifier: ?number = undefined;
    @observable translateIdentifier: ?number = undefined;

    componentDidMount() {
        ['scroll', 'resize'].forEach((eventName) => {
            window.addEventListener(eventName, this.handleScrollResize, true);
        });

        document.addEventListener('sulu.focus', this.handleSuluFocus);
        document.addEventListener('click', this.handleGlobalClick);
    }

    componentWillUnmount() {
        ['scroll', 'resize'].forEach((eventName) => {
            window.removeEventListener(eventName, this.handleScrollResize, true);
        });

        document.removeEventListener('sulu.focus', this.handleSuluFocus);
        document.removeEventListener('click', this.handleGlobalClick);
    }

    @action handleScrollResize = () => {
        if (this.selectedElement && this.selectedElement.parentElement) {
            this.selectedRect = this.selectedElement.parentElement.getBoundingClientRect();
        }
    };

    @action handleSuluFocus = (event: Event) => {
        if (this.translateOpen || this.writingAssistantOpen || !(event.target instanceof HTMLElement)) {
            return;
        }

        this.selectedElement = event.target;
        if (this.selectedElement.parentElement) {
            this.selectedRect = this.selectedElement.parentElement.getBoundingClientRect();
        }

        const detail: {
            dataPath: string,
            formInspector: FormInspector,
            getValue: () => string,
            schemaPath: string,
            schemaType: string,
            setValue: (value: string) => void,
        // $FlowFixMe
        } = event.detail;
        if (!detail) {
            return;
        }

        this.selectedComponent = {
            dataPath: detail.dataPath,
            formInspector: detail.formInspector,
            getValue: detail.getValue,
            isInsideBlock: this.isInsideBlock(detail.formInspector, detail.schemaPath),
            name: detail.schemaPath.split('/').slice(0, -1)[0],
            schemaType: detail.schemaType,
            setValue: detail.setValue,
        };
        this.selectedText = this.selectedComponent.getValue();
        this.setFocus(true);
    };

    @action handleGlobalClick = (event: Event) => {
        if ((event.target instanceof HTMLElement) && !this.isRelevantElement(event.target)) {
            this.hasFocus = false;
        }
    };

    @action setFocus = (focused: boolean) => {
        this.hasFocus = focused;
    };

    isRelevantElement(element: HTMLElement) {
        return element.matches('input, textarea, [contenteditable]')
            || element.closest('[contenteditable]');
    }

    isInsideBlock(formInspector: FormInspector, schemaPath: string) {
        const parts = schemaPath.split('/');

        for (let i = 2; i < parts.length; i++) {
            const path = '/' + parts.slice(1, i).join('/');

            try {
                const schema = formInspector.getSchemaEntryByPath(path);
                if (schema?.type === 'block') {
                    return true;
                }
            } catch (e) {
                return false;
            }
        }

        return false;
    }

    moveCursorToEnd = (element: HTMLInputElement) => {
        element.focus();
        if (typeof element.selectionStart === 'number') {
            // For input and textarea elements
            element.selectionStart = element.selectionEnd = element.value.length;
        } else if (window.getSelection && document.createRange) {
            // For contenteditable elements
            const range = document.createRange();
            range.selectNodeContents(element);
            range.collapse(false);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
        }
    };

    @action handleWritingAssistantClose = () => {
        this.selectedText = this.selectedComponent.getValue();
        this.writingAssistantOpen = false;
        this.setFocus(false);
    };

    @action handleWritingAssistantConfirm = (optimizedText: string) => {
        this.selectedComponent.setValue(optimizedText);

        if (this.selectedElement instanceof HTMLInputElement) {
            this.moveCursorToEnd(this.selectedElement);
        }
        this.writingAssistantOpen = false;
        this.setFocus(true);
    };

    @action handleTranslateClose = () => {
        this.selectedText = this.selectedComponent.getValue();
        this.translateOpen = false;
        this.setFocus(false);
    };

    @action handleTranslateConfirm = (translatedText: string) => {
        this.selectedComponent.setValue(translatedText);

        if (this.selectedElement instanceof HTMLInputElement) {
            this.moveCursorToEnd(this.selectedElement);
        }
        this.translateOpen = false;
        this.setFocus(true);
    };

    @computed get position() {
        return {
            position: 'absolute',
            bottom: this.selectedRect ? (window.innerHeight - this.selectedRect.top + this.delta) + 'px' : 0,
            right: this.selectedRect ? (window.innerWidth - this.selectedRect.right) + 'px' : 0,
        };
    }

    @computed get delta() {
        const schemaType = this.selectedComponent?.schemaType;
        if (schemaType && this.canonicalFieldType(schemaType) === 'text_editor') {
            return 50;
        }

        return 5;
    }

    canonicalFieldType(schemaType: string): 'text_line' | 'text_area' | 'text_editor' {
        if (schemaType === 'text_line' || schemaType === 'text_area' || schemaType === 'text_editor') {
            return schemaType;
        }

        if (this.props.htmlFieldTypes.includes(schemaType)) {
            return 'text_editor';
        }

        return 'text_line';
    }

    @action handleWritingAssistantOpen = () => {
        this.selectedText = this.selectedComponent.getValue();

        this.writingAssistantOpen = true;
        this.writingAssistantIdentifier = Math.floor(Math.random() * 10000000);
    };

    @action handleTranslateOpen = () => {
        this.selectedText = this.selectedComponent.getValue();

        this.translateOpen = true;
        this.translateIdentifier = Math.floor(Math.random() * 10000000);
    };

    @computed get writingAssistantUrl() {
        return symfonyRouting.generate(this.props.writingAssistant.route, {
            chatId: this.writingAssistantIdentifier,
        });
    }

    @computed get translationUrl() {
        return symfonyRouting.generate(this.props.translation.route, {
            translateId: this.translateIdentifier,
        });
    }

    @computed get suggestedLocales(): Array<string> {
        return localizationStore.localizations.map((localization) => localization.locale);
    }

    /* the whole record is offered, not just the fields of the open tab: writing SEO text needs the page content,
       which the SEO form does not describe. everything collected here ends up verbatim in the prompt, so the
       bookkeeping the resource carries alongside its content is left out */
    @computed get contentData(): ?Object {
        const data = this.selectedComponent?.formInspector?.formStore?.data;
        if (!data) {
            return undefined;
        }

        const contentData = {};

        Object.keys(data).forEach((key) => {
            if (key.startsWith('_') || SYSTEM_FIELDS.includes(key)) {
                return;
            }

            const value = toJS(data[key]);
            if (value !== undefined && value !== null && value !== '') {
                contentData[key] = value;
            }
        });

        return Object.keys(contentData).length > 0 ? contentData : undefined;
    }

    @computed get actionUrl() {
        if (!this.props.feedback) {
            return undefined;
        }

        return symfonyRouting.generate(this.props.feedback.route);
    }

    render() {
        const {
            writingAssistant: {
                enabled: writingAssistantEnabled,
            },
            translation: {
                enabled: translationEnabled,
            },
        } = this.props;

        if (!this.hasFocus
            && !this.writingAssistantOpen
            && !this.translateOpen
        ) {
            return null;
        }

        const locale = this.selectedComponent.formInspector.locale?.get().toLowerCase();
        if (!locale) {
            return null;
        }

        const schemaType = this.selectedComponent?.schemaType || 'text_line';
        if (!this.props.textFieldTypes.includes(schemaType)
            && !this.props.htmlFieldTypes.includes(schemaType)
        ) {
            return null;
        }

        const canonicalType = this.canonicalFieldType(schemaType);

        return (
            <div style={this.position}>
                {!this.writingAssistantOpen && !this.translateOpen && (
                    <FeatureBadge
                        messages={{
                            translate: translate('sulu_admin.translator'),
                            writingAssistant: translate('sulu_admin.writing_assistant'),
                        }}
                        onTranslateClick={translationEnabled ? this.handleTranslateOpen : undefined}
                        onWritingAssistantClick={writingAssistantEnabled ? this.handleWritingAssistantOpen : undefined}
                        skin={this.selectedComponent.isInsideBlock ? 'gray' : 'white'}
                    />
                )}
                {this.writingAssistantOpen && writingAssistantEnabled && (
                    <WritingAssistant
                        action={this.props.feedback?.enabled ? ActionOverlay : undefined}
                        actionProps={{
                            formKey: this.props.feedback?.formKey,
                            url: this.actionUrl,
                        }}
                        configuration={this.props.writingAssistant}
                        contactEmail={this.props.writingAssistant.contactEmail}
                        contentData={this.contentData}
                        dataPath={this.selectedComponent.dataPath}
                        locale={locale}
                        messages={{
                            addMessage: translate('sulu_admin.writing_assistant_prompt_placeholder'),
                            contactAdmin: translate('sulu_admin.contact_admin'),
                            copiedToClipboard: translate('sulu_admin.sucessfully_copied_to_clipboard'),
                            experts: translate('sulu_admin.experts'),
                            includeContentContext: translate('sulu_admin.include_content_context'),
                            includeContentContextInfo: translate('sulu_admin.include_content_context_info'),
                            initialMessage: translate('sulu_admin.selected_text'),
                            morePredefinedPrompts: translate('sulu_admin.more_predefined_prompts'),
                            predefinedPrompts: translate('sulu_admin.predefined_prompts'),
                            requestFailed: translate('sulu_admin.request_failed'),
                            requestFailedDescription: translate('sulu_admin.request_failed_description'),
                            send: translate('sulu_admin.send'),
                            tryAgain: translate('sulu_admin.try_again'),
                            writingAssistant: translate('sulu_admin.writing_assistant'),
                        }}
                        onConfirm={this.handleWritingAssistantConfirm}
                        onDialogClose={this.handleWritingAssistantClose}
                        resourceId={this.selectedComponent.formInspector.id}
                        resourceKey={this.selectedComponent.formInspector.resourceKey}
                        type={canonicalType}
                        url={this.writingAssistantUrl}
                        value={this.selectedText}
                        webspaceKey={this.selectedComponent.formInspector.options?.webspace}
                    />
                )}
                {this.translateOpen && translationEnabled && (
                    <Translator
                        action={this.props.feedback?.enabled ? ActionOverlay : undefined}
                        actionProps={{
                            formKey: this.props.feedback?.formKey,
                            url: this.actionUrl,
                        }}
                        contactEmail={this.props.writingAssistant.contactEmail}
                        locale={locale}
                        messages={{
                            allLanguages: translate('sulu_admin.all_languages'),
                            contactAdmin: translate('sulu_admin.contact_admin'),
                            title: translate('sulu_admin.translator'),
                            insert: translate('sulu_admin.insert'),
                            detected: translate('sulu_admin.detected'),
                            errorTranslatingText: translate('sulu_admin.translator_error'),
                            searchLanguages: translate('sulu_admin.search_languages'),
                            sourceLanguage: translate('sulu_admin.source_language'),
                            targetLanguage: translate('sulu_admin.target_language'),
                            suggestedLanguages: translate('sulu_admin.suggested_languages'),
                        }}
                        onConfirm={this.handleTranslateConfirm}
                        onDialogClose={this.handleTranslateClose}
                        resourceId={this.selectedComponent.formInspector.id}
                        resourceKey={this.selectedComponent.formInspector.resourceKey}
                        sourceLanguages={this.props.translation.sourceLanguages}
                        suggestedLocales={this.suggestedLocales}
                        targetLanguages={this.props.translation.targetLanguages}
                        type={canonicalType}
                        url={this.translationUrl}
                        value={this.selectedText}
                        webspaceKey={this.selectedComponent.formInspector.options?.webspace}
                    />
                )}
            </div>
        );
    }
}
