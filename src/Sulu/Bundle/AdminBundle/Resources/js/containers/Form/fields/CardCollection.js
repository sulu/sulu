// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import CardCollectionComponent from '../../../components/CardCollection';
import Overlay from '../../../components/Overlay';
import Form, {MemoryFormStore} from '../../../containers/Form';
import {translate} from '../../../utils/Translator';
import type {FieldTypeProps} from '../../../types';
import cardCollectionStyles from './cardCollection.scss';

@observer
export default class CardCollection extends React.Component<FieldTypeProps<Array<Object>>> {
    @observable overlayIndex: number | typeof undefined = undefined;
    @observable formStore: ?MemoryFormStore = undefined;
    formRef: ?ElementRef<typeof Form>;

    constructor(props: FieldTypeProps<Array<Object>>) {
        super(props);

        const {
            fieldTypeOptions: {
                renderCardContent,
                schema,
            } = {},
        } = this.props;

        if (!renderCardContent) {
            throw new Error('The "renderCardContent" field type option must be a function!');
        }

        if (!schema) {
            throw new Error('The "schema" field type option must be a valid schema!');
        }
    }

    setFormRef = (formRef: ?ElementRef<typeof Form>) => {
        this.formRef = formRef;
    };

    @action handleAdd = () => {
        const {
            fieldTypeOptions: {
                jsonSchema,
                schema,
            },
        } = this.props;

        this.overlayIndex = undefined;
        this.formStore = new MemoryFormStore({}, schema, jsonSchema);
    };

    @action handleEdit = (index: number) => {
        const {
            fieldTypeOptions: {
                jsonSchema,
                schema,
            },
            value,
        } = this.props;

        if (!value) {
            throw new Error('The index to edit does not exists. This should not happen and is likely a bug.');
        }

        this.overlayIndex = index;
        this.formStore = new MemoryFormStore(toJS(value[index]), schema, jsonSchema);
    };

    @action handleRemove = (index: number) => {
        const {onChange, value} = this.props;

        if (!value) {
            throw new Error('The index to remove does not exists. This should not happen and is likely a bug.');
        }

        onChange(value.filter((element, elementIndex) => elementIndex !== index));
    };

    @action handleCloseOverlay = () => {
        this.closeFormStore();
    };

    handleConfirm = () => {
        if (!this.formRef) {
            throw new Error(
                'The reference to the form does not exist, although the overlay was confirmed.'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.formRef.submit();
    };

    @action handleOverlaySubmit = () => {
        const {onChange, onFinish, value} = this.props;
        const {formStore} = this;

        if (!formStore) {
            throw new Error(
                'The formStore does not exist, although it was submitted. This should nto happen and is likely a bug.'
            );
        }

        if (value === null || value === undefined) {
            onChange([formStore.data]);
        } else if (this.overlayIndex === undefined) {
            onChange([...value, formStore.data]);
        } else {
            onChange(value.map((element, index) => index === this.overlayIndex ? formStore.data : element));
        }

        onFinish();
        this.closeFormStore();
    };

    @action closeFormStore() {
        if (!this.formStore){
            return;
        }

        this.formStore.destroy();
        this.formStore = undefined;
    }

    render() {
        const {
            fieldTypeOptions: {
                addOverlayTitle,
                editOverlayTitle,
                renderCardContent,
            },
            value,
        } = this.props;

        return (
            <Fragment>
                <CardCollectionComponent onAdd={this.handleAdd} onEdit={this.handleEdit} onRemove={this.handleRemove}>
                    {!!value && value.map((card, index) => (
                        <CardCollectionComponent.Card key={index}>
                            {renderCardContent(card)}
                        </CardCollectionComponent.Card>
                    ))}
                </CardCollectionComponent>
                <Overlay
                    confirmDisabled={!!this.formStore && !this.formStore.dirty}
                    confirmText={translate('sulu_admin.ok')}
                    onClose={this.handleCloseOverlay}
                    onConfirm={this.handleConfirm}
                    open={!!this.formStore}
                    size="small"
                    title={this.overlayIndex !== null
                        ? translate(editOverlayTitle)
                        : translate(addOverlayTitle)
                    }
                >
                    <div className={cardCollectionStyles.overlay}>
                        {!!this.formStore &&
                            <Form onSubmit={this.handleOverlaySubmit} ref={this.setFormRef} store={this.formStore} />
                        }
                    </div>
                </Overlay>
            </Fragment>
        );
    }
}
