// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import symfonyRouting from 'fos-jsrouting/router';
import Loader from '../../components/Loader';
import Overlay from '../../components/Overlay';
import {default as ListContainer, ListStore} from '../../containers/List';
import {withToolbar} from '../../containers/Toolbar';
import Requester from '../../services/Requester';
import {translate} from '../../utils/Translator';
import requestLogStyles from './requestLog.scss';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {ElementRef} from 'react';

const RESOURCE_KEY = 'request_log';

type ChainStep = {
    annotations: Array<string>,
    content: ?string,
    contentType: 'text' | 'json',
    title: string,
    type: string,
};

type RequestLogDetail = {
    chain: Array<ChainStep>,
    credits: ?number,
    durationMs: ?number,
    errorCode: ?string,
    errorMessage: ?string,
    feature: string,
    id: number,
    locale: ?string,
    model: ?string,
    status: string,
};

function formatDuration(durationMs: ?number): ?string {
    if (durationMs === null || durationMs === undefined) {
        return undefined;
    }

    return (durationMs / 1000).toFixed(1) + 's';
}

@observer
class RequestLog extends React.Component<ViewProps> {
    listStore: ListStore;
    list: ?ElementRef<typeof ListContainer>;

    @observable selectedId: ?(string | number) = null;
    @observable detail: ?RequestLogDetail;
    @observable detailLoading: boolean = false;
    @observable detailError: boolean = false;

    constructor(props: ViewProps) {
        super(props);

        this.listStore = new ListStore(
            RESOURCE_KEY,
            RESOURCE_KEY,
            RESOURCE_KEY,
            {page: observable.box(1)},
            {},
            {}
        );
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
        this.loadDetail(itemId);
    };

    @action handleOverlayClose = () => {
        this.selectedId = null;
        this.detail = undefined;
        this.detailError = false;
    };

    @action loadDetail(itemId: string | number) {
        this.detailLoading = true;
        this.detailError = false;

        Requester.get(symfonyRouting.generate('sulu_ai_platform.request_log_detail', {id: itemId}))
            .then(action((response) => {
                this.detail = response;
                this.detailLoading = false;
            }))
            .catch(action(() => {
                this.detailError = true;
                this.detailLoading = false;
            }));
    }

    renderMetaItem(labelKey: string, value: mixed) {
        if (value === undefined || value === null || value === '') {
            return null;
        }

        return (
            <div className={requestLogStyles.metaItem} key={labelKey}>
                <dt className={requestLogStyles.metaLabel}>{translate(labelKey)}</dt>
                <dd className={requestLogStyles.metaValue}>{String(value)}</dd>
            </div>
        );
    }

    renderChainStep(step: ChainStep, index: number) {
        return (
            <li className={requestLogStyles.chainStep} key={index}>
                <div className={requestLogStyles.chainStepNumber}>{index + 1}</div>
                <div className={requestLogStyles.chainStepBody}>
                    <div className={requestLogStyles.chainStepTitle}>{step.title}</div>
                    {step.annotations.length > 0 &&
                        <div className={requestLogStyles.chainStepAnnotations}>
                            {step.annotations.join(' · ')}
                        </div>
                    }
                    {step.content !== null && step.content !== undefined &&
                        <pre className={requestLogStyles.chainStepContent}>{step.content}</pre>
                    }
                </div>
            </li>
        );
    }

    renderDetailContent(detail: RequestLogDetail) {
        return (
            <div>
                <dl className={requestLogStyles.metaGrid}>
                    {this.renderMetaItem('sulu_ai_platform.request_log.feature', detail.feature)}
                    {this.renderMetaItem('sulu_ai_platform.request_log.model', detail.model)}
                    {this.renderMetaItem('sulu_ai_platform.request_log.locale', detail.locale)}
                    {this.renderMetaItem('sulu_ai_platform.request_log.duration', formatDuration(detail.durationMs))}
                    {this.renderMetaItem('sulu_ai_platform.request_log.credits', detail.credits)}
                    {this.renderMetaItem(
                        'sulu_ai_platform.request_log.status',
                        translate('sulu_ai_platform.request_log.status.' + detail.status)
                    )}
                </dl>
                {detail.status === 'failed' &&
                    <div className={requestLogStyles.errorBanner}>
                        {detail.errorCode && <strong>{detail.errorCode}</strong>}
                        {detail.errorMessage && <p>{detail.errorMessage}</p>}
                    </div>
                }
                <ol className={requestLogStyles.chain}>
                    {detail.chain.map((step, index) => this.renderChainStep(step, index))}
                </ol>
            </div>
        );
    }

    renderDetailOverlay() {
        const {detail, detailError, detailLoading, selectedId} = this;

        return (
            <Overlay
                onClose={this.handleOverlayClose}
                open={selectedId !== null}
                size="large"
                title={translate('sulu_ai_platform.request_log.detail_title')}
            >
                <div className={requestLogStyles.detail}>
                    {detailLoading && <Loader />}
                    {!detailLoading && detailError &&
                        <p className={requestLogStyles.detailError}>
                            {translate('sulu_ai_platform.request_log.detail_error')}
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
                label: translate('sulu_ai_platform.request_log.refresh'),
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
