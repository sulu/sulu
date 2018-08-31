// @flow
import React from 'react';
import {autorun, observable } from 'mobx';
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
    onConfirm: (selectedItem: Object) => void,
    open: boolean,
    options?: Object,
    resourceKey: string,
    preSelectedItem?: ?Object,
    title: string,
|};

export default class SingleDatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable.box(1);
    selectionDisposer: () => void;

    static defaultProps = {
        clearSelectionOnClose: false,
    };

    constructor(props: Props) {
        super(props);

        const {locale, options, preSelectedItem, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        const initialSelectionIds = [];
        if (preSelectedItem) {
            initialSelectionIds.push(preSelectedItem.id);
        }
        this.datagridStore = new DatagridStore(resourceKey, observableOptions, options, initialSelectionIds);

        this.selectionDisposer = autorun(() => {
            const {selections} = this.datagridStore;

            if (selections.length <= 1) {
                return;
            }

            const selection = selections[selections.length - 1];

            if (!selection) {
                return;
            }

            this.datagridStore.clearSelection();
            this.datagridStore.select(selection);
        });
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.selectionDisposer();
    }

    handleConfirm = () => {
        if (this.datagridStore.selections.length > 1) {
            throw new Error(
                'The SingleDatagridOverlay can only handle single selection.'
                + 'This should not happen and is likely a bug.'
            );
        }

        this.props.onConfirm(this.datagridStore.selections[0]);
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
            preSelectedItem,
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
                preSelectedItems={preSelectedItem ? [preSelectedItem] : undefined}
                title={title}
            />
        );
    }
}
