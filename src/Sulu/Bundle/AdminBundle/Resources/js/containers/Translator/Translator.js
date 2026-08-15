// src/Sulu/Bundle/AdminBundle/Resources/js/containers/Translator/Translator.js
// @flow

import React from 'react';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {action, observable, toJS} from 'mobx';
import {Overlay, SingleSelect} from '../../components';
import {Requester} from '../../services';
import translatorStyles from './translator.scss';
import Input from './Input';
import TranslationAlternatives from './TranslationAlternatives';

// Import dummy data (in a real implementation, this would come from API calls)
import {DUMMY_TEXT, DUMMY_SEGMENTS, DUMMY_ALTERNATIVES} from './dummyData';

type Props = {|
    action?: React$ComponentType<Object>,
    actionProps?: Object,
    locale: string,
    messages: {|
        detected: string,
        errorTranslatingText: string,
        insert: string,
        title: string,
        alternatives: string, // "Alternative translations"
        noAlternatives: string, // "No alternatives available"
    |},
    onConfirm: (text: string) => void,
    onDialogClose: () => void,
    resourceId?: ?(string | number),
    resourceKey?: ?string,
    sourceLanguages: Array<{|
        label: string,
        locale: string,
    |}>,
    targetLanguages: Array<{|
        label: string,
        locale: string,
    |}>,
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
    @observable loading = false;
    @observable sourceText = '';
    @observable targetText = '';
    @observable sourceLanguage = undefined;
    @observable sourceSelectedOnce = false;
    @observable targetLanguage = undefined;
    @observable lastResponse = undefined;

    // New observables for alternatives feature
    @observable segments = [];
    @observable selectedSegment = null;
    @observable alternatives = [];
    @observable alternativesLoading = false;

    // ... existing methods ...

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

    @action componentDidMount() {
        this.targetLanguage = this.props.locale;
        this.sourceText = this.props.value;

        this.translateText(this.sourceText);
    }

    @action handleSourceTextChanged = (text: string) => {
        this.sourceText = text;

        this.translateText(text);
    };

    @action resetAlternatives = () => {
        this.selectedSegment = null;
        this.alternatives = [];
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

        this.loading = true;
        this.lastResponse = undefined;
        this.resetAlternatives();

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
            this.targetText = DUMMY_TEXT;
            this.lastResponse = data;

            this.targetLanguage = data.response.targetLanguage.toLowerCase();
            this.sourceLanguage = data.response.sourceLanguage.toLowerCase();

            // Add dummy segments for the translated text
            this.segments = this.getDummySegments(this.targetText);

            return data;
        })).catch(action((error) => {
            this.loading = false;
            this.lastResponse = {error};
            this.resetAlternatives();

            this.snackbarMessage = {
                message: errorTranslatingTextMessage,
                type: 'error',
            };
        }));
    }), 500);

    // Method to get dummy segments for the demo
    @action getDummySegments(text) {
        // For demo purposes, we'll use pre-defined segments
        // In a real implementation, this would call an AI segmentation API
        return DUMMY_SEGMENTS.map(segment => ({
            ...segment,
            alternatives: []
        }));
    }

    @action handleSegmentClick = (segment) => {
        this.selectedSegment = segment;
        this.fetchAlternatives(segment);
    };

    @action fetchAlternatives = (segment) => {
        // In a real implementation, this would call an API
        // For demo purposes, we'll use pre-defined alternatives
        this.alternativesLoading = true;

        // Simulate API delay
        setTimeout(action(() => {
            this.alternativesLoading = false;

            // Find alternatives for this segment from our dummy data
            const segmentAlternatives = (DUMMY_ALTERNATIVES[segment.id] || [])
                .filter(alternative => alternative !== segment.text);

            segment.alternatives = segmentAlternatives;
            this.alternatives = segmentAlternatives;
        }), 800);
    };

    @action handleAlternativeSelect = (alternativeText) => {
        if (!this.selectedSegment) return;

        const before = this.targetText.substring(0, this.selectedSegment.beginPos);
        const after = this.targetText.substring(this.selectedSegment.endPos);

        // Update the target text with the selected alternative
        this.targetText = before + alternativeText + after;

        // Update segment positions
        const lengthDiff = alternativeText.length - this.selectedSegment.text.length;

        if (lengthDiff !== 0) {
            const currentSegmentIndex = this.segments.indexOf(this.selectedSegment);

            for (let i = currentSegmentIndex + 1; i < this.segments.length; i++) {
                this.segments[i].beginPos += lengthDiff;
                this.segments[i].endPos += lengthDiff;
            }

            // Update the current segment
            this.selectedSegment.text = alternativeText;
            this.selectedSegment.endPos = this.selectedSegment.beginPos + alternativeText.length;
        }

        // Reset selection
        this.selectedSegment = null;
    };

    render() {
        const {
            type,
            sourceLanguages,
            targetLanguages,
            action: Action,
            messages: {
                title: titleMessage,
                insert: insertMessage,
                detected: detectedMessage,
                alternatives: alternativesMessage,
                noAlternatives: noAlternativesMessage,
            },
        } = this.props;

        const actionNode = Action ? (
            <Action
                {...(this.props.actionProps || {})}
                context={toJS(this.lastResponse)}
                source="translator"
            />
        ) : <React.Fragment/>;

        return (
            <Overlay
                confirmDisabled={this.targetText === ''}
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
                            <SingleSelect
                                onChange={this.handleSourceLanguageChanged}
                                skin="flat"
                                value={this.sourceLanguage}
                            >
                                {sourceLanguages.map((option) => {
                                    const isDetected = option.locale.toLowerCase() === this.sourceLanguage
                                        && !this.sourceSelectedOnce;

                                    return (
                                        <SingleSelect.Option key={option.locale} value={option.locale.toLowerCase()}>
                                            {option.label}
                                            {isDetected && ' (' + detectedMessage + ')'}
                                        </SingleSelect.Option>
                                    );
                                })}
                            </SingleSelect>
                        </div>
                        <Input
                            onChange={this.handleSourceTextChanged}
                            text={this.sourceText || ''}
                            type={type}
                        />
                    </div>
                    <div className={translatorStyles.column}>
                        <div className={translatorStyles.select}>
                            <SingleSelect
                                onChange={this.handleTargetLanguageChanged}
                                skin="flat"
                                value={this.targetLanguage}
                            >
                                {targetLanguages.map((option) => (
                                    <SingleSelect.Option key={option.locale} value={option.locale.toLowerCase()}>
                                        {option.label}
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </div>
                        <TranslationAlternatives
                            text={this.targetText}
                            type={type}
                            segments={this.segments}
                            onSegmentClick={this.handleSegmentClick}
                            selectedSegment={this.selectedSegment}
                        />
                    </div>
                </div>

                {/* Alternatives panel */}
                {this.selectedSegment && (
                    <div className={translatorStyles.alternativesPanel}>
                        <h4 className={translatorStyles.alternativesTitle}>{alternativesMessage}</h4>
                        <div className={translatorStyles.content}>
                            {this.alternativesLoading ? (
                                <div className={translatorStyles.loading}>
                                    <span className={translatorStyles.loadingIndicator}></span>
                                    Loading alternatives...
                                </div>
                            ) : this.alternatives.length > 0 ? (
                                <div className={translatorStyles.alternativesList}>
                                    {this.alternatives.map((alternative, index) => (
                                        <button
                                            key={index}
                                            type="button"
                                            className={translatorStyles.alternativeButton}
                                            onClick={() => this.handleAlternativeSelect(alternative)}
                                        >
                                            {alternative}
                                        </button>
                                    ))}
                                </div>
                            ) : (
                                <div className={translatorStyles.noAlternatives}>{noAlternativesMessage}</div>
                            )}
                        </div>
                    </div>
                )}
            </Overlay>
        );
    }
}
