// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {arrayMove, MultiItemSelection} from '../../components';
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

    @observable overlayOpen: boolean = false;

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

    handleOverlayConfirm = (selectedIds: Array<string | number>) => {
        this.props.onChange(selectedIds);
        this.closeOverlay();
    };

    handleRemove = (id: number | string) => {
        const {onChange, value} = this.props;

        if (!value) {
            return;
        }

        onChange(value.filter((element) => element !== id));
    };

    handleSorted = (oldItemIndex: number, newItemIndex: number) => {
        const {onChange, value} = this.props;
        onChange(arrayMove(value, oldItemIndex, newItemIndex));
    };

    render() {
        const {icon, label, resourceKey, title, value} = this.props;

        return (
            <Fragment>
                <MultiItemSelection
                    label={label && value.length + ' ' + label}
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                    onItemRemove={this.handleRemove}
                    onItemsSorted={this.handleSorted}
                >
                    {value && value.map((id, index) => (
                        <MultiItemSelection.Item key={id} id={id} index={index}>{id}</MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <DatagridOverlay
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    resourceKey={resourceKey}
                    preSelectedIds={value}
                    title={title}
                />
            </Fragment>
        );
    }
}
