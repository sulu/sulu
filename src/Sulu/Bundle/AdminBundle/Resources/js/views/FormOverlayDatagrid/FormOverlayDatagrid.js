// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import log from 'loglevel';
import type {ViewProps} from '../../containers/ViewRenderer';
import Datagrid from '../Datagrid';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils/Translator';
import Form, {ResourceFormStore} from '../../containers/Form';
import {ResourceStore} from '../../stores';
import formOverlayDatagridStyles from './formOverlayDatagrid.scss';

@observer
export default class FormOverlayDatagrid extends React.Component<ViewProps> {
    static getDerivedRouteAttributes = Datagrid.getDerivedRouteAttributes;

    @observable formStore: ?ResourceFormStore;
    formRef: ?Form;

    handleItemAdd = () => {
        const {
            router: {
                route: {
                    options: {
                        addFormKey,
                    },
                },
            },
        } = this.props;

        this.updateFormStore(undefined, addFormKey);
    };

    handleItemClick = (itemId: string | number) => {
        const {
            router: {
                route: {
                    options: {
                        editFormKey,
                    },
                },
            },
        } = this.props;

        this.updateFormStore(itemId, editFormKey);
    };

    handleOverlayConfirm = () => {
        if (this.formRef) {
            this.formRef.submit();
        }
    };

    handleOverlayClose = () => {
        this.destroyFormStore();
    };

    handleFormSubmit = () => {
        if (this.formStore) {
            this.formStore.save()
                .then(() => {
                    this.destroyFormStore();
                })
                .catch((error) => {
                    log.error('Error while saving form-overlay content', error);
                });
        }
    };

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    @action updateFormStore = (itemId: ?string | number, formKey: string) => {
        const {
            router: {
                attributes,
                route: {
                    options: {
                        apiOptions = {},
                        resourceKey,
                        routerAttributesToFormStore = {},
                    },
                },
            },
        } = this.props;

        if (this.formStore) {
            this.formStore.destroy();
        }

        const formStoreOptions = this.buildFormStoreOptions(apiOptions, attributes, routerAttributesToFormStore);
        const resourceStore = new ResourceStore(resourceKey, itemId, {}, formStoreOptions);
        this.formStore = new ResourceFormStore(resourceStore, formKey, formStoreOptions);
    };

    @action destroyFormStore = () => {
        if (this.formStore) {
            this.formStore.destroy();
            this.formStore = undefined;
        }
    };

    buildFormStoreOptions(
        apiOptions: Object,
        attributes: Object,
        routerAttributesToFormStore: {[string | number]: string}
    ) {
        const formStoreOptions = apiOptions ? apiOptions : {};

        routerAttributesToFormStore = toJS(routerAttributesToFormStore);
        Object.keys(routerAttributesToFormStore).forEach((key) => {
            const attributeName = routerAttributesToFormStore[key];
            const formOptionKey = isNaN(key) ? key : routerAttributesToFormStore[key];

            formStoreOptions[formOptionKey] = attributes[attributeName];
        });

        return formStoreOptions;
    }

    componentWillUnmount() {
        this.destroyFormStore();
    }

    render() {
        const {
            router: {
                route: {
                    options: {
                        addFormKey,
                        addOverlayTitle,
                        editFormKey,
                        editOverlayTitle,
                    },
                },
            },
        } = this.props;

        const overlayTitle = this.formStore && this.formStore.id
            ? translate(editOverlayTitle || 'sulu_admin.edit')
            : translate(addOverlayTitle || 'sulu_admin.create');

        return (
            <Fragment>
                <Datagrid
                    {...this.props}
                    onItemAdd={addFormKey && this.handleItemAdd}
                    onItemClick={editFormKey && this.handleItemClick}
                />
                {!!this.formStore &&
                    <Overlay
                        confirmDisabled={!this.formStore.dirty}
                        confirmLoading={this.formStore.saving}
                        confirmText={translate('sulu_admin.save')}
                        onClose={this.handleOverlayClose}
                        onConfirm={this.handleOverlayConfirm}
                        open={!!this.formStore}
                        size="large"
                        title={overlayTitle}
                    >
                        <div className={formOverlayDatagridStyles.form}>
                            <Form
                                onSubmit={this.handleFormSubmit}
                                ref={this.setFormRef}
                                store={this.formStore}
                            />
                        </div>
                    </Overlay>
                }
            </Fragment>
        );
    }
}
