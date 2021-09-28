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
import previewStyles from './preview.scss';
import type {PreviewLink} from './types';

type Props = {|
    previewStore: PreviewStore,
|};

@observer
class PreviewLinkPopover extends React.Component<Props> {
    @observable previewLink: ?PreviewLink;
    @observable loading: boolean;
    @observable generating: boolean = false;

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
        })).catch(action(() => {
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

    handleRevokeClick = () => {
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

    handleCopyClick = () => {
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
            <div className={previewStyles.previewLinkContainer}>
                {this.previewLink && (
                    <React.Fragment>
                        <div style={{marginBottom: '10px'}}>
                            <a href={this.link} style={{color: '#fff'}} target="blank">{this.link}</a>
                        </div>
                        <div>
                            <Button
                                className={previewStyles.previewLinkButton}
                                onClick={this.handleCopyClick}
                                skin="secondary"
                            >
                                {translate('sulu_preview.copy_to_clipboard')}
                            </Button>
                            <Button
                                onClick={this.handleRevokeClick}
                                skin="secondary"
                            >
                                {translate('sulu_preview.revoke')}
                            </Button>
                        </div>
                    </React.Fragment>
                )}
                {!this.previewLink && (
                    <React.Fragment>
                        <Button
                            loading={this.generating}
                            onClick={this.handleGenerateClick}
                            skin="secondary"
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
