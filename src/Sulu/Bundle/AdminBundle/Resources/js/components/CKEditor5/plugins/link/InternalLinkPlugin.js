// @flow
import * as React from 'react';
import Plugin from '@ckeditor/ckeditor5-core/src/plugin';
import {observable, action, observe} from 'mobx';
import {observer} from 'mobx-react';
import linkTypeConfigStore from '../../../../stores/LinkTypeConfigStore';
import SingleSelection from '../../../../containers/SingleSelection';
import {translate} from '../../../../utils/Translator';
import SingleSelect from '../../../SingleSelect';
import Dialog from '../../../Dialog';
import Form from '../../../Form/Form';
import createLinkPlugin from './src/link';

const {Field} = Form;

type Props = {
    open: boolean,
    plugin: InternalLinkPlugin,
};

const DEFAULT_TARGET = '_self';

@observer
export class InternalLinkPluginComponent extends React.Component<Props> {
    static defaultProvider: string;

    @observable selectedResource: ?{
        uuid: string,
        title?: ?string,
    };
    @observable selectedTarget: ?string;
    @observable provider: ?string;
    @observable selectedResourceError: string | typeof undefined;

    constructor(props: Props) {
        super(props);

        InternalLinkPluginComponent.defaultProvider = linkTypeConfigStore.getProviders()[0];

        this.selectedResource = undefined;
        this.selectedTarget = DEFAULT_TARGET;
        this.provider = undefined;
        this.selectedResourceError = undefined;
    }

    @action handleResourceChange = (uuid: ?string | number, item: ?Object) => {
        if (!item) {
            return;
        }

        this.selectedResource = uuid
            ? {
                uuid: uuid.toString(),
                title: item.title || undefined,
            }
            : undefined;

        this.selectedResourceError = this.selectedResource ? undefined : 'Bitte Seite auswählen';
    };

    @action handleTargetChange = (value: string) => {
        this.selectedTarget = value;
    };

    @action handleResourceTypeChange = (value: string) => {
        this.selectedResource = undefined;
        this.provider = value;
    };

    handleCancel = () => {
        this.props.plugin.handleChange(null);
    };

    @action handleConfirm = () => {
        const {handleChange} = this.props.plugin;

        if (!this.selectedResource) {
            this.handleCancel();
            return;
        }

        const result = {
            value: this.selectedResource.uuid,
            text: this.selectedResource.title || undefined,
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
            this.selectedResource = currentValue ? {
                uuid: currentValue.value,
            } : undefined;

            const {attributes: {target}} = currentValue;
            if (target) {
                this.selectedTarget = target;
            }
        }

        if (prevProps.open && !open) {
            this.selectedResource = undefined;
            this.selectedTarget = DEFAULT_TARGET;
            this.provider = undefined;
            this.selectedResourceError = undefined;
        }
    }

    render() {
        const {open} = this.props;
        const config = linkTypeConfigStore.getConfig(this.provider || InternalLinkPluginComponent.defaultProvider);

        if (!config) {
            throw new Error('No link providers found!');
        }

        const {
            resourceKey,
            adapter,
        } = config;

        return (
            <Dialog
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!this.selectedResource}
                confirmText={translate('sulu_admin.confirm')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title="Internal Sulu Link"
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

                    <Field label="Link type" required={true}>
                        <SingleSelect
                            onChange={this.handleResourceTypeChange}
                            value={this.provider || InternalLinkPluginComponent.defaultProvider}
                        >
                            {linkTypeConfigStore.getProviders().map((provider) => {
                                return (
                                    <SingleSelect.Option
                                        key={provider}
                                        value={provider}
                                    >
                                        {provider}
                                    </SingleSelect.Option>
                                );
                            })}
                        </SingleSelect>
                    </Field>

                    <Field error={this.selectedResourceError} label="Page" required={true}>
                        <SingleSelection
                            adapter={adapter}
                            displayProperties={['title']}
                            emptyText="Keine Seite ausgewählt"
                            icon="su-document"
                            key={this.provider}
                            listKey={resourceKey}
                            locale={observable.box('en')}
                            onChange={this.handleResourceChange}
                            overlayTitle="Seite auswählen"
                            resourceKey={resourceKey}
                            value={this.selectedResource ? this.selectedResource.uuid : undefined}
                        />
                    </Field>
                </Form>
            </Dialog>
        );
    }
}

type InternalLinkValueType = ?{
    value: string,
    attributes: {
        target?: string,
    },
};

export default class InternalLinkPlugin {
    @observable open: boolean = false;
    setValue: (value: InternalLinkValueType) => void = () => {};
    currentValue: InternalLinkValueType = null;

    constructor(onChange: () => void) {
        observe(this, 'open', () => {
            onChange();
        });
    }

    @action handleChange = (value: InternalLinkValueType): void => {
        this.setValue(value);
        this.setValue = () => {};
        this.currentValue = null;
        this.open = false;
    };

    @action handleLinkCommand = (
        setValue: (value: InternalLinkValueType) => void,
        currentValue: InternalLinkValueType
    ): void => {
        this.currentValue = currentValue;
        this.setValue = setValue;
        this.open = true;
    };

    getPlugin = (): Plugin => {
        return createLinkPlugin(
            'Internal Link',
            'internal',
            'sulu:link',
            'href',
            'internalLinkHref',
            this.handleLinkCommand,
            false
        );
    };
}
