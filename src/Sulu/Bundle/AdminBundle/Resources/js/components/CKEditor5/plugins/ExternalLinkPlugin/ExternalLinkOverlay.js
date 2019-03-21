// @flow
import React from 'react';
import {observer} from 'mobx-react';
import Dialog from '../../../Dialog';
import Form from '../../../Form';
import SingleSelect from '../../../SingleSelect';
import Url from '../../../Url';
import {translate} from '../../../../utils/Translator';

type Props = {|
    onCancel: () => void,
    onConfirm: () => void,
    onTargetChange: (target: string) => void,
    onUrlChange: (url: ?string) => void,
    open: boolean,
    target: ?string,
    url: ?string,
|};

@observer
export default class ExternalLinkOverlay extends React.Component<Props> {
    constructor(props: Props) {
        super(props);
    }

    handleConfirm = () => {
        const {onConfirm} = this.props;

        onConfirm();
    };

    render() {
        const {onCancel, onTargetChange, onUrlChange, open, target, url} = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!url}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={onCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_admin.link')}
            >
                <Form>
                    <Form.Field label={translate('sulu_admin.link_target')} required={true}>
                        <SingleSelect onChange={onTargetChange} value={target}>
                            <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                            <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                            <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                            <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                        </SingleSelect>
                    </Form.Field>

                    <Form.Field label={translate('sulu_admin.link_url')} required={true}>
                        <Url defaultProtocol="https://" onChange={onUrlChange} valid={true} value={url} />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}
