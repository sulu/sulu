// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import type {OptionSelectedVisualization, SelectChildren, SelectProps} from './types';
import OverlayList from './OverlayList';
import Action from './Action';
import Option from './Option';
import Label from './Label';
import genericSelectStyles from './genericSelect.scss';

type Props = SelectProps & {
    onSelect: (values: string) => void,
    labelText: string,
    closeOnSelect: boolean,
    isOptionSelected: (option: Element<typeof Option>) => boolean,
    selectedVisualization?: OptionSelectedVisualization,
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
        const {icon, labelText} = this.props;
        const labelDimensions = this.label ? this.label.getDimensions() : {};
        const listChildren = this.renderListChildren();

        return (
            <div className={genericSelectStyles.select}>
                <Label
                    ref={this.setLabel}
                    icon={icon}
                    onClick={this.handleLabelClick}>
                    {labelText}
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
                    selected: this.props.isOptionSelected(child),
                    selectedVisualization: this.props.selectedVisualization,
                });
            }
            if (child.type === Action) {
                child = React.cloneElement(child, {
                    afterAction: this.closeList,
                });
            }
            return child;
        });
    }

    getCenteredChildIndex(): number {
        const index = React.Children.toArray(this.props.children).findIndex(
            (child: any) => child.type === Option && this.props.isOptionSelected(child)
        );
        return index === -1 ? 0 : index;
    }
}
