// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import type {ViewProps} from '../../containers/ViewRenderer';
import Datagrid from '../Datagrid';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils/Translator';
import Form, {ResourceFormStore} from '../../containers/Form';
import {ResourceStore} from '../../stores';
import formOverlayDatagridStyles from './formOverlayDatagrid.scss';
import ErrorSnackbar from './ErrorSnackbar';

@observer
export default class FormOverlayDatagrid extends React.Component<ViewProps> {
    static getDerivedRouteAttributes = Datagrid.getDerivedRouteAttributes;

    datagridRef: ?Datagrid;
    formRef: ?Form;

    @observable formStore: ?ResourceFormStore;
    @observable formErrors = [];

    handleItemAdd = () => {
        const {
            router: {
                route: {
                    options: {
                        formKey,
                    },
                },
            },
        } = this.props;

        this.createFormOverlay(undefined, formKey);
    };

    handleItemClick = (itemId: string | number) => {
        const {
            router: {
                route: {
                    options: {
                        formKey,
                    },
                },
            },
        } = this.props;

        this.createFormOverlay(itemId, formKey);
    };

    handleFormOverlayConfirm = () => {
        if (this.formRef) {
            this.formRef.submit();
        }
    };

    handleFormOverlayClose = () => {
        this.destroyFormOverlay();
    };

    handleFormSubmit = () => {
        if (this.formStore) {
            this.formStore.save()
                .then(() => {
                    this.destroyFormOverlay();
                    if (this.datagridRef) {
                        this.datagridRef.datagridStore.sendRequest();
                    }
                })
                .catch(action((error) => {
                    this.formErrors.push(error);
                }));
        }
    };

    @action handleErrorSnackbarClose = () => {
        this.formErrors.pop();
    };

    @action createFormOverlay = (itemId: ?string | number, formKey: string) => {
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

        const observableOptions = {};
        if (this.datagridRef && this.datagridRef.locale.get()) {
            observableOptions['locale'] = this.datagridRef.locale;
        }

        const formStoreOptions = this.buildFormStoreOptions(apiOptions, attributes, routerAttributesToFormStore);
        const resourceStore = new ResourceStore(resourceKey, itemId, observableOptions, formStoreOptions);
        this.formStore = new ResourceFormStore(resourceStore, formKey, formStoreOptions);
    };

    @action destroyFormOverlay = () => {
        this.formErrors = [];

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

    setFormRef = (formRef: ?Form) => {
        this.formRef = formRef;
    };

    setDatagridRef = (datagridRef: ?Datagrid) => {
        this.datagridRef = datagridRef;
    };

    componentWillUnmount() {
        this.destroyFormOverlay();
    }

    renderFormOverlay() {
        const {
            router: {
                route: {
                    options: {
                        addOverlayTitle,
                        editOverlayTitle,
                    },
                },
            },
        } = this.props;

        if (!this.formStore) {
            return null;
        }

        const overlayTitle = this.formStore.id
            ? translate(editOverlayTitle || 'sulu_admin.edit')
            : translate(addOverlayTitle || 'sulu_admin.create');

        return (
            <Overlay
                confirmDisabled={!this.formStore.dirty}
                confirmLoading={this.formStore.saving}
                confirmText={translate('sulu_admin.save')}
                onClose={this.handleFormOverlayClose}
                onConfirm={this.handleFormOverlayConfirm}
                open={!!this.formStore}
                size="small"
                title={overlayTitle}
            >
                <div className={formOverlayDatagridStyles.form}>
                    <ErrorSnackbar
                        onCloseClick={this.handleErrorSnackbarClose}
                        visible={!!this.formErrors.length}
                    />
                    <Form
                        onSubmit={this.handleFormSubmit}
                        ref={this.setFormRef}
                        store={this.formStore}
                    />
                </div>
            </Overlay>
        );
    }

    render() {
        const {
            router: {
                route: {
                    options: {
                        formKey,
                    },
                },
            },
        } = this.props;

        return (
            <Fragment>
                <Datagrid
                    {...this.props}
                    onItemAdd={formKey && this.handleItemAdd}
                    onItemClick={formKey && this.handleItemClick}
                    ref={this.setDatagridRef}
                />
                {this.renderFormOverlay()}
            </Fragment>
        );
    }
}
