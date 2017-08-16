// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {SelectChildren} from './types';
import OverlayList from './OverlayList';
import Option from './Option';
import Label from './Label';
import genericSelectStyles from './genericSelect.scss';

type Props = {
    children: SelectChildren,
    icon?: string,
    onSelect: (values: string) => void,
    getLabelText: () => string,
    optionIsSelected: (option: Element<typeof Option>) => boolean,
};

@observer
export default class GenericSelect extends React.PureComponent<Props> {
    label: ?ElementRef<typeof Label>;
    @observable isOpen: boolean;

    @action openList = () => {
        this.isOpen = true;
    };

    @action closeList = () => {
        this.isOpen = false;
    };

    handleOptionClick = (value: string) => {
        this.props.onSelect(value);
        this.closeList();
    };

    handleLabelClick = this.openList;
    handleListRequestClose = this.closeList;
    setLabel = (label: ?ElementRef<typeof Label>) => this.label = label;

    render() {
        const labelDimensions = this.label ? this.label.getDimensions() : {};
        const listChildren = this.renderListChildren();
        const centeredChildIndex = this.getCenteredChildIndex(listChildren);

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
                    centeredChildIndex={centeredChildIndex}
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
                });
            }
            return child;
        });
    }

    getCenteredChildIndex(children: SelectChildren): number {
        const index = React.Children.toArray(children).findIndex((child: any) => child.props.selected);
        return index === -1 ? 0 : index;
    }
}
