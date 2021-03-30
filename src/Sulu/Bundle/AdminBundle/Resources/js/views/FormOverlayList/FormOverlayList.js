// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import type {ElementRef} from 'react';
import type {IObservableValue} from 'mobx';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import FormOverlay from '../../containers/FormOverlay';
import ResourceStore from '../../stores/ResourceStore';
import List from '../List';
import ResourceFormStore from '../../containers/Form/stores/ResourceFormStore';

type Props = ViewProps & {
    resourceStore?: ResourceStore,
};

@observer
class FormOverlayList extends React.Component<Props> {
    static getDerivedRouteAttributes = List.getDerivedRouteAttributes;
    locale: IObservableValue<string> = observable.box();

    listRef: ?ElementRef<typeof List>;

    @observable formStore: ?ResourceFormStore;

    handleItemAdd = () => {
        this.createFormOverlay(undefined);
    };

    handleItemClick = (itemId: string | number) => {
        this.createFormOverlay(itemId);
    };

    handleFormOverlayConfirm = () => {
        this.destroyFormStore();
        if (this.listRef) {
            this.listRef.reload();
        }
    };

    handleFormOverlayClose = () => {
        this.destroyFormStore();
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
                        metadataRequestParameters = {},
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
        this.formStore = new ResourceFormStore(resourceStore, formKey, formStoreOptions, metadataRequestParameters);
    };

    @action destroyFormStore = () => {
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

    setListRef = (listRef: ?ElementRef<typeof List>) => {
        this.listRef = listRef;
    };

    componentWillUnmount() {
        this.destroyFormStore();
    }

    render() {
        const {
            formStore,
            props: {
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
            },
        } = this;

        const overlayTitle = formStore && formStore.id
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
                {!!formStore && (
                    <FormOverlay
                        confirmText={translate('sulu_admin.save')}
                        formStore={formStore}
                        onClose={this.handleFormOverlayClose}
                        onConfirm={this.handleFormOverlayConfirm}
                        open={!!formStore}
                        size={overlaySize ? overlaySize : 'small'}
                        title={overlayTitle}
                    />
                )}
            </Fragment>
        );
    }
}

export default FormOverlayList;
