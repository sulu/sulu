// @flow
import React, {Fragment} from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {MultiItemSelection} from '../../components';
import AssignmentStore from './stores/AssignmentStore';
import DatagridOverlay from './DatagridOverlay';

type Props = {|
    onChange: (selectedIds: Array<string | number>) => void,
    label?: string,
    icon: string,
    resourceKey: string,
    value: Array<string | number>,
    title: string,
|};

@observer
export default class Assignment extends React.Component<Props> {
    static defaultProps = {
        icon: 'su-plus',
        value: [],
    };

    assignmentStore: AssignmentStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    @observable overlayOpen: boolean = false;

    componentWillMount() {
        const {onChange, resourceKey, value} = this.props;

        this.assignmentStore = new AssignmentStore(resourceKey, value);
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
        // TODO replace language with actual variable
        this.assignmentStore.loadItems(nextProps.value, observable('en'));
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
        const {icon, label, resourceKey, title} = this.props;
        const {items, loading} = this.assignmentStore;

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
                        <MultiItemSelection.Item key={item.id} id={item.id} index={index}>
                            {item.id}
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <DatagridOverlay
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    resourceKey={resourceKey}
                    preSelectedItems={items}
                    title={title}
                />
            </Fragment>
        );
    }
}
