// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Dialog from '../../components/Dialog';
import Form from '../../components/Form';
import SingleSelect from '../../components/SingleSelect';
import {translate} from '../../utils/Translator';
import type {SchemaType} from './types';

type Props = {|
    onCancel: () => void,
    onConfirm: (locale: string) => void,
    open: boolean,
    types: {[key: string]: SchemaType},
|};

@observer
class MissingTypeDialog extends React.Component<Props> {
    @observable selectedType: string;

    handleCancel = () => {
        this.props.onCancel();
    };

    handleConfirm = () => {
        this.props.onConfirm(this.selectedType);
    };

    @action handleTypeChange = (type: string | number) => {
        if (typeof type !== 'string') {
            throw new Error('Only strings are accepted as types! This should not happen and is likely a bug.');
        }

        this.selectedType = type;
    };

    render() {
        const {
            open,
            types,
        } = this.props;

        return (
            <Dialog
                align="left"
                cancelText={translate('sulu_admin.cancel')}
                confirmDisabled={!this.selectedType}
                confirmText={translate('sulu_admin.ok')}
                onCancel={this.handleCancel}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_admin.missing_type_dialog_title')}
            >
                <p>{translate('sulu_admin.missing_type_dialog_description')}</p>
                <Form>
                    <Form.Field colSpan={6}>
                        <SingleSelect onChange={this.handleTypeChange} value={this.selectedType}>
                            {Object.keys(types).map((key) => (
                                <SingleSelect.Option key={types[key].key} value={types[key].key}>
                                    {types[key].title}
                                </SingleSelect.Option>
                            ))}
                        </SingleSelect>
                    </Form.Field>
                </Form>
            </Dialog>
        );
    }
}

export default MissingTypeDialog;
