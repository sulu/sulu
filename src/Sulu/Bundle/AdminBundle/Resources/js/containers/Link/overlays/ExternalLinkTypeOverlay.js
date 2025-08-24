// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed} from 'mobx';
import Dialog from '../../../components/Dialog';
import Form from '../../../components/Form';
import Input from '../../../components/Input';
import SingleSelect from '../../../components/SingleSelect';
import Toggler from '../../../components/Toggler';
import TextArea from '../../../components/TextArea';
import Url from '../../../components/Url';
import {translate} from '../../../utils';
import type {LinkTypeOverlayProps} from '../types';

@observer
class ExternalLinkTypeOverlay extends React.Component<LinkTypeOverlayProps> {
    @observable protocol: ?string = undefined;
    @observable href: ?string = undefined;
    @observable mailSubject: ?string = undefined;
    @observable mailBody: ?string = undefined;

    constructor(props: LinkTypeOverlayProps) {
        super(props);

        this.updateUrl();
    }

    @action componentDidUpdate(prevProps: LinkTypeOverlayProps) {
        if (prevProps.open === false && this.props.open === true) {
            this.updateUrl();
        }
    }

    updateUrl() {
        const {
            href,
        } = this.props;

        if (!href) {
            this.href = undefined;

            return;
        }

        if (typeof href === 'string' && href.startsWith('mailto:')) {
            const urlParts = href.split('?');
            const urlParameters = new URLSearchParams(urlParts[1]);
            const mailSubject = urlParameters.get('subject');
            const mailBody = urlParameters.get('body');

            this.href = urlParts[0];
            this.mailSubject = mailSubject ? mailSubject : undefined;
            this.mailBody = mailBody ? mailBody : undefined;

            return;
        }

        this.href = String(href);
        this.mailSubject = undefined;
        this.mailBody = undefined;
    }

    callUrlChange = () => {
        const {
            onTargetChange, onHrefChange,
        } = this.props;
        const {
            mailBody, mailSubject, href,
        } = this;

        if (!href) {
            onHrefChange(undefined);

            return;
        }

        const urlParameters = new URLSearchParams();

        if (href.startsWith('mailto:')) {
            if (onTargetChange) {
                onTargetChange('_self');
            }

            if (mailSubject) {
                urlParameters.set('subject', mailSubject);
            }

            if (mailBody) {
                urlParameters.set('body', mailBody);
            }
        }

        onHrefChange(
            href + (
                Array.from(urlParameters).length > 0
                    // Replacing value is required, because Apple Mail does not seem to understand it otherwise
                    ? '?' + urlParameters.toString().replace(/\+/g, '%20')
                    : ''
            )
        );
    };

    handleUrlBlur = this.callUrlChange;

    @action handleHrefChange = (href: ?string) => {
        this.href = href;
    };

    handleMailSubjectBlur = this.callUrlChange;

    @action handleProtocolChange = (protocol: ?string) => {
        this.protocol = protocol;
    };

    @action handleMailSubjectChange = (mailSubject: ?string) => {
        this.mailSubject = mailSubject;
    };

    handleMailBodyBlur = this.callUrlChange;

    @action handleMailBodyChange = (mailBody: ?string) => {
        this.mailBody = mailBody;
    };

    handleRelNoFollowChange = (noFollow: boolean) => {
        const {
            onRelChange,
            rel,
        } = this.props;

        if (!onRelChange) {
            return;
        }

        let rels = (rel || '').toLowerCase().trim().split(' ').map((v) => v.trim()).filter((v) => !!v);

        if (noFollow && !rels.includes('nofollow')) {
            rels = [...rels, 'nofollow'];
        } else if (!noFollow && rels.includes('nofollow')) {
            rels = rels.filter((v) => v !== 'nofollow');
        }

        const newRel = rels.join(' ') || undefined;

        if (rel !== newRel) {
            onRelChange(newRel);
        }
    };

    @computed get isRelNoFollow(): boolean {
        const {
            rel,
        } = this.props;

        if (!rel) {
            return false;
        }

        return rel.toLowerCase().includes('nofollow');
    }

    render() {
        const {
            onCancel,
            onConfirm,
            onTargetChange,
            onTitleChange,
            onRelChange,
            open,
            target,
            title,
            href,
            options,
        } = this.props;

        const targets = options?.targets || ['_blank', '_self', '_parent', '_top'];

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!href}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={onCancel}
                onConfirm={onConfirm}
                open={open}
                title={translate('sulu_admin.link')}
            >
                <Form>
                    <Form.Field label={translate('sulu_admin.link_url')} required={true}>
                        <Url
                            defaultProtocol="https://"
                            onBlur={this.handleUrlBlur}
                            onChange={this.handleHrefChange}
                            onProtocolChange={this.handleProtocolChange}
                            valid={true}
                            value={this.href}
                        />
                    </Form.Field>

                    {this.protocol && this.protocol !== 'mailto:' && onTargetChange
                        && <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                            <SingleSelect onChange={onTargetChange} value={target}>
                                {targets.map((targetValue) => (
                                    <SingleSelect.Option key={targetValue} value={targetValue}>
                                        {translate(`sulu_admin.link_target${targetValue}`)}
                                    </SingleSelect.Option>
                                ))}
                            </SingleSelect>
                        </Form.Field>
                    }

                    {this.protocol && this.protocol === 'mailto:'
                        && <Fragment>
                            <Form.Field label={translate('sulu_admin.mail_subject')}>
                                <Input
                                    onBlur={this.handleMailSubjectBlur}
                                    onChange={this.handleMailSubjectChange}
                                    value={this.mailSubject}
                                />
                            </Form.Field>
                            <Form.Field label={translate('sulu_admin.mail_body')}>
                                <TextArea
                                    onBlur={this.handleMailBodyBlur}
                                    onChange={this.handleMailBodyChange}
                                    value={this.mailBody}
                                />
                            </Form.Field>
                        </Fragment>
                    }

                    {onTitleChange
                        && <Form.Field label={translate('sulu_admin.link_title')}>
                            <Input onChange={onTitleChange} value={title} />
                        </Form.Field>
                    }

                    {onRelChange
                        && <Form.Field>
                            <Toggler checked={this.isRelNoFollow} onChange={this.handleRelNoFollowChange}>
                                {translate('sulu_admin.no_follow')}
                            </Toggler>
                        </Form.Field>
                    }
                </Form>
            </Dialog>
        );
    }
}

export default ExternalLinkTypeOverlay;
