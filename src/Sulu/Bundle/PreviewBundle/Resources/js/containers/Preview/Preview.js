// @flow
import React from 'react';
import ReactDOM from 'react-dom';
import {action, observable, reaction, toJS, when} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {Router} from 'sulu-admin-bundle/services';
import {ResourceFormStore, sidebarStore} from 'sulu-admin-bundle/containers';
import {Loader, Toolbar} from 'sulu-admin-bundle/components';
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

    @observable iframeRef: ?HTMLIFrameElement;
    @observable started: boolean = false;

    previewStore: PreviewStore;

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
