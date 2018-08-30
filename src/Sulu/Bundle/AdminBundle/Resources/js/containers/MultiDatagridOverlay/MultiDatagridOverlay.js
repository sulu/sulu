// @flow
import React from 'react';
import {observable } from 'mobx';
import type {IObservableValue} from 'mobx';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import DatagridOverlay from '../DatagridOverlay';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems?: boolean,
    clearSelectionOnClose: boolean,
    confirmLoading?: boolean,
    disabledIds?: Array<string | number>,
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItems: Array<Object>) => void,
    open: boolean,
    options?: Object,
    resourceKey: string,
    preSelectedItems: Array<Object>,
    title: string,
|};

export default class MultiDatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable.box(1);

    static defaultProps = {
        clearSelectionOnClose: false,
        preSelectedItems: [],
    };

    constructor(props: Props) {
        super(props);

        const {locale, options, preSelectedItems, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        this.datagridStore = new DatagridStore(resourceKey, observableOptions, options);

        preSelectedItems.forEach((preSelectedItem) => {
            this.datagridStore.select(preSelectedItem);
        });
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
    }

    handleConfirm = () => {
        this.props.onConfirm(this.datagridStore.selections);
    };

    render() {
        const {
            adapter,
            allowActivateForDisabledItems,
            clearSelectionOnClose,
            confirmLoading,
            disabledIds,
            onClose,
            open,
            preSelectedItems,
            title,
        } = this.props;

        return (
            <DatagridOverlay
                adapter={adapter}
                allowActivateForDisabledItems={allowActivateForDisabledItems}
                clearSelectionOnClose={clearSelectionOnClose}
                confirmLoading={confirmLoading}
                datagridStore={this.datagridStore}
                disabledIds={disabledIds}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                preSelectedItems={preSelectedItems}
                title={title}
            />
        );
    }
}
