// @flow
import React from 'react';
import {action, computed, observable, reaction, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import classNames from 'classnames';
import log from 'loglevel';
import {DatePicker, Form, Loader, Toolbar} from 'sulu-admin-bundle/components';
import {ResourceFormStore, sidebarStore} from 'sulu-admin-bundle/containers';
import {Router} from 'sulu-admin-bundle/services';
import {ResourceListStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import {webspaceStore} from 'sulu-page-bundle/stores';
import previewStyles from './preview.scss';
import './public-preview.scss';
import PreviewStore from './stores/PreviewStore';
import PreviewLinkPopover from './PreviewLinkPopover';
import type {PreviewMode} from './types';

type Props = {|
    formStore: ResourceFormStore,
    router: Router,
|};

const NAVIGATE_MESSAGE_TYPE = 'sulu.preview.navigate';
const READY_MESSAGE_TYPE = 'sulu.preview.ready';

function collectBlockIds(data: mixed, ids: Set<string> = new Set()): Set<string> {
    if (Array.isArray(data)) {
        data.forEach((item) => collectBlockIds(item, ids));
    } else if (data && typeof data === 'object') {
        // $FlowFixMe
        const {_id} = data;
        if (typeof _id === 'string') {
            ids.add(_id);
        }

        Object.keys(data).forEach((key) => collectBlockIds(data[key], ids));
    }

    return ids;
}

// A collapsed block only renders its lightweight collapsed preview, not its nested fields (see
// FieldBlocks.js's renderBlockContent), so a block nested inside a collapsed ancestor does not
// exist in the DOM yet and can't be found by simply querying for it. This walks the raw form data
// (not the DOM) to find the chain of block ids - from outermost ancestor to the target itself -
// that needs to be expanded, in order, before the target block will actually be mounted.
function findBlockIdPath(data: mixed, targetId: string, path: Array<string> = []): ?Array<string> {
    if (Array.isArray(data)) {
        for (const item of data) {
            const result = findBlockIdPath(item, targetId, path);
            if (result) {
                return result;
            }
        }

        return undefined;
    }

    if (data && typeof data === 'object') {
        // $FlowFixMe
        const {_id} = data;
        const nextPath = typeof _id === 'string' ? path.concat(_id) : path;

        if (_id === targetId) {
            return nextPath;
        }

        for (const key of Object.keys(data)) {
            const result = findBlockIdPath(data[key], targetId, nextPath);
            if (result) {
                return result;
            }
        }
    }

    return undefined;
}

function findBlockElement(id: string): ?HTMLElement {
    const element = Array.from(document.querySelectorAll('[data-sulu-block-id]'))
        .find((candidate) => candidate.getAttribute('data-sulu-block-id') === id);

    return element instanceof HTMLElement ? element : undefined;
}

@observer
class Preview extends React.Component<Props> {
    static debounceDelay: number = 250;
    static mode: PreviewMode = 'auto';
    static audienceTargeting: boolean = false;

    availableDeviceOptions = [
        {label: translate('sulu_preview.auto'), value: 'auto'},
        {label: translate('sulu_preview.desktop'), value: 'desktop'},
        {label: translate('sulu_preview.tablet'), value: 'tablet'},
        {label: translate('sulu_preview.smartphone'), value: 'smartphone'},
    ];

    @observable iframeRef: ?HTMLIFrameElement;
    @observable started: boolean = false;
    @observable selectedDeviceOption = this.availableDeviceOptions[0].value;
    @observable targetGroupsStore: ?ResourceListStore;

    @observable previewStore: PreviewStore;
    @observable previewWindow: any;
    @observable reloadCounter: number = 0;

    // Bumped on every updatePreview call so an in-flight response can tell it has been superseded
    // by a newer request (e.g. the user reordered/edited blocks again before this one resolved) and
    // must not merge its now-stale backfilled ids into the live form data.
    updateRequestId: number = 0;

    schemaDisposer: () => mixed;
    dataDisposer: () => mixed;
    localeDisposer: () => mixed;

    unmounted: boolean = false;
    renderedData: ?Object;

    @computed get webspaceKey() {
        const {
            formStore: {
                data,
            },
            router: {
                attributes: {
                    webspace,
                },
            },
        } = this.props;

        if (webspace !== undefined && typeof webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string if set!');
        }

        // Priority: router attribute > article mainWebspace > first available webspace
        const mainWebspace = data?.mainWebspace;
        if (webspace) {
            return webspace;
        }

        // Use mainWebspace if it's valid and supports the current locale
        if (mainWebspace && this.webspaceOptions.some((option) => option.value === mainWebspace)) {
            return mainWebspace;
        }

        return this.webspaceOptions[0]?.value;
    }

    @computed get webspaceOptions(): Array<Object> {
        const {formStore: {locale}} = this.props;
        const currentLocale = locale?.get();

        const supportsLocale = (localizations) => localizations.some(
            (localization) => localization.locale === currentLocale
                || (localization.children && supportsLocale(localization.children))
        );

        return webspaceStore.grantedWebspaces
            .filter((webspace) => {
                // Filter webspaces that support the current locale
                if (!currentLocale || !webspace.localizations) {
                    return true;
                }
                // Localizations are a nested tree (e.g. "de" -> "de_ch"), so the
                // check needs to recurse into the child localizations.
                return supportsLocale(webspace.localizations);
            })
            .map((webspace): Object => ({
                label: webspace.name,
                value: webspace.key,
            }));
    }

    @computed get segments() {
        if (!this.webspaceKey) {
            return [];
        }

        return webspaceStore.getWebspace(this.webspaceKey).segments;
    }

    @computed get shouldUpdateFormStore() {
        return this.props.formStore.resourceKey === this.previewStore.resourceKey;
    }

    constructor(props: Props) {
        super(props);

        if (Preview.audienceTargeting) {
            this.targetGroupsStore = new ResourceListStore('target_groups');
        }

        this.createPreviewStore();
        if (Preview.mode === 'auto') {
            this.startPreview();
        }
    }

    componentDidMount() {
        window.addEventListener('message', this.handleMessage);
    }

    componentDidUpdate(prevProps: Props) {
        const {
            formStore,
        } = this.props;

        if (this.props.formStore !== prevProps.formStore) {
            this.disposeFormStoreReactions();
            this.updatePreview(toJS(formStore.data));

            this.initializeFormStoreReactions();
        }
    }

    @action createPreviewStore = () => {
        const {
            formStore: {
                resourceKey,
                id,
                locale,
            },
            router: {
                route: {
                    options: {
                        previewResourceKey = null,
                    },
                },
            },
        } = this.props;

        this.previewStore = new PreviewStore(
            previewResourceKey || resourceKey,
            id,
            locale,
            this.webspaceKey,
            this.segments.find((segment) => segment.default === true)?.key
        );
    };

    @action setStarted = (started: boolean) => {
        this.started = started;
    };

    startPreview = () => {
        const {previewStore} = this;

        const {
            formStore,
        } = this.props;

        previewStore.start();

        when(
            () => !formStore.loading
                && !previewStore.starting
                && this.iframeRef !== null
                && (!this.targetGroupsStore || !this.targetGroupsStore.loading),
            this.initializeFormStoreReactions
        );

        this.setStarted(true);
    };

    initializeFormStoreReactions = (): void => {
        const {previewStore} = this;

        const {
            formStore,
        } = this.props;

        this.localeDisposer = reaction(
            () => toJS(formStore.locale),
            (locale) => {
                // Update webspace to one that supports the new locale
                this.previewStore.setWebspace(this.webspaceKey);
                this.previewStore.restart(locale);
            }
        );

        if (previewStore.resourceKey !== formStore.resourceKey) {
            return;
        }

        this.dataDisposer = reaction(
            () => toJS(formStore.data),
            (data) => {
                if (this.iframeRef === null && !this.previewWindow) {
                    return;
                }

                this.updatePreview(data);
            }
        );

        this.schemaDisposer = reaction(
            () => toJS(formStore.schema),
            () => {
                if (formStore.type) {
                    const data = toJS(formStore.data);
                    previewStore.updateContext(toJS(formStore.type), data)
                        .then((previewContent) => this.setContent(previewContent, data));
                }
            }
        );
    };

    updatePreview = debounce((data: Object) => {
        if (this.shouldUpdateFormStore && !!this.previewStore.token) {
            const {previewStore} = this;
            const requestId = ++this.updateRequestId;
            previewStore.update(data).then((result) => {
                if (!this.unmounted && requestId === this.updateRequestId) {
                    this.mergeMissingBlockIds(data, result.data, []);
                }

                this.setContent(result.content, data);
            });
        }
    }, Preview.debounceDelay);

    // The provider backfills missing block "_id"s server-side while rendering the preview (e.g. for
    // a block nested inside a collapsed ancestor whose BlockCollection never mounted client-side, so
    // never generated one) - but only the rendered HTML normally makes it back to the admin, leaving
    // the form's own copy of that block still without an id. Without this, clicking that block in the
    // preview has no matching id to navigate to in the admin form. This walks the data that was sent
    // alongside what the provider echoes back and copies over any id the form is still missing.
    mergeMissingBlockIds = (sentData: mixed, enrichedData: mixed, path: Array<string>) => {
        if (!enrichedData || typeof enrichedData !== 'object') {
            return;
        }

        if (Array.isArray(sentData) && Array.isArray(enrichedData)) {
            const length = Math.min(sentData.length, enrichedData.length);
            for (let i = 0; i < length; i++) {
                this.mergeMissingBlockIds(sentData[i], enrichedData[i], path.concat(String(i)));
            }

            return;
        }

        if (!sentData || typeof sentData !== 'object' || Array.isArray(sentData) || Array.isArray(enrichedData)) {
            return;
        }

        const {formStore} = this.props;

        // $FlowFixMe
        const sentId = sentData._id;
        // $FlowFixMe
        const enrichedId = enrichedData._id;

        if (typeof sentId !== 'string' && typeof enrichedId === 'string') {
            const idPath = path.concat('_id');

            // Only fill the id in if the live form data still lacks one at this exact position -
            // blindly overwriting could otherwise clobber a newer id. The caller additionally checks
            // the request hasn't been superseded before calling this at all, so a reorder/edit that
            // happened after this request was sent causes the whole merge to be skipped rather than
            // attaching this response's id to a different block that has since taken this position.
            if (formStore.getValueByPath('/' + idPath.join('/')) === undefined) {
                formStore.change(idPath.join('/'), enrichedId);
            }
        }

        Object.keys(sentData).forEach((key) => {
            // $FlowFixMe
            this.mergeMissingBlockIds(sentData[key], enrichedData[key], path.concat(key));
        });
    };

    setContent = (previewContent: string, data: Object) => {
        const previewDocument = this.getPreviewDocument();
        if (!previewDocument) {
            return;
        }

        // Tracked per-render (not read live from formStore.data) so warnAboutMissingDeepLinkAttributes
        // compares the "ready" ids against the data that actually produced this content, rather than
        // whatever the user has already typed since - which could otherwise flag ids that are simply
        // not rendered yet as "missing" from the template.
        this.renderedData = data;

        const preservedScrollPosition = this.getPreviewScrollPosition();
        previewDocument.open(); // This will lose in Firefox the and safari previewDocument.location
        previewDocument.write(previewContent);
        previewDocument.close();

        if (preservedScrollPosition) {
            setTimeout(() => this.setPreviewScrollPosition(preservedScrollPosition), 0);
        }
    };

    componentWillUnmount() {
        this.unmounted = true;
        window.removeEventListener('message', this.handleMessage);

        this.disposeFormStoreReactions();

        if (!this.started) {
            return;
        }

        this.updatePreview.clear();
        this.previewStore.stop();
    }

    handleMessage = (event: MessageEvent) => {
        if (event.source !== this.getPreviewWindow()) {
            return;
        }

        // event.source is the preview window's WindowProxy, which stays valid even if that
        // window navigates to a different origin (e.g. via a link inside the previewed page) -
        // checking it alone would keep trusting messages from whatever page ends up loaded
        // there. The preview iframe/window is always same-origin with the admin today, so also
        // require that.
        if (event.origin !== window.location.origin) {
            return;
        }

        const {data} = event;
        if (!data || typeof data !== 'object') {
            return;
        }

        if (data.type === NAVIGATE_MESSAGE_TYPE && typeof data.id === 'string') {
            this.navigateToBlock(data.id);
        }

        if (data.type === READY_MESSAGE_TYPE && Array.isArray(data.ids)) {
            this.warnAboutMissingDeepLinkAttributes(data.ids);
        }
    };

    navigateToBlock = (id: string) => {
        const {formStore} = this.props;
        const idPath = findBlockIdPath(toJS(formStore.data), id);
        if (!idPath) {
            return;
        }

        const maxMountAttempts = 30; // ~0.5s at 60fps, generous for a React re-render to commit

        // idPath includes the target block itself as its last entry (not just its ancestors), so
        // every entry needs to be expanded via a real click - including the target, since it may
        // itself be collapsed - before it can be scrolled into view.
        const expandNext = (index: number, mountAttempt: number = 0) => {
            // The component may have unmounted (e.g. the sidebar was switched away from Preview)
            // while a requestAnimationFrame callback below was still pending - rAF callbacks are
            // not tied to React's lifecycle and would otherwise keep clicking/scrolling the still-
            // mounted admin form after the user already left Preview.
            if (this.unmounted) {
                return;
            }

            if (index >= idPath.length) {
                const target = findBlockElement(idPath[idPath.length - 1]);
                if (target) {
                    target.scrollIntoView({behavior: 'smooth', block: 'start'});
                }

                return;
            }

            const element = findBlockElement(idPath[index]);
            if (!element) {
                if (mountAttempt >= maxMountAttempts) {
                    // A parent's nested content never mounted (stale/removed block) - give up
                    // instead of polling forever.
                    return;
                }

                requestAnimationFrame(() => expandNext(index, mountAttempt + 1));
                return;
            }

            element.click();
            requestAnimationFrame(() => expandNext(index + 1));
        };

        expandNext(0);
    };

    warnAboutMissingDeepLinkAttributes = (renderedIds: $ReadOnlyArray<mixed>) => {
        const {formStore} = this.props;
        const expectedIds = collectBlockIds(this.renderedData || toJS(formStore.data));
        const missingIds = Array.from(expectedIds).filter((id) => !renderedIds.includes(id));

        if (missingIds.length > 0) {
            log.warn(
                '[sulu_preview] The following blocks are missing the "sulu_preview_deep_link()" attribute ' +
                'on their template root element and cannot be navigated to from the preview: '
                + missingIds.join(', ')
            );
        }
    };

    disposeFormStoreReactions() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }

        if (this.dataDisposer) {
            this.dataDisposer();
        }

        if (this.localeDisposer) {
            this.localeDisposer();
        }
    }

    getPreviewDocument = (): ?Document => {
        if (this.previewWindow) {
            return this.previewWindow.document;
        }

        if (!(this.iframeRef instanceof HTMLIFrameElement)) {
            return;
        }

        return this.iframeRef.contentDocument;
    };

    getPreviewWindow = (): ?any => {
        if (this.previewWindow) {
            return this.previewWindow;
        }

        if (!(this.iframeRef instanceof HTMLIFrameElement)) {
            return;
        }

        return this.iframeRef.contentWindow;
    };

    getPreviewScrollPosition = (): ?number => {
        const previewWindow = this.getPreviewWindow();
        if (previewWindow) {
            return previewWindow.document?.documentElement?.scrollTop
                || previewWindow.pageYOffset
                || previewWindow.document?.body?.scrollTop;
        }
    };

    setPreviewScrollPosition = (pos: number) => {
        const previewWindow = this.getPreviewWindow();
        if (previewWindow) {
            previewWindow.scrollTo({top: pos});
        }
    };

    @action setIframe = (iframeRef: ?Object) => {
        this.iframeRef = iframeRef;
    };

    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'medium') {
            return sidebarStore.setSize('large');
        }

        sidebarStore.setSize('medium');
    };

    @action handleDeviceSelectChange = (value: string | number) => {
        this.selectedDeviceOption = value;
    };

    @action handleDateTimeChange = debounce((value: ?Date) => {
        const {formStore} = this.props;

        this.previewStore.setDateTime(value || new Date());
        this.updatePreview(toJS(formStore.data));
    }, Preview.debounceDelay);

    @action handleWebspaceChange = (webspace: string) => {
        const {formStore} = this.props;

        this.previewStore.setWebspace(webspace);
        this.updatePreview(toJS(formStore.data));
    };

    handleTargetGroupChange = (targetGroupId: number) => {
        const {formStore} = this.props;

        this.previewStore.setTargetGroup(targetGroupId);
        this.updatePreview(toJS(formStore.data));
    };

    handleSegmentChange = (segmentKey: ?string) => {
        const {formStore} = this.props;

        this.previewStore.setSegment(segmentKey);
        this.updatePreview(toJS(formStore.data));
    };

    @action handleRefreshClick = () => {
        // We can not reload the iframe here as safari and firefox
        // resets the location.href to another url on previewDocument.open
        // so instead of this we rerender the whole iframe.
        ++this.reloadCounter;
    };

    handleStartClick = () => {
        this.startPreview();
    };

    @action handlePreviewWindowClick = () => {
        this.previewWindow = window.open(this.previewStore.renderRoute);
        this.previewWindow.addEventListener('beforeunload', action(() => {
            this.previewWindow = undefined;
        }));
    };

    render() {
        const {router} = this.props;
        const {previewWebspaceChooser = true} = router.route.options;

        if (this.previewWindow || (this.targetGroupsStore && this.targetGroupsStore.loading)) {
            return null;
        }

        if (Preview.mode !== 'auto' && !this.started) {
            return <button onClick={this.handleStartClick} type="button">Start</button>;
        }

        const containerClass = classNames(
            previewStyles.container,
            {
                [previewStyles[this.selectedDeviceOption]]: this.selectedDeviceOption,
            }
        );

        return (
            <div className={containerClass}>
                {this.previewStore.starting
                    ? <div className={previewStyles.loaderContainer}>
                        <Loader />
                    </div>
                    : <div className={previewStyles.previewContainer}>
                        <div className={previewStyles.iframeContainer}>
                            <iframe
                                className={previewStyles.iframe}
                                key={this.reloadCounter}
                                ref={this.setIframe}
                                src={this.previewStore.renderRoute}
                            />
                        </div>
                    </div>
                }
                <Toolbar skin="dark">
                    <Toolbar.Controls grow={true}>
                        <Toolbar.Button
                            icon={sidebarStore.size === 'medium' ? 'su-arrow-left' : 'su-arrow-right'}
                            onClick={this.handleToggleSidebarClick}
                        />
                        <Toolbar.Items>
                            <Toolbar.Select
                                icon="su-expand"
                                onChange={this.handleDeviceSelectChange}
                                options={this.availableDeviceOptions}
                                value={this.selectedDeviceOption}
                            />
                            <Toolbar.Popover
                                icon="su-calendar"
                                label={(this.previewStore?.dateTime || new Date()).toLocaleString()}
                            >
                                {() => (
                                    <div className={previewStyles.dateTimeForm}>
                                        <Form skin="dark">
                                            <Form.Field
                                                description={translate('sulu_admin.preview_date_time_description')}
                                                label={translate('sulu_admin.preview_date_time')}
                                            >
                                                <DatePicker
                                                    onChange={this.handleDateTimeChange}
                                                    options={{dateFormat: true, timeFormat: true}}
                                                    value={this.previewStore?.dateTime}
                                                />
                                            </Form.Field>
                                        </Form>
                                    </div>
                                )}
                            </Toolbar.Popover>
                            {previewWebspaceChooser &&
                                <Toolbar.Select
                                    icon="su-webspace"
                                    onChange={this.handleWebspaceChange}
                                    options={this.webspaceOptions}
                                    value={this.previewStore.webspace}
                                />
                            }
                            {!!this.targetGroupsStore &&
                                <Toolbar.Select
                                    icon="su-user"
                                    loading={this.targetGroupsStore.loading}
                                    onChange={this.handleTargetGroupChange}
                                    options={
                                        [
                                            {label: translate('sulu_audience_targeting.no_target_group'), value: -1},
                                            ...(this.targetGroupsStore
                                                ? this.targetGroupsStore.data.map((targetGroup) => ({
                                                    label: targetGroup.title,
                                                    value: targetGroup.id,
                                                }))
                                                : []
                                            ),
                                        ]
                                    }
                                    value={this.previewStore && this.previewStore.targetGroup}
                                />
                            }
                            {this.segments.length > 0 &&
                                <Toolbar.Select
                                    icon="su-focus"
                                    onChange={this.handleSegmentChange}
                                    options={
                                        this.segments.map(({title, key}) => ({
                                            label: title,
                                            value: key,
                                        }))
                                    }
                                    value={this.previewStore && this.previewStore.segment}
                                />
                            }
                            <Toolbar.Button
                                icon="su-sync"
                                onClick={this.handleRefreshClick}
                            >
                                {translate('sulu_preview.reload')}
                            </Toolbar.Button>
                            <Toolbar.Popover
                                icon="su-share"
                                label={translate('sulu_preview.preview_link')}
                            >
                                {() => (
                                    <PreviewLinkPopover
                                        previewStore={this.previewStore}
                                    />
                                )}
                            </Toolbar.Popover>
                            <Toolbar.Button
                                icon="su-link"
                                onClick={this.handlePreviewWindowClick}
                            >
                                {translate('sulu_preview.open_in_window')}
                            </Toolbar.Button>
                        </Toolbar.Items>
                    </Toolbar.Controls>
                </Toolbar>
            </div>
        );
    }
}

export default Preview;
