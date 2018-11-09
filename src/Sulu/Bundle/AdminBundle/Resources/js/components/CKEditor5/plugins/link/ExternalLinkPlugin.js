// @flow
import * as React from 'react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import {observable, action, observe} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from '../../../../utils/Translator';
import SingleSelect from '../../../SingleSelect';
import Url from '../../../Url';
import Dialog from '../../../Dialog';
import Form from '../../../Form/Form';
import createLinkPlugin from './src/link';

const {Field} = Form;

type Props = {
    open: boolean,
    plugin: ExternalLinkPlugin,
};

const DEFAULT_TARGET = '_blank';

@observer
export class ExternalLinkPluginComponent extends React.Component<Props> {
    @observable url: ?string;
    @observable selectedTarget: ?string;

    constructor(props: Props) {
        super(props);

        this.url = undefined;
        this.selectedTarget = DEFAULT_TARGET;
    }

    @action handleUrlChange = (value: ?string) => {
        this.url = value;
    };

    @action handleTargetChange = (value: string) => {
        this.selectedTarget = value;
    };

    handleCancel = () => {
        this.props.plugin.handleChange(null);
    };

    @action handleConfirm = () => {
        const {handleChange} = this.props.plugin;

        if (!this.url) {
            this.handleCancel();
            return;
        }

        const result = {
            value: this.url,
            attributes: {},
        };

        if (this.selectedTarget) {
            result.attributes.target = this.selectedTarget;
        }

        handleChange(result);
    };

    @action componentDidUpdate(prevProps: Props) {
        const {open, plugin: {currentValue}} = this.props;

        if (!prevProps.open && open && currentValue) {
            this.url = currentValue ? currentValue.value : undefined;

            const {attributes: {target}} = currentValue;
            if (target) {
                this.selectedTarget = target;
            }
        }

        if (prevProps.open && !open) {
            this.url = undefined;
            this.selectedTarget = DEFAULT_TARGET;
        }
    }

    render() {
        const {open} = this.props;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!this.url}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title="Link"
            >
                <Form>
                    <Field label="Link target" required={true}>
                        <SingleSelect onChange={this.handleTargetChange} value={this.selectedTarget}>
                            <SingleSelect.Option value="_blank">_blank</SingleSelect.Option>
                            <SingleSelect.Option value="_self">_self</SingleSelect.Option>
                            <SingleSelect.Option value="_parent">_parent</SingleSelect.Option>
                            <SingleSelect.Option value="_top">_top</SingleSelect.Option>
                        </SingleSelect>
                    </Field>

                    <Field label="Link URL" required={true}>
                        <Url defaultProtocol="https://" onChange={this.handleUrlChange} valid={true} value={this.url} />
                    </Field>
                </Form>
            </Dialog>
        );
    }
}

type ExternalLinkValueType = ?{
    value: string,
    attributes: {
        target?: string,
    },
};

export default class ExternalLinkPlugin {
    @observable open: boolean = false;
    setValue: (value: ExternalLinkValueType) => void = () => {};
    currentValue: ExternalLinkValueType = null;

    constructor(onChange: () => void) {
        observe(this, 'open', () => {
            onChange();
        });
    }

    @action handleChange = (value: ExternalLinkValueType): void => {
        this.setValue(value);
        this.setValue = () => {};
        this.currentValue = null;
        this.open = false;
    };

    @action handleLinkCommand = (
        setValue: (value: ExternalLinkValueType) => void,
        currentValue: ExternalLinkValueType
    ): void => {
        this.currentValue = currentValue;
        this.setValue = setValue;
        this.open = true;
    };

    getPlugin = (): Plugin => {
        return createLinkPlugin(
            'Link',
            'external',
            'a',
            'href',
            'externalLinkHref',
            this.handleLinkCommand,
            true
        );
    };
}
