// @flow
import React from 'react';
import ReactDOM from 'react-dom';
import {action, autorun, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import Router from 'sulu-admin-bundle/services/Router';
import {sidebarStore} from 'sulu-admin-bundle/containers/Sidebar';
import {FormStore} from 'sulu-admin-bundle/containers/Form';
import ResourceStore from 'sulu-admin-bundle/stores/ResourceStore';
import Requester from 'sulu-admin-bundle/services/Requester';
import Toolbar from 'sulu-admin-bundle/components/Toolbar';
import Loader from 'sulu-admin-bundle/components/Loader';
import previewStyles from './preview.scss';
import previewConfigStore from './stores/PreviewConfigStore';

type Props = {
    resourceStore: ResourceStore,
    formStore: FormStore,
    router: Router,
};

@observer
export default class Preview extends React.Component<Props> {
    @observable iframeRef: ?Object;
    @observable token: ?string;
    @observable started: boolean = false;

    typeDisposer: () => void;
    dataDisposer: () => void;

    componentDidMount() {
        if (previewConfigStore.mode === 'auto') {
            this.startPreview();
        }
    }

    @action startPreview = () => {
        const {
            resourceStore,
            formStore,
            router: {
                attributes: {
                    locale,
                },
            },
        } = this.props;

        const route = previewConfigStore.generateRoute('start', {
            provider: resourceStore.resourceKey,
            locale: locale,
            id: resourceStore.id,
        });
        if (!route) {
            return;
        }

        Requester.get(route).then((response) => {
            this.setToken(response.token);
        });

        this.typeDisposer = autorun(() => {
            if (formStore.loading || !this.iframeRef) {
                return;
            }

            // $FlowFixMe
            this.updateContext(formStore.type.get());
        });

        this.dataDisposer = autorun(() => {
            if (resourceStore.loading || !this.iframeRef) {
                return;
            }

            this.updatePreview(toJS(resourceStore.data));
        });

        this.started = true;
    };

    updatePreview = debounce((data) => {
        const {
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        const route = previewConfigStore.generateRoute('update', {
            locale: locale,
            webspace: webspace,
            token: this.token,
        });
        if (!route) {
            return;
        }

        Requester.post(route, {data: data}).then((response) => {
            const document = this.getPreviewDocument();
            if (!document) {
                return;
            }

            document.open();
            document.write(response.content);
            document.close();
        });
    }, previewConfigStore.debounceDelay);

    updateContext = debounce((type) => {
        const {
            router: {
                attributes: {
                    webspace,
                },
            },
        } = this.props;

        const route = previewConfigStore.generateRoute('update-context', {
            webspace: webspace,
            token: this.token,
        });
        if (!route) {
            return;
        }

        Requester.post(route, {context: {template: type}}).then((response) => {
            const document = this.getPreviewDocument();
            if (!document) {
                return;
            }

            document.open();
            document.write(response.content);
            document.close();
        });
    }, previewConfigStore.debounceDelay);

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

        const route = previewConfigStore.generateRoute('stop', {token: this.token});
        if (!route) {
            return;
        }

        Requester.get(route);
    }

    getPreviewDocument = (): ?Document => {
        // eslint-disable-next-line
        const iframe = ReactDOM.findDOMNode(this.iframeRef);
        if (!(iframe instanceof HTMLIFrameElement)) {
            return;
        }

        return iframe.contentDocument;
    };

    @action setToken = (token: string) => {
        this.token = token;
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

        if (!this.token) {
            return (
                <div className={previewStyles.container}>
                    <div className={previewStyles.loaderContainer}>
                        <Loader />
                    </div>

                    {this.renderToolbar()}
                </div>
            );
        }

        const {
            router: {
                attributes: {
                    locale,
                    webspace,
                },
            },
        } = this.props;

        const url = previewConfigStore.generateRoute('render', {
            webspace: webspace,
            locale: locale,
            token: this.token,
        });

        return (
            <div className={previewStyles.container}>
                <div className={previewStyles.iframeContainer}>
                    <iframe
                        className={previewStyles.iframe}
                        ref={this.setIframe}
                        src={url}
                    />
                </div>

                {this.renderToolbar()}
            </div>
        );
    }
}
