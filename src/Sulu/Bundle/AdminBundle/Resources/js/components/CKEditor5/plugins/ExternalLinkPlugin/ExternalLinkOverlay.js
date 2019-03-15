// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Dialog from '../../../Dialog';
import Form from '../../../Form';
import SingleSelect from '../../../SingleSelect';
import Url from '../../../Url';
import {translate} from '../../../../utils/Translator';
import type {ExternalLinkEventInfo} from './types';

const DEFAULT_TARGET = '_blank';

type Props = {|
    onCancel: () => void,
    onConfirm: (value: ExternalLinkEventInfo) => void,
    open: boolean,
|};

@observer
export default class ExternalLinkOverlay extends React.Component<Props> {
    @observable url: ?string;
    @observable selectedTarget: ?string;

    constructor(props: Props) {
        super(props);

        this.url = undefined;
        this.selectedTarget = DEFAULT_TARGET;
    }

    @action handleUrlChange = (url: ?string) => {
        this.url = url;
    };

    @action handleTargetChange = (selectedTarget: string) => {
        this.selectedTarget = selectedTarget;
    };

    handleConfirm = () => {
        const {onConfirm} = this.props;

        onConfirm({target: this.selectedTarget, url: this.url});
    };

    @action componentDidUpdate(prevProps: Props) {
        if (prevProps.open && !this.props.open) {
            this.url = undefined;
            this.selectedTarget = DEFAULT_TARGET;
        }
    }

    render() {
        const {onCancel, open} = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!this.url}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={onCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title="Link"
            >
                <Form>
                    <Form.Field label="Link target" required={true}>
                        <SingleSelect onChange={this.handleTargetChange} value={this.selectedTarget}>
                            <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                            <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                            <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                            <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                        </SingleSelect>
                    </Form.Field>

                    <Form.Field label="Link URL" required={true}>
                        <Url defaultProtocol="https://" onChange={this.handleUrlChange} valid={true} value={this.url} />
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}
