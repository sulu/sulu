// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {OptionSelectedVisualization, SelectChildren} from './types';
import OverlayList from './OverlayList';
import Option from './Option';
import Label from './Label';
import genericSelectStyles from './genericSelect.scss';

type Props = {
    children: SelectChildren,
    icon?: string,
    onSelect: (values: string) => void,
    getLabelText: () => string,
    closeOnSelect: boolean,
    optionIsSelected: (option: Element<typeof Option>) => boolean,
    selectedVisualization: OptionSelectedVisualization,
};

@observer
export default class GenericSelect extends React.PureComponent<Props> {
    static defaultProps = {
        closeOnSelect: true,
    };

    label: ?ElementRef<typeof Label>;
    centeredChildIndex: number;
    @observable isOpen: boolean;

    @action openList = () => {
        this.centeredChildIndex = this.getCenteredChildIndex();
        this.isOpen = true;
    };

    @action closeList = () => {
        this.isOpen = false;
    };

    handleOptionClick = (value: string) => {
        this.props.onSelect(value);
        if (this.props.closeOnSelect) {
            this.closeList();
        }
    };

    handleLabelClick = this.openList;
    handleListRequestClose = this.closeList;
    setLabel = (label: ?ElementRef<typeof Label>) => this.label = label;

    render() {
        const labelDimensions = this.label ? this.label.getDimensions() : {};
        const listChildren = this.renderListChildren();

        return (
            <div className={genericSelectStyles.select}>
                <Label
                    ref={this.setLabel}
                    icon={this.props.icon}
                    onClick={this.handleLabelClick}>
                    {this.props.getLabelText()}
                </Label>
                <OverlayList
                    anchorTop={labelDimensions.top}
                    anchorLeft={labelDimensions.left}
                    anchorWidth={labelDimensions.width}
                    anchorHeight={labelDimensions.height}
                    isOpen={this.isOpen}
                    centeredChildIndex={this.centeredChildIndex}
                    onRequestClose={this.handleListRequestClose}>
                    {listChildren}
                </OverlayList>
            </div>
        );
    }

    renderListChildren(): SelectChildren {
        return React.Children.map(this.props.children, (child: any) => {
            if (child.type === Option) {
                child = React.cloneElement(child, {
                    onClick: this.handleOptionClick,
                    selected: this.props.optionIsSelected(child),
                    selectedVisualization: this.props.selectedVisualization,
                });
            }
            return child;
        });
    }

    getCenteredChildIndex(): number {
        const index = React.Children.toArray(this.props.children).findIndex(
            (child: any) => child.type === Option && this.props.optionIsSelected(child)
        );
        return index === -1 ? 0 : index;
    }
}
