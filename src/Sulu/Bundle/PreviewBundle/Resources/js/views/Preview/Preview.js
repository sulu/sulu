// @flow
import React from 'react';
import ReactDOM from 'react-dom';
import {action, autorun, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {Router} from 'sulu-admin-bundle/services';
import {FormStore, sidebarStore} from 'sulu-admin-bundle/containers';
import {Loader, Toolbar} from 'sulu-admin-bundle/components';
import previewStyles from './preview.scss';
import PreviewStore from './stores/PreviewStore';
import type {PreviewMode} from './types';

type Props = {|
    formStore: FormStore,
    router: Router,
|};

@observer
export default class Preview extends React.Component<Props> {
    static debounceDelay: number = 250;
    static mode: PreviewMode = 'auto';

    @observable iframeRef: ?HTMLIFrameElement;
    @observable started: boolean = false;

    previewStore: PreviewStore;

    typeDisposer: () => void;
    dataDisposer: () => void;

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

        this.previewStore.start().then(() => {
            this.setStarted(true);
        });

        this.dataDisposer = autorun(() => {
            if (this.previewStore.starting || formStore.loading || !this.iframeRef) {
                return;
            }

            this.updatePreview(toJS(formStore.data));
        });

        this.typeDisposer = autorun(() => {
            if (this.previewStore.starting || formStore.loading || !this.iframeRef) {
                return;
            }

            this.previewStore.updateContext(toJS(formStore.type)).then(this.setContent);
        });
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

        this.previewStore.stop();
    }

    getPreviewDocument = (): ?Document => {
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

    handleRefreshClick = () => {
        const document = this.getPreviewDocument();
        if (!document) {
            return;
        }

        document.location.reload();
    };

    handleStartClick = () => {
        this.startPreview();
    };

    renderToolbar() {
        return (
            <Toolbar skin="dark">
                <Toolbar.Controls>
                    <Toolbar.Button
                        icon={sidebarStore.size === 'medium' ? 'su-arrow-left' : 'su-arrow-right'}
                        onClick={this.handleToggleSidebarClick}
                    />
                    <Toolbar.Button
                        icon="fa-refresh"
                        onClick={this.handleRefreshClick}
                    />
                </Toolbar.Controls>
            </Toolbar>
        );
    }

    render() {
        if (!this.started) {
            return <button onClick={this.handleStartClick}>Start</button>;
        }

        if (this.previewStore.starting) {
            return (
                <div className={previewStyles.container}>
                    <div className={previewStyles.loaderContainer}>
                        <Loader />
                    </div>

                    {this.renderToolbar()}
                </div>
            );
        }

        return (
            <div className={previewStyles.container}>
                <div className={previewStyles.iframeContainer}>
                    <iframe
                        className={previewStyles.iframe}
                        ref={this.setIframe}
                        src={this.previewStore.renderRoute}
                    />
                </div>

                {this.renderToolbar()}
            </div>
        );
    }
}
