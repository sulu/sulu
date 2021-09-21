// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import {translate} from '../../utils/Translator';
import {ResourceFormStore, resourceFormStoreFactory} from '../../containers/Form';
import FormOverlay from '../../containers/FormOverlay';
import ResourceStore from '../../stores/ResourceStore';
import List from '../List';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef} from 'react';

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
                        routerAttributesToFormMetadata = {},
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

        const formStoreMetadataOptions = this.buildFormStoreMetadataOptions(
            metadataRequestParameters,
            attributes,
            routerAttributesToFormMetadata
        );

        const resourceStore = new ResourceStore(resourceKey, itemId, observableOptions, formStoreOptions);
        this.formStore = resourceFormStoreFactory.createFromResourceStore(
            resourceStore,
            formKey,
            formStoreOptions,
            formStoreMetadataOptions
        );
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

    buildFormStoreMetadataOptions(
        metadataRequestParameters: Object,
        attributes: Object,
        routerAttributesToFormMetadata: {[string | number]: string}
    ) {
        const metadataOptions = metadataRequestParameters ? metadataRequestParameters : {};

        Object.keys(toJS(routerAttributesToFormMetadata)).forEach((key) => {
            const metadataOptionKey = routerAttributesToFormMetadata[key];
            const attributeName = isNaN(key) ? key : toJS(routerAttributesToFormMetadata[key]);

            metadataOptions[metadataOptionKey] = attributes[attributeName];
        });

        return metadataOptions;
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
                        confirmDisabled={!formStore.dirty}
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
