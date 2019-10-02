// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import type {ElementRef} from 'react';
import type {IObservableValue} from 'mobx';
import type {ViewProps} from '../../containers/ViewRenderer';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils/Translator';
import Form from '../../containers/Form';
import ResourceStore from '../../stores/ResourceStore';
import List from '../List';
import ResourceFormStore from '../../containers/Form/stores/ResourceFormStore';
import Snackbar from '../../components/Snackbar';
import formOverlayListStyles from './formOverlayList.scss';

type Props = ViewProps & {
    resourceStore?: ResourceStore,
};

@observer
class FormOverlayList extends React.Component<Props> {
    static getDerivedRouteAttributes = List.getDerivedRouteAttributes;
    locale: IObservableValue<string> = observable.box();

    listRef: ?ElementRef<typeof List>;
    formRef: ?ElementRef<typeof Form>;

    @observable formStore: ?ResourceFormStore;
    @observable formErrors: Array<string> = [];

    handleItemAdd = () => {
        this.createFormOverlay(undefined);
    };

    handleItemClick = (itemId: string | number) => {
        this.createFormOverlay(itemId);
    };

    handleFormOverlayConfirm = () => {
        if (!this.formRef) {
            throw new Error('The Form ref has not been set! This should not happen and is likely a bug.');
        }

        this.formRef.submit();
    };

    handleFormOverlayClose = () => {
        this.destroyFormOverlay();
    };

    handleFormSubmit = () => {
        if (!this.formStore) {
            throw new Error('The FormStore has not been initialized! This should not happen and is likely a bug.');
        }

        this.formStore.save()
            .then(() => {
                this.destroyFormOverlay();
                if (this.listRef) {
                    this.listRef.reload();
                }
            })
            .catch(action(() => {
                this.formErrors.push(translate('sulu_admin.form_save_server_error'));
            }));
    };

    @action handleErrorSnackbarClose = () => {
        this.formErrors.pop();
    };

    handleFormError = () => {
        this.formErrors.push(translate('sulu_admin.form_contains_invalid_values'));
    };

    @action createFormOverlay = (itemId: ?string | number) => {
        const {
            router: {
                attributes,
                route: {
                    options: {
                        requestParameters = {},
                        formKey,
                        resourceKey,
                        routerAttributesToFormRequest = {},
                        resourceStorePropertiesToFormRequest = {},
                    },
                },
            },
        } = this.props;

        if (this.formStore) {
            this.formStore.destroy();
        }

        const observableOptions = {};
        if (this.locale.get()) {
            observableOptions.locale = this.locale;
        }

        const formStoreOptions = this.buildFormStoreOptions(
            requestParameters,
            attributes,
            routerAttributesToFormRequest,
            resourceStorePropertiesToFormRequest
        );
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
        requestParameters: Object,
        attributes: Object,
        routerAttributesToFormRequest: {[string | number]: string},
        resourceStorePropertiesToFormRequest: {[string | number]: string}
    ) {
        const formStoreOptions = requestParameters ? requestParameters : {};

        routerAttributesToFormRequest = toJS(routerAttributesToFormRequest);
        Object.keys(routerAttributesToFormRequest).forEach((key) => {
            const formOptionKey = routerAttributesToFormRequest[key];
            const attributeName = isNaN(key) ? key : routerAttributesToFormRequest[key];

            formStoreOptions[formOptionKey] = attributes[attributeName];
        });

        resourceStorePropertiesToFormRequest = toJS(resourceStorePropertiesToFormRequest);

        Object.keys(resourceStorePropertiesToFormRequest).forEach((key) => {
            const formOptionKey = resourceStorePropertiesToFormRequest[key];
            const attributeName = isNaN(key) ? key : resourceStorePropertiesToFormRequest[key];

            if (!this.props.resourceStore) {
                return;
            }

            formStoreOptions[formOptionKey] = this.props.resourceStore.data[attributeName];
        });

        return formStoreOptions;
    }

    setFormRef = (formRef: ?ElementRef<typeof Form>) => {
        this.formRef = formRef;
    };

    setListRef = (listRef: ?ElementRef<typeof List>) => {
        this.listRef = listRef;
    };

    componentWillUnmount() {
        this.destroyFormOverlay();
    }

    render() {
        const {
            router: {
                route: {
                    options: {
                        addOverlayTitle,
                        editOverlayTitle,
                        formKey,
                        overlaySize,
                    },
                },
            },
        } = this.props;

        const overlayTitle = this.formStore && this.formStore.id
            ? translate(editOverlayTitle || 'sulu_admin.edit')
            : translate(addOverlayTitle || 'sulu_admin.create');

        return (
            <Fragment>
                <List
                    {...this.props}
                    locale={this.locale}
                    onItemAdd={formKey && this.handleItemAdd}
                    onItemClick={formKey && this.handleItemClick}
                    ref={this.setListRef}
                />
                {!!this.formStore &&
                    <Overlay
                        confirmDisabled={!this.formStore.dirty}
                        confirmLoading={this.formStore.saving}
                        confirmText={translate('sulu_admin.save')}
                        onClose={this.handleFormOverlayClose}
                        onConfirm={this.handleFormOverlayConfirm}
                        open={!!this.formStore}
                        size={overlaySize ? overlaySize : 'small'}
                        title={overlayTitle}
                    >
                        <Snackbar
                            message={this.formErrors[this.formErrors.length - 1]}
                            onCloseClick={this.handleErrorSnackbarClose}
                            type="error"
                            visible={!!this.formErrors.length}
                        />
                        <div className={formOverlayListStyles.form}>
                            <Form
                                onError={this.handleFormError}
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

export default FormOverlayList;
