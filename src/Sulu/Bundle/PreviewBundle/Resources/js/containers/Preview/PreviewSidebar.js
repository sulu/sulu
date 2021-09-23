// @flow
import React from 'react';
import {action, computed, observable, reaction, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {ResourceFormStore, sidebarStore} from 'sulu-admin-bundle/containers';
import {Router} from 'sulu-admin-bundle/services';
import {webspaceStore} from 'sulu-page-bundle/stores';
import PreviewStore from './stores/PreviewStore';
import Preview from './Preview';
import type {PreviewMode} from './types';
import previewStyles from './preview.scss';
import {Loader} from 'sulu-admin-bundle/components';
import {ResourceListStore} from 'sulu-admin-bundle/stores';

type Props = {|
    formStore: ResourceFormStore,
    router: Router,
|};

@observer
class PreviewSidebar extends React.Component<Props> {
    static debounceDelay: number = 250;
    static mode: PreviewMode = 'auto';

    @observable started: boolean = false;

    @observable previewStore: PreviewStore;
    @observable previewWindow: any;
    @observable webspaceOptions: Array<Object> = [];
    @observable targetGroupOptions: Array<Object> = [];

    @observable iframeRef: ?HTMLIFrameElement;
    @observable reloadCounter: number = 0;

    @observable targetGroupsStore: ?ResourceListStore;

    schemaDisposer: () => mixed;
    dataDisposer: () => mixed;

    @computed get webspaceKey() {
        const {
            router: {
                attributes: {
                    webspace,
                },
            },
        } = this.props;

        if (webspace !== undefined && typeof webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string if set!');
        }

        return webspace || this.webspaceOptions[0].value;
    }

    @computed get segments() {
        if (!this.webspaceKey) {
            return [];
        }

        return webspaceStore.getWebspace(this.webspaceKey).segments;
    }

    constructor(props: Props) {
        super(props);

        const {
            formStore,
        } = this.props;

        if (PreviewSidebar.audienceTargeting) {
            this.targetGroupsStore = new ResourceListStore('target_groups');

            this.targetGroupOptions = this.targetGroupsStore.data.map((targetGroup) => ({
                label: targetGroup.title,
                value: targetGroup.id,
            }));
        }

        this.webspaceOptions = webspaceStore.grantedWebspaces.map((webspace): Object => ({
            label: webspace.name,
            value: webspace.key,
        }));

        this.previewStore = new PreviewStore(
            formStore.resourceKey,
            formStore.id,
            formStore.locale,
            this.webspaceKey,
            this.segments.find((segment) => segment.default === true)?.key
        );

        if (PreviewSidebar.mode === 'auto') {
            this.startPreview();
        }
    }

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
            this.initializeReaction
        );

        this.setStarted(true);
    };

    initializeReaction = (): void => {
        const {previewStore} = this;

        const {
            formStore,
        } = this.props;

        this.dataDisposer = reaction(
            () => toJS(formStore.data),
            (data) => {
                this.updatePreview(data);
            }
        );

        this.schemaDisposer = reaction(
            () => toJS(formStore.schema),
            () => {
                previewStore.updateContext(toJS(formStore.type), toJS(formStore.data)).then(this.setContent);
            }
        );
    };

    updatePreview = debounce((data: Object) => {
        const {previewStore} = this;
        previewStore.update(data).then(this.setContent);
    }, Preview.debounceDelay);

    setContent = (previewContent: string) => {
        const previewDocument = this.getPreviewDocument();

        if (!previewDocument) {
            return;
        }

        previewDocument.open(); // This will lose in Firefox the and safari previewDocument.location
        previewDocument.write(previewContent);
        previewDocument.close();
    };

    getPreviewDocument = (): ?Document => {
        if (this.previewWindow) {
            return this.previewWindow.document;
        }

        if (!(this.iframeRef instanceof HTMLIFrameElement)) {
            return;
        }

        return this.iframeRef.contentDocument;
    };

    componentWillUnmount() {
        if (this.schemaDisposer) {
            this.schemaDisposer();
        }

        if (this.dataDisposer) {
            this.dataDisposer();
        }

        if (!this.started) {
            return;
        }

        this.updatePreview.clear();
        this.previewStore.stop();
    }

    @action setIframe = (iframeRef: ?Object) => {
        this.iframeRef = iframeRef;
    };

    handleToggleSidebarClick = () => {
        if (sidebarStore.size === 'medium') {
            return sidebarStore.setSize('large');
        }

        sidebarStore.setSize('medium');
    };

    @action handleDateTimeChange = debounce((value: ?Date) => {
        this.previewStore.setDateTime(value || new Date());
    }, Preview.debounceDelay);

    @action handleWebspaceChange = (webspace: string) => {
        this.previewStore.setWebspace(webspace);
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

    handleStartClick = () => {
        this.startPreview();
    };

    @action handlePreviewWindowClick = () => {
        this.previewWindow = window.open(this.previewStore.renderRoute);
        this.previewWindow.addEventListener('beforeunload', action(() => {
            this.previewWindow = undefined;
        }));
    };

    @action handleRefreshClick = () => {
        // We can not reload the iframe here as safari and firefox
        // resets the location.href to another url on previewDocument.open
        // so instead of this we rerender the whole iframe.
        ++this.reloadCounter;
    };

    render() {
        const {router} = this.props;
        const {previewWebspaceChooser = true} = router.route.options;

        if (this.previewWindow) {
            return null;
        }

        if (!this.started) {
            return <button onClick={this.handleStartClick}>Start</button>;
        }

        if (this.previewStore.starting || (this.targetGroupsStore && this.targetGroupsStore.loading)) {
            return (
                <div className={previewStyles.container}>
                    <div className={previewStyles.loaderContainer}>
                        <Loader/>
                    </div>
                </div>
            );
        }

        return (
            <Preview
                webspace={this.webspaceKey}
                webspaceOptions={this.webspaceOptions}
                segments={this.segments}
                segment={this.previewStore.segment}
                targetGroup={this.previewStore.targetGroup}
                targetGroupOptions={this.targetGroupOptions}
                size={sidebarStore.size}
                dateTime={this.previewStore?.dateTime}
                renderRoute={this.previewStore.renderRoute}
                onPreviewWindowClick={this.handlePreviewWindowClick}
                onSegmentChange={this.handleSegmentChange}
                onTargetGroupChange={this.targetGroupsStore ? this.handleTargetGroupChange : null}
                onDateTimeChange={this.handleDateTimeChange}
                onToggleSidebarClick={this.handleToggleSidebarClick}
                onWebspaceChange={previewWebspaceChooser ? this.handleWebspaceChange : null}
                onRefreshClick={this.handleRefreshClick}
            >
                <iframe
                    className={previewStyles.iframe}
                    key={this.reloadCounter}
                    ref={this.setIframe}
                    src={this.previewStore.renderRoute}
                />
            </Preview>
        );
    }
}

export default PreviewSidebar;
