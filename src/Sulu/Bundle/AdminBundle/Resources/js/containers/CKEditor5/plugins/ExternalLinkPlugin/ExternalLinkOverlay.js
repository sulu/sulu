// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Dialog from '../../../../components/Dialog';
import Form from '../../../../components/Form';
import Input from '../../../../components/Input';
import SingleSelect from '../../../../components/SingleSelect';
import TextArea from '../../../../components/TextArea';
import Url from '../../../../components/Url';
import {translate} from '../../../../utils/Translator';

type Props = {|
    onCancel: () => void,
    onConfirm: () => void,
    onTargetChange: (target: string) => void,
    onTitleChange: (title: ?string) => void,
    onUrlChange: (url: ?string) => void,
    open: boolean,
    target: ?string,
    title: ?string,
    url: ?string,
|};

@observer
class ExternalLinkOverlay extends React.Component<Props> {
    @observable protocol: ?string = undefined;
    @observable url: ?string = undefined;
    @observable mailSubject: ?string = undefined;
    @observable mailBody: ?string = undefined;

    constructor(props: Props) {
        super(props);

        this.updateUrl();
    }

    @action componentDidUpdate(prevProps: Props) {
        if (prevProps.open === false && this.props.open === true) {
            this.updateUrl();
        }
    }

    updateUrl() {
        const {url} = this.props;

        if (!url) {
            this.url = undefined;
            return;
        }

        const urlParts = url.split('?');

        this.url = urlParts[0];

        const urlParameters = new URLSearchParams(urlParts[1]);
        const mailSubject = urlParameters.get('subject');
        const mailBody = urlParameters.get('body');

        this.mailSubject = mailSubject ? mailSubject : undefined;
        this.mailBody = mailBody ? mailBody : undefined;
    }

    callUrlChange = () => {
        const {onTargetChange, onUrlChange} = this.props;
        const {mailBody, mailSubject, url} = this;

        if (!url) {
            onUrlChange(undefined);
            return;
        }

        if (url && url.startsWith('mailto:')) {
            onTargetChange('_self');
        }

        const urlParameters = new URLSearchParams();

        if (mailSubject) {
            urlParameters.set('subject', mailSubject);
        }

        if (mailBody) {
            urlParameters.set('body', mailBody);
        }

        onUrlChange(
            url + (
                Array.from(urlParameters).length > 0
                    // Replacing value is required, because Apple Mail does not seem to understand it otherwise
                    ? '?' + urlParameters.toString().replace(/\+/g, '%20')
                    : ''
            )
        );
    };

    handleUrlBlur = this.callUrlChange;

    @action handleUrlChange = (url: ?string) => {
        this.url = url;
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

    render() {
        const {
            onCancel,
            onConfirm,
            onTargetChange,
            onTitleChange,
            open,
            target,
            title,
            url,
        } = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!url}
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
                            onChange={this.handleUrlChange}
                            onProtocolChange={this.handleProtocolChange}
                            protocols={['http://', 'https://', 'ftp://', 'ftps://', 'mailto:']}
                            valid={true}
                            value={this.url}
                        />
                    </Form.Field>

                    {this.protocol && this.protocol !== 'mailto:' &&
                        <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                            <SingleSelect onChange={onTargetChange} value={target}>
                                <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                                <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                                <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                                <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                            </SingleSelect>
                        </Form.Field>
                    }

                    {this.protocol && this.protocol === 'mailto:' &&
                        <Fragment>
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

                    <Form.Field label={translate('sulu_admin.link_title')}>
                        <Input onChange={onTitleChange} value={title} />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}

export default ExternalLinkOverlay;
