// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import copyToClipboard from 'copy-to-clipboard';
import Button from 'sulu-admin-bundle/components/Button';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {transformDateForUrl} from 'sulu-admin-bundle/utils/Date';
import {translate} from 'sulu-admin-bundle/utils';
import PreviewStore from './stores/PreviewStore';
import previewLinkStyles from './preview-link.scss';
import type {PreviewLink} from './types';

type Props = {|
    previewStore: PreviewStore,
|};

@observer
class PreviewLinkPopover extends React.Component<Props> {
    @observable previewLink: ?PreviewLink;
    @observable loading: boolean = false;
    @observable generating: boolean = false;
    @observable copying: boolean = false;

    componentDidMount() {
        this.loadPreviewLink();
    }

    @action loadPreviewLink() {
        const {
            previewStore,
        } = this.props;

        this.loading = true;
        ResourceRequester.get('preview_links', {
            resourceKey: previewStore.resourceKey,
            resourceId: previewStore.id,
            locale: previewStore.locale,
        }).then(action((previewLink) => {
            this.previewLink = previewLink;
            this.loading = false;
        })).catch(action((error) => {
            if (error.status !== 404) {
                return Promise.reject(error);
            }

            this.loading = false;
        }));
    }

    @action handleGenerateClick = () => {
        const {
            previewStore,
        } = this.props;

        this.generating = true;
        ResourceRequester.post('preview_links', {}, {
            action: 'generate',
            resourceKey: previewStore.resourceKey,
            resourceId: previewStore.id,
            locale: previewStore.locale,
            webspaceKey: previewStore.webspace,
            segmentKey: previewStore.segment,
            targetGroupId: previewStore.targetGroup,
            dateTime: previewStore.dateTime && transformDateForUrl(previewStore.dateTime),
        }).then(action((previewLink) => {
            this.previewLink = previewLink;
        })).finally(action(() => this.generating = false));
    };

    handleRevokeClick = (event: SyntheticEvent<HTMLFormElement>) => {
        event.preventDefault();

        const {
            previewStore,
        } = this.props;

        ResourceRequester.post('preview_links', {}, {
            action: 'revoke',
            resourceKey: previewStore.resourceKey,
            resourceId: previewStore.id,
            locale: previewStore.locale,
        }).then(action(() => {
            this.previewLink = null;
        }));
    };

    @action handleCopyClick = () => {
        this.copying = true;
        setTimeout(action(() => this.copying = false), 125);

        copyToClipboard(this.link);
    };

    @computed get link() {
        if (!this.previewLink) {
            return '';
        }

        return PreviewStore.endpoints['preview-link'].replace(':token', this.previewLink.token);
    }

    render() {
        if (this.loading) {
            return null;
        }

        return (
            <div className={previewLinkStyles.container}>
                {this.previewLink && (
                    <React.Fragment>
                        <div>
                            <label className={previewLinkStyles.label}>
                                {translate('sulu_preview.copy_preview_link')}
                            </label>
                            <div className={previewLinkStyles.inputContainer}>
                                <input
                                    className={previewLinkStyles.input}
                                    readOnly={true}
                                    value={this.link}
                                />

                                <Button
                                    className={previewLinkStyles.copyButton}
                                    loading={this.copying}
                                    onClick={this.handleCopyClick}
                                    skin="primary"
                                >
                                    {translate('sulu_preview.copy')}
                                </Button>
                            </div>
                        </div>
                        <div className={previewLinkStyles.revoke}>
                            <button
                                className={previewLinkStyles.revokeButton}
                                onClick={this.handleRevokeClick}
                                type="button"
                            >
                                {translate('sulu_preview.revoke')}
                            </button>
                        </div>
                    </React.Fragment>
                )}
                {!this.previewLink && (
                    <React.Fragment>
                        <Button
                            loading={this.generating}
                            onClick={this.handleGenerateClick}
                            skin="primary"
                        >
                            {translate('sulu_preview.generate_link')}
                        </Button>
                    </React.Fragment>
                )}
            </div>
        );
    }
}

export default PreviewLinkPopover;
