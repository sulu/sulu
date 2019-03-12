// @flow
import React from 'react';
import ReactDOM from 'react-dom';
import {action, observable, reaction, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import classNames from 'classnames';
import {Loader, Toolbar} from 'sulu-admin-bundle/components';
import {ResourceFormStore, sidebarStore} from 'sulu-admin-bundle/containers';
import {Router} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import previewStyles from './preview.scss';
import PreviewStore from './stores/PreviewStore';
import type {PreviewMode} from './types';

type Props = {|
    formStore: ResourceFormStore,
    router: Router,
|};

@observer
export default class Preview extends React.Component<Props> {
    static debounceDelay: number = 250;
    static mode: PreviewMode = 'auto';

    availableDeviceOptions = [
        {label: translate('sulu_preview.auto'), value: 'auto'},
        {label: translate('sulu_preview.desktop'), value: 'desktop'},
        {label: translate('sulu_preview.tablet'), value: 'tablet'},
        {label: translate('sulu_preview.smartphone'), value: 'smartphone'},
    ];

    @observable iframeRef: ?HTMLIFrameElement;
    @observable started: boolean = false;
    @observable selectedDeviceOption = this.availableDeviceOptions[0].value;

    previewStore: PreviewStore;
    @observable previewWindow: any;

    typeDisposer: () => mixed;
    dataDisposer: () => mixed;

    constructor(props: Props) {
        super(props);

        const {
            formStore,
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        this.previewStore = new PreviewStore(formStore.resourceKey, formStore.id, locale, webspace);
    }

    componentDidMount() {
        if (Preview.mode === 'auto') {
            this.startPreview();
        }
    }

    @action setStarted = (started: boolean) => {
        this.started = started;
    };

    startPreview = () => {
        const {
            formStore,
        } = this.props;

        this.previewStore.start();

        when(
            () => !formStore.loading && !this.previewStore.starting && this.iframeRef !== null,
            this.initializeReaction
        );

        this.setStarted(true);
    };

    initializeReaction = (): void => {
        const {
            formStore,
        } = this.props;

        this.dataDisposer = reaction(
            () => toJS(formStore.data),
            (data) => {
                this.updatePreview(data);
            }
        );

        this.typeDisposer = reaction(
            () => toJS(formStore.type),
            (type) => {
                this.previewStore.updateContext(type).then(this.setContent);
            }
        );
    };

    updatePreview = debounce((data: Object) => {
        this.previewStore.update(data).then(this.setContent);
    }, Preview.debounceDelay);

    setContent = (content: string) => {
        const document = this.getPreviewDocument();
        if (!document) {
            return;
        }

        document.open();
        document.write(content);
        document.close();
    };

    componentWillUnmount() {
        if (this.typeDisposer) {
            this.typeDisposer();
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

    getPreviewDocument = (): ?Document => {
        if (this.previewWindow) {
            return this.previewWindow.document;
        }

        // eslint-disable-next-line react/no-find-dom-node
        const iframe = ReactDOM.findDOMNode(this.iframeRef);
        if (!(iframe instanceof HTMLIFrameElement)) {
            return;
        }

        return iframe.contentDocument;
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

    @action handleDeviceSelectChange = (value: string) => {
        this.selectedDeviceOption = value;
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
        if (this.previewWindow) {
            return null;
        }

        if (!this.started) {
            return <button onClick={this.handleStartClick}>Start</button>;
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
                                ref={this.setIframe}
                                src={this.previewStore.renderRoute}
                            />
                        </div>
                    </div>
                }
                <Toolbar skin="dark">
                    <Toolbar.Controls>
                        <Toolbar.Button
                            icon={sidebarStore.size === 'medium' ? 'su-arrow-left' : 'su-arrow-right'}
                            onClick={this.handleToggleSidebarClick}
                        />
                        <Toolbar.Select
                            icon="su-expand"
                            onChange={this.handleDeviceSelectChange}
                            options={this.availableDeviceOptions}
                            value={this.selectedDeviceOption}
                        />
                        <Toolbar.Button
                            icon="su-link"
                            onClick={this.handlePreviewWindowClick}
                        >
                            {translate('sulu_preview.open_in_window')}
                        </Toolbar.Button>
                    </Toolbar.Controls>
                </Toolbar>
            </div>
        );
    }
}
