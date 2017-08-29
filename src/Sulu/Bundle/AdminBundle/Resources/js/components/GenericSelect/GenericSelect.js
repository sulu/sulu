// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Action from './Action';
import Option from './Option';
import type {OptionSelectedVisualization, SelectChildren, SelectProps} from './types';
import OverlayList from './OverlayList';
import DisplayValue from './DisplayValue';
import genericSelectStyles from './genericSelect.scss';

type Props = SelectProps & {
    onSelect: (values: string) => void,
    displayValue: string,
    closeOnSelect: boolean,
    isOptionSelected: (option: Element<typeof Option>) => boolean,
    selectedVisualization?: OptionSelectedVisualization,
};

@observer
export default class GenericSelect extends React.PureComponent<Props> {
    static defaultProps = {
        closeOnSelect: true,
    };

    displayValue: ?ElementRef<typeof DisplayValue>;
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

    handleDisplayValueClick = this.openList;
    handleListClose = this.closeList;
    setDisplayValue = (displayValue: ?ElementRef<typeof DisplayValue>) => this.displayValue = displayValue;

    render() {
        const {icon, displayValue} = this.props;
        const displayValueDimensions = this.displayValue ? this.displayValue.getDimensions() : {};
        const listChildren = this.renderListChildren();

        return (
            <div className={genericSelectStyles.select}>
                <DisplayValue
                    ref={this.setDisplayValue}
                    icon={icon}
                    onClick={this.handleDisplayValueClick}>
                    {displayValue}
                </DisplayValue>
                <OverlayList
                    anchorTop={displayValueDimensions.top}
                    anchorLeft={displayValueDimensions.left}
                    anchorWidth={displayValueDimensions.width}
                    anchorHeight={displayValueDimensions.height}
                    isOpen={this.isOpen}
                    centeredChildIndex={this.centeredChildIndex}
                    onClose={this.handleListClose}>
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
