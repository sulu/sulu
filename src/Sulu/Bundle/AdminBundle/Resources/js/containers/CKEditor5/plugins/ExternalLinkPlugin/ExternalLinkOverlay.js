// @flow
import React from 'react';
import Dialog from '../../../../components/Dialog';
import Form from '../../../../components/Form';
import Input from '../../../../components/Input';
import SingleSelect from '../../../../components/SingleSelect';
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

export default class ExternalLinkOverlay extends React.Component<Props> {
    render() {
        const {onCancel, onConfirm, onTargetChange, onTitleChange, onUrlChange, open, target, title, url} = this.props;

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
                            onChange={onUrlChange}
                            protocols={['http://', 'https://', 'ftp://', 'ftps://', 'mailto:']}
                            valid={true}
                            value={url}
                        />
                    </Form.Field>

                    {url && !url.startsWith('mailto:') &&
                        <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                            <SingleSelect onChange={onTargetChange} value={target}>
                                <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                                <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                                <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                                <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                            </SingleSelect>
                        </Form.Field>
                    }

                    <Form.Field label={translate('sulu_admin.link_title')}>
                        <Input onChange={onTitleChange} value={title} />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}
