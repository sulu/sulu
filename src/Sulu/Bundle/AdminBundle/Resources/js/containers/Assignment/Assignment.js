// @flow
import React, {Fragment} from 'react';
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import {MultiItemSelection} from '../../components';
import AssignmentStore from './stores/AssignmentStore';
import DatagridOverlay from './DatagridOverlay';
import assignmentStyles from './assignment.scss';

type Props = {|
    adapter: string,
    disabledIds: Array<string | number>,
    displayProperties: Array<string>,
    onChange: (selectedIds: Array<string | number>) => void,
    label?: string,
    locale?: ?IObservableValue<string>,
    icon: string,
    resourceKey: string,
    value: Array<string | number>,
    overlayTitle: string,
|};

@observer
export default class Assignment extends React.Component<Props> {
    static defaultProps = {
        disabledIds: [],
        displayProperties: [],
        icon: 'su-plus',
        value: [],
    };

    assignmentStore: AssignmentStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    @observable overlayOpen: boolean = false;

    componentWillMount() {
        const {onChange, locale, resourceKey, value} = this.props;

        this.assignmentStore = new AssignmentStore(resourceKey, value, locale);
        this.changeDisposer = autorun(() => {
            const itemIds = this.assignmentStore.items.map((item) => item.id);

            if (!this.changeAutorunInitialized) {
                this.changeAutorunInitialized = true;
                return;
            }

            onChange(itemIds);
        });
    }

    componentWillReceiveProps(nextProps: Props) {
        const {value: newValue} = nextProps;

        if (newValue.every((id) => this.assignmentStore.items.some((item) => item.id === id))) {
            return;
        }

        this.assignmentStore.loadItems(nextProps.value);
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action handleOverlayOpen = () => {
        this.openOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedItems: Array<Object>) => {
        this.assignmentStore.set(selectedItems);
        this.closeOverlay();
    };

    handleRemove = (id: number | string) => {
        this.assignmentStore.removeById(id);
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        this.assignmentStore.move(oldItemIndex, newItemIndex);
    };

    render() {
        const {adapter, disabledIds, displayProperties, icon, label, locale, resourceKey, overlayTitle} = this.props;
        const {items, loading} = this.assignmentStore;
        const columns = displayProperties.length;

        return (
            <Fragment>
                <MultiItemSelection
                    label={label && this.assignmentStore.items.length + ' ' + label}
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                    loading={loading}
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                >
                    {items.map((item, index) => (
                        <MultiItemSelection.Item key={item.id} id={item.id} index={index + 1}>
                            <div>
                                {displayProperties.map((displayProperty) => (
                                    <span
                                        className={assignmentStyles.itemColumn}
                                        key={displayProperty}
                                        style={{width: 100 / columns + '%'}}
                                    >
                                        {item[displayProperty]}
                                    </span>
                                ))}
                            </div>
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <DatagridOverlay
                    adapter={adapter}
                    disabledIds={disabledIds}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    resourceKey={resourceKey}
                    preSelectedItems={items}
                    title={overlayTitle}
                />
            </Fragment>
        );
    }
}
