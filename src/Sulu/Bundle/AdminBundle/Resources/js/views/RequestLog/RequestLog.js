// @flow
import React from 'react';
import classNames from 'classnames';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Loader from '../../components/Loader';
import Overlay from '../../components/Overlay';
import Snackbar from '../../components/Snackbar';
import {default as ListContainer, ListStore} from '../../containers/List';
import BadgeFieldTransformer from '../../containers/List/fieldTransformers/BadgeFieldTransformer';
import DateTimeFieldTransformer from '../../containers/List/fieldTransformers/DateTimeFieldTransformer';
import DurationFieldTransformer from '../../containers/List/fieldTransformers/DurationFieldTransformer';
import {TextEditor} from '../../containers';
import {withToolbar} from '../../containers/Toolbar';
import ResourceRequester from '../../services/ResourceRequester';
import {translate} from '../../utils/Translator';
import requestLogStyles from './requestLog.scss';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {ElementRef, Node} from 'react';

const RESOURCE_KEY = 'request_log';
const EMPTY_VALUE_PLACEHOLDER = '-';
const COLLAPSE_LINE_THRESHOLD = 8;
const COLLAPSE_LENGTH_THRESHOLD = 600;

type ChainStep = {
    annotations: Array<string>,
    content: ?string,
    contentType: 'text' | 'json' | 'html',
    segmentKey: ?string,
    title: string,
    type: string,
};

type ChainCard = {
    step: ChainStep,
    writeback: ?string,
};

type TranslationSegment = {
    key: string,
    original: ChainStep,
    title: ?string,
    translation: ?ChainStep,
};

type RequestLogDetail = {
    chain: Array<ChainStep>,
    credits: ?number,
    durationMs: ?number,
    endpoint: string,
    errorCode: ?string,
    errorMessage: ?string,
    expertKey: ?string,
    expertName: ?string,
    feature: string,
    id: number,
    locale: ?string,
    model: ?string,
    provider: ?string,
    requestType: ?string,
    startedAt: string,
    status: string,
    streamed: boolean,
    trigger: ?string,
    uuid: ?string,
    webspaceKey: ?string,
};

const CHAIN_STEP_COLOR_MODIFIER = {
    assistant: 'success',
    context: 'user',
    expert: 'expert',
    prompt: 'user',
    response: 'success',
    tool: 'tool',
    user: 'user',
};

function chainStepColorModifier(type: string): string {
    return CHAIN_STEP_COLOR_MODIFIER[type] || 'system';
}

// Only a message that is JSON as a whole is indented. Everything else is markdown and stays as it is.
function formatContent(content: string): string {
    const trimmed = content.trim();

    if (trimmed[0] !== '{' && trimmed[0] !== '[') {
        return content;
    }

    try {
        return JSON.stringify(JSON.parse(trimmed), null, 2);
    } catch (e) {
        return content;
    }
}

function isContentLong(content: ?string): boolean {
    if (content === null || content === undefined) {
        return false;
    }

    const formattedContent = formatContent(content);

    return formattedContent.length > COLLAPSE_LENGTH_THRESHOLD
        || formattedContent.split('\n').length > COLLAPSE_LINE_THRESHOLD;
}

function stripHtml(content: string): string {
    return content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
}

// A full-content translation records one user/response pair per segmentKey (one per translated
// field); a single translate() call has none, and falls back to the one legacy pair in
// renderDetailContent. A segment still missing its response (a failed or in-flight translation)
// renders with an empty result column rather than being dropped, since that is the field a
// reader of the log is most likely looking for.
function groupTranslationSegments(chain: Array<ChainStep>): Array<TranslationSegment> {
    const originals: {[string]: ChainStep} = {};
    const translations: {[string]: ChainStep} = {};
    const order = [];

    chain.forEach((step) => {
        const {segmentKey} = step;

        if (segmentKey === null || segmentKey === undefined) {
            return;
        }

        if (!(segmentKey in originals) && !(segmentKey in translations)) {
            order.push(segmentKey);
        }

        if (step.type === 'user') {
            originals[segmentKey] = step;
        } else if (step.type === 'response') {
            translations[segmentKey] = step;
        }
    });

    return order
        .filter((key) => !!originals[key])
        .map((key) => ({key, title: key, original: originals[key], translation: translations[key] || null}));
}

function groupChainSteps(chain: Array<ChainStep>): Array<ChainCard> {
    const cards: Array<ChainCard> = [];

    chain.forEach((step) => {
        const previousCard = cards[cards.length - 1];

        if (step.type === 'writeback' && previousCard && previousCard.step.type === 'response') {
            previousCard.writeback = step.title;

            return;
        }

        cards.push({step, writeback: null});
    });

    return cards;
}

function formatValue(value: mixed): string {
    if (value === null || value === undefined || value === '') {
        return EMPTY_VALUE_PLACEHOLDER;
    }

    return String(value);
}

const durationFieldTransformer = new DurationFieldTransformer();

function formatDuration(durationMs: ?number): string {
    const transformed = durationFieldTransformer.transform(durationMs);

    return transformed === null || transformed === undefined ? EMPTY_VALUE_PLACEHOLDER : String(transformed);
}

const dateTimeFieldTransformer = new DateTimeFieldTransformer();

function formatDateTime(startedAt: string): Node {
    return dateTimeFieldTransformer.transform(startedAt, {format: 'default_with_seconds'}) ?? EMPTY_VALUE_PLACEHOLDER;
}

/**
 * @internal
 */
@observer
class RequestLog extends React.Component<ViewProps> {
    translationPrefix: string;
    listStore: ListStore;
    list: ?ElementRef<typeof ListContainer>;

    @observable selectedId: ?(string | number) = null;
    @observable detail: ?RequestLogDetail;
    @observable detailLoading: boolean = false;
    @observable detailError: boolean = false;
    @observable expandedChainSteps: {[number]: boolean} = {};
    @observable expandedTranslationSegments: {[string]: boolean} = {};

    constructor(props: ViewProps) {
        super(props);

        const {translationPrefix} = props.router.route.options;
        this.translationPrefix = translationPrefix;

        this.listStore = new ListStore(
            RESOURCE_KEY,
            RESOURCE_KEY,
            RESOURCE_KEY,
            {page: observable.box(1)},
            {},
            {}
        );
    }

    translateKey(key: string): string {
        return translate(this.translationPrefix + key);
    }

    componentWillUnmount() {
        this.listStore.destroy();
    }

    setListRef = (list: ?ElementRef<typeof ListContainer>) => {
        this.list = list;
    };

    handleRefreshClick = () => {
        this.listStore.reload();
    };

    handleDeleteClick = () => {
        if (this.list) {
            this.list.requestSelectionDelete();
        }
    };

    @action handleItemClick = (itemId: string | number) => {
        this.selectedId = itemId;
        this.expandedChainSteps = {};
        this.expandedTranslationSegments = {};
        this.loadDetail(itemId);
    };

    @action handleOverlayClose = () => {
        this.selectedId = null;
        this.detail = undefined;
        this.detailError = false;
    };

    @action handleToggleExpand = (event: SyntheticEvent<HTMLButtonElement>) => {
        const index = Number(event.currentTarget.dataset.index);

        this.expandedChainSteps = {
            ...this.expandedChainSteps,
            [index]: !this.expandedChainSteps[index],
        };
    };

    @action handleToggleTranslationExpand = (event: SyntheticEvent<HTMLButtonElement>) => {
        const key = event.currentTarget.dataset.segmentKey;

        this.expandedTranslationSegments = {
            ...this.expandedTranslationSegments,
            [key]: !this.expandedTranslationSegments[key],
        };
    };

    handleReadOnlyEditorChange = () => {
        // the translation columns are always read-only, this exists only to satisfy TextEditor's props
    };

    @action loadDetail(itemId: string | number) {
        this.detailLoading = true;
        this.detailError = false;

        ResourceRequester.get(RESOURCE_KEY, {id: itemId})
            .then(action((response) => {
                this.detail = response;
                this.detailLoading = false;
            }))
            .catch(action(() => {
                this.detailError = true;
                this.detailLoading = false;
            }));
    }

    renderStatusBadge(status: string) {
        return new BadgeFieldTransformer().transform(status, {
            error: 'failed',
            prefix: this.translationPrefix + 'status.',
            success: 'succeeded',
        });
    }

    renderFact(labelKey: string, value: string) {
        return (
            <div className={requestLogStyles.fact} key={labelKey}>
                <dt className={requestLogStyles.factLabel}>{this.translateKey(labelKey)}</dt>
                <dd className={requestLogStyles.factValue}>{value}</dd>
            </div>
        );
    }

    formatRequestType(detail: RequestLogDetail): string {
        if (!detail.requestType) {
            return formatValue(detail.endpoint);
        }

        return this.translateKey('request_type.' + detail.requestType);
    }

    renderChatFacts(detail: RequestLogDetail) {
        return (
            <React.Fragment>
                <dl className={requestLogStyles.facts}>
                    {this.renderFact('request_type', this.formatRequestType(detail))}
                    {this.renderFact('expert_title', formatValue(detail.expertName))}
                    {this.renderFact('expert_key_label', formatValue(detail.expertKey))}
                    {this.renderFact('model', formatValue(detail.model))}
                    {this.renderFact('locale', formatValue(detail.locale))}
                </dl>
                <dl className={requestLogStyles.facts}>
                    {this.renderFact('webspace_key', formatValue(detail.webspaceKey))}
                    {this.renderFact('duration', formatDuration(detail.durationMs))}
                    {this.renderFact(
                        'streamed',
                        detail.streamed ? translate('sulu_admin.yes') : translate('sulu_admin.no')
                    )}
                    {this.renderFact('credits', formatValue(detail.credits))}
                    {detail.trigger &&
                        this.renderFact('trigger', formatValue(detail.trigger))
                    }
                </dl>
            </React.Fragment>
        );
    }

    renderTranslationFacts(detail: RequestLogDetail) {
        return (
            <dl className={requestLogStyles.facts}>
                {this.renderFact('target_language', formatValue(detail.locale))}
                {this.renderFact('provider', formatValue(detail.provider))}
                {this.renderFact('webspace_key', formatValue(detail.webspaceKey))}
                {this.renderFact('duration', formatDuration(detail.durationMs))}
                {this.renderFact('credits', formatValue(detail.credits))}
            </dl>
        );
    }

    renderFactsCard(detail: RequestLogDetail) {
        return (
            <div className={requestLogStyles.factsCard}>
                <div className={requestLogStyles.identity}>
                    <div className={requestLogStyles.identityName}>
                        <span className={requestLogStyles.featureName}>
                            {this.translateKey('feature.' + detail.feature)}
                        </span>
                        {this.renderStatusBadge(detail.status)}
                    </div>
                    <span className={requestLogStyles.timestamp}>{formatDateTime(detail.startedAt)}</span>
                </div>
                <div className={requestLogStyles.requestId}>
                    <span className={requestLogStyles.requestIdLabel}>
                        {this.translateKey('request_id')}
                    </span>
                    <span className={requestLogStyles.requestIdValue}>{formatValue(detail.uuid)}</span>
                </div>
                <div className={requestLogStyles.divider} />
                {detail.requestType === 'translation'
                    ? this.renderTranslationFacts(detail)
                    : this.renderChatFacts(detail)
                }
            </div>
        );
    }

    renderContentBox(content: string, expanded: boolean, contentType: 'text' | 'json' | 'html' = 'text') {
        return (
            <div className={classNames(requestLogStyles.chainStepBody, {[requestLogStyles.collapsed]: !expanded})}>
                {contentType === 'html'
                    ? this.renderHtmlContentBox(content, expanded)
                    : <div className={requestLogStyles.contentBox}>{formatContent(content)}</div>
                }
            </div>
        );
    }

    // A collapsed row never mounts the editor: with dozens of translated fields on one page, that
    // would mean dozens of live CKEditor instances, most of them clipped to 180px by .collapsed and
    // never seen.
    renderHtmlContentBox(content: string, expanded: boolean) {
        if (!expanded) {
            return <div className={requestLogStyles.contentBox}>{stripHtml(content)}</div>;
        }

        return (
            <div className={requestLogStyles.contentBox}>
                <TextEditor
                    adapter="ckeditor5"
                    disabled={true}
                    locale={observable.box((this.detail && this.detail.locale) || 'en')}
                    onChange={this.handleReadOnlyEditorChange}
                    value={content}
                />
            </div>
        );
    }

    renderChainStepContent(step: ChainStep, index: number) {
        const {content} = step;

        if (content === null || content === undefined) {
            return null;
        }

        const isLong = isContentLong(content);
        const expanded = !isLong || !!this.expandedChainSteps[index];
        const showFullLabelKey = expanded
            ? 'show_less'
            : 'show_full';

        return (
            <React.Fragment>
                {this.renderContentBox(content, expanded)}
                {isLong &&
                    <button
                        className={requestLogStyles.showFull}
                        data-index={index}
                        onClick={this.handleToggleExpand}
                        type="button"
                    >
                        {this.translateKey(showFullLabelKey)}
                    </button>
                }
            </React.Fragment>
        );
    }

    renderChainCard(card: ChainCard, index: number) {
        const {step, writeback} = card;
        const cardClass = classNames(
            requestLogStyles.chainStep,
            requestLogStyles[chainStepColorModifier(step.type)]
        );

        return (
            <li className={cardClass} key={index}>
                <div className={requestLogStyles.chainStepHead}>
                    <div className={requestLogStyles.chainStepNumber}>{index + 1}</div>
                    <span className={requestLogStyles.chainStepTitle}>{step.title}</span>
                    {step.annotations.length > 0 &&
                        <span className={requestLogStyles.chainStepAnnotations}>
                            {step.annotations.join(' · ')}
                        </span>
                    }
                </div>
                {writeback &&
                    <p className={requestLogStyles.writeback}>{writeback}</p>
                }
                {this.renderChainStepContent(step, index)}
            </li>
        );
    }

    renderTranslationColumn(step: ?ChainStep, titleKey: string, expanded: boolean) {
        const cardClass = classNames(
            requestLogStyles.chainStep,
            step && requestLogStyles[chainStepColorModifier(step.type)]
        );

        return (
            <div className={cardClass}>
                <div className={requestLogStyles.chainStepHead}>
                    <span className={requestLogStyles.chainStepTitle}>{this.translateKey(titleKey)}</span>
                </div>
                {step && step.content !== null && step.content !== undefined &&
                    this.renderContentBox(step.content, expanded, step.contentType)
                }
            </div>
        );
    }

    renderTranslationSegment(segment: TranslationSegment) {
        const {key, original, title, translation} = segment;
        const isLong = isContentLong(original.content) || (!!translation && isContentLong(translation.content));
        const expanded = !isLong || !!this.expandedTranslationSegments[key];
        const showFullLabelKey = expanded
            ? 'show_less'
            : 'show_full';

        return (
            <div className={requestLogStyles.translationSegment} key={key}>
                {title !== null &&
                    <p className={requestLogStyles.translationSegmentTitle}>{title}</p>
                }
                <div className={requestLogStyles.translationColumns}>
                    {this.renderTranslationColumn(original, 'translation_original', expanded)}
                    {this.renderTranslationColumn(translation, 'translation_result', expanded)}
                </div>
                {isLong &&
                    <button
                        className={classNames(requestLogStyles.showFull, requestLogStyles.centered)}
                        data-segment-key={key}
                        onClick={this.handleToggleTranslationExpand}
                        type="button"
                    >
                        {this.translateKey(showFullLabelKey)}
                    </button>
                }
            </div>
        );
    }

    renderTranslationSection(detail: RequestLogDetail, segments: Array<TranslationSegment>) {
        return (
            <div className={requestLogStyles.chainSection}>
                <div className={requestLogStyles.chainHeader}>
                    <p className={requestLogStyles.chainTitle}>
                        {this.translateKey('translation')}
                    </p>
                    <p className={requestLogStyles.chainDescription}>
                        {formatDateTime(detail.startedAt)}
                        {' · '}
                        {this.translateKey('translation_description')}
                    </p>
                </div>
                {segments.map((segment) => this.renderTranslationSegment(segment))}
            </div>
        );
    }

    renderDetailContent(detail: RequestLogDetail) {
        const chainCards = groupChainSteps(detail.chain);
        const segments = groupTranslationSegments(detail.chain);
        const original = detail.chain.find((step) => step.type === 'user' && !step.segmentKey);
        const translation = detail.chain.find(
            (step) => step.type === 'response' && !!step.content && !step.segmentKey
        );
        const legacySegment = original && translation
            ? [{key: 'default', title: null, original, translation}]
            : [];
        const translationSegments = segments.length > 0 ? segments : legacySegment;
        const isTranslation = detail.requestType === 'translation' && translationSegments.length > 0;

        return (
            <React.Fragment>
                {this.renderFactsCard(detail)}
                {detail.status === 'failed' &&
                    <Snackbar
                        message={detail.errorMessage || ''}
                        title={detail.errorCode || undefined}
                        type="error"
                    />
                }
                {isTranslation && this.renderTranslationSection(detail, translationSegments)}
                {!isTranslation && <div className={requestLogStyles.chainSection}>
                    <div className={requestLogStyles.chainHeader}>
                        <p className={requestLogStyles.chainTitle}>
                            {this.translateKey('resolved_chain')}
                        </p>
                        <p className={requestLogStyles.chainDescription}>
                            {formatDateTime(detail.startedAt)}
                            {' · '}
                            {this.translateKey('resolved_chain_description')}
                        </p>
                    </div>
                    <ol className={requestLogStyles.chain}>
                        {chainCards.map((card, index) => this.renderChainCard(card, index))}
                    </ol>
                </div>}
            </React.Fragment>
        );
    }

    renderDetailOverlay() {
        const {detail, detailError, detailLoading, selectedId} = this;

        const title = detail
            ? this.translateKey('feature.' + detail.feature)
            : this.translateKey('detail_title');

        return (
            <Overlay
                confirmText={translate('sulu_admin.close')}
                onClose={this.handleOverlayClose}
                onConfirm={this.handleOverlayClose}
                open={selectedId !== null}
                size="large"
                title={title}
            >
                <div className={requestLogStyles.detail}>
                    {detailLoading && <Loader />}
                    {!detailLoading && detailError &&
                        <p className={requestLogStyles.detailError}>
                            {this.translateKey('detail_error')}
                        </p>
                    }
                    {!detailLoading && !detailError && detail && this.renderDetailContent(detail)}
                </div>
            </Overlay>
        );
    }

    render() {
        return (
            <div className={requestLogStyles.requestLog}>
                <h1 className={requestLogStyles.title}>{this.translateKey('title')}</h1>
                <ListContainer
                    adapters={['table']}
                    filterable={true}
                    onItemClick={this.handleItemClick}
                    paginated={true}
                    ref={this.setListRef}
                    searchable={true}
                    selectable={true}
                    showColumnOptions={true}
                    store={this.listStore}
                />
                {this.renderDetailOverlay()}
            </div>
        );
    }
}

export default withToolbar(RequestLog, function() {
    return {
        items: [
            {
                icon: 'su-sync',
                label: this.translateKey('refresh'),
                onClick: this.handleRefreshClick,
                type: 'button',
            },
            {
                disabled: this.listStore.selectionIds.length === 0,
                icon: 'su-trash-alt',
                label: translate('sulu_admin.delete'),
                loading: this.listStore.deletingSelection,
                onClick: this.handleDeleteClick,
                type: 'button',
            },
        ],
    };
});
