// @flow

import React from 'react';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {action, observable, toJS} from 'mobx';
import {Overlay} from '../../components';
import Snackbar from '../../components/Snackbar';
import {Requester} from '../../services';
import {translate} from '../../utils';
import {ACCOUNT_LIMIT_MESSAGE_KEYS, readMessageKey} from '../AiApplication/accountLimits';
import translatorStyles from './translator.scss';
import Input from './Input';
import LanguageSelect from './LanguageSelect';
import type {LanguageType} from './types';

type Props = {|
    action?: React$ComponentType<Object>,
    actionProps?: Object,
    contactEmail?: ?string,
    locale: string,
    messages: {|
        allLanguages: string,
        contactAdmin: string,
        detected: string,
        errorTranslatingText: string,
        insert: string,
        searchLanguages: string,
        sourceLanguage: string,
        suggestedLanguages: string,
        targetLanguage: string,
        title: string,
    |},
    onConfirm: (text: string) => void,
    onDialogClose: () => void,
    resourceId?: ?(string | number),
    resourceKey?: ?string,
    sourceLanguages: Array<LanguageType>,
    suggestedLocales: Array<string>,
    targetLanguages: Array<LanguageType>,
    type: 'text_line' | 'text_area' | 'text_editor',
    url: string,
    value?: string,
    webspaceKey?: ?string,
|};

/**
 * @internal
 */
@observer
export default class Translator extends React.Component<Props> {
    @observable snackbarMessage: ?{ message: string, type: 'error' } = undefined;
    @observable accountLimit: ?string = undefined;
    @observable loading = false;
    @observable sourceText = '';
    @observable targetText = '';
    @observable sourceLanguage = undefined;
    @observable sourceSelectedOnce = false;
    @observable targetLanguage = undefined;
    @observable lastResponse = undefined;

    @action handleClose = () => {
        const {onDialogClose} = this.props;

        onDialogClose();
    };

    @action handleConfirm = () => {
        const {
            onConfirm,
        } = this.props;

        // We have to stop the propagation of the event to prevent the focus lose of the input / editor field
        // $FlowFixMe
        event.stopPropagation();

        onConfirm(this.targetText);
    };

    @action handleSnackbarCloseClick = () => {
        this.snackbarMessage = undefined;
    };

    @action componentDidMount() {
        this.targetLanguage = this.props.locale;
        this.sourceText = this.props.value;

        this.translateText(this.sourceText);
    }

    @action handleSourceTextChanged = (text: string) => {
        this.sourceText = text;

        this.translateText(text);
    };

    handleContactAdminClick = () => {
        const {contactEmail} = this.props;

        if (contactEmail) {
            window.location.href = 'mailto:' + contactEmail;
        }
    };

    translateText = debounce(action(() => {
        const {
            url,
            type,
            resourceId,
            resourceKey,
            webspaceKey,
            messages: {
                errorTranslatingText: errorTranslatingTextMessage,
            },
        } = this.props;

        if (this.accountLimit) {
            return;
        }

        this.loading = true;
        this.lastResponse = undefined;

        return Requester.post(
            url,
            {
                text: this.sourceText,
                sourceLanguage: this.sourceLanguage,
                targetLanguage: this.targetLanguage,
                type,
                resourceId,
                resourceKey,
                webspaceKey,
            }
        ).then(action((data: {
            response: {
                sourceLanguage: string,
                targetLanguage: string,
                text: string,
            },
        }) => {
            this.loading = false;
            this.targetText = data.response.text;
            this.lastResponse = data;

            this.targetLanguage = data.response.targetLanguage.toLowerCase();
            this.sourceLanguage = data.response.sourceLanguage.toLowerCase();

            return data;
        })).catch(action((error) => {
            this.loading = false;
            this.lastResponse = {error};

            return readMessageKey(error).then(action((messageKey: ?string) => {
                if (messageKey && ACCOUNT_LIMIT_MESSAGE_KEYS.includes(messageKey)) {
                    this.accountLimit = messageKey;

                    return;
                }

                this.snackbarMessage = {
                    message: errorTranslatingTextMessage,
                    type: 'error',
                };
            }));
        }));
    }), 500);

    @action handleSourceLanguageChanged = (locale: string) => {
        this.sourceLanguage = locale;
        this.sourceSelectedOnce = true;

        this.translateText(this.sourceText);
    };

    @action handleTargetLanguageChanged = (locale: string) => {
        this.targetLanguage = locale;

        this.translateText(this.sourceText);
    };

    render() {
        const {accountLimit} = this;
        const {
            contactEmail,
            type,
            sourceLanguages,
            suggestedLocales,
            targetLanguages,
            action: Action,
            messages: {
                allLanguages: allLanguagesMessage,
                contactAdmin: contactAdminMessage,
                title: titleMessage,
                insert: insertMessage,
                detected: detectedMessage,
                searchLanguages: searchLanguagesMessage,
                sourceLanguage: sourceLanguageMessage,
                suggestedLanguages: suggestedLanguagesMessage,
                targetLanguage: targetLanguageMessage,
            },
        } = this.props;

        const languageSelectMessages = {
            allLanguages: allLanguagesMessage,
            searchLanguages: searchLanguagesMessage,
            suggestedLanguages: suggestedLanguagesMessage,
        };

        const actionNode = Action ? (
            <Action
                {...(this.props.actionProps || {})}
                context={toJS(this.lastResponse)}
                source="translator"
            />
        ) : <React.Fragment />;

        return (
            <Overlay
                confirmDisabled={this.targetText === '' || !!this.accountLimit}
                confirmLoading={this.loading}
                confirmText={insertMessage}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={true}
                size="small"
                snackbarMessage={this.snackbarMessage?.message}
                snackbarType={this.snackbarMessage?.type}
                title={titleMessage}
            >
                {actionNode}

                <div className={translatorStyles.translator}>
                    <div className={translatorStyles.column}>
                        <div className={translatorStyles.select}>
                            <LanguageSelect
                                ariaLabel={sourceLanguageMessage}
                                languages={sourceLanguages}
                                messages={languageSelectMessages}
                                onChange={this.handleSourceLanguageChanged}
                                suffix={this.sourceSelectedOnce ? undefined : detectedMessage}
                                suggestedLocales={suggestedLocales}
                                value={this.sourceLanguage}
                            />
                        </div>
                        <Input
                            onChange={this.handleSourceTextChanged}
                            text={this.sourceText || ''}
                            type={type}
                        />
                    </div>
                    <div className={translatorStyles.column}>
                        <div className={translatorStyles.select}>
                            <LanguageSelect
                                ariaLabel={targetLanguageMessage}
                                languages={targetLanguages}
                                messages={languageSelectMessages}
                                onChange={this.handleTargetLanguageChanged}
                                suggestedLocales={suggestedLocales}
                                value={this.targetLanguage}
                            />
                        </div>
                        <Input
                            text={this.targetText}
                            type={type}
                        />
                    </div>
                </div>
                {accountLimit &&
                    <div className={translatorStyles.accountLimit}>
                        <Snackbar
                            actions={contactEmail
                                ? [
                                    {label: contactAdminMessage, onClick: this.handleContactAdminClick},
                                    {label: 'Second Link (Demo)', onClick: this.handleContactAdminClick},
                                ]
                                : undefined
                            }
                            message={translate(accountLimit + '_description')}
                            title={translate(accountLimit)}
                            type="error"
                        />
                    </div>
                }
            </Overlay>
        );
    }
}
