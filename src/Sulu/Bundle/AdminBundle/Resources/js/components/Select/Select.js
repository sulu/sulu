// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import selectStyles from './select.scss';
import Divider from './Divider';
import OverlayList from './OverlayList';
import Option from './Option';
import Label from './Label';

@observer
export default class Select extends React.PureComponent {
    props: {
        value?: string,
        onChange?: (s: string) => void,
        children: Array<Option | Divider>,
        icon?: string,
    };

    static defaultProps = {
        children: [],
    };

    label: Label;
    @observable isOpen: boolean;

    @action openList = () => {
        this.isOpen = true;
    };

    @action closeList = () => {
        this.isOpen = false;
    };

    @computed get labelText(): string {
        let label = '';
        React.Children.forEach(this.props.children, (child) => {
            if (child.type !== Option) {
                return;
            }
            if (!label || this.props.value === child.props.value) {
                label = child.props.children;
            }
        });

        return label;
    }

    handleOptionClick = (value: string) => {
        if (this.props.onChange) {
            this.props.onChange(value);
        }
        this.closeList();
    };

    handleLabelClick = this.openList;
    handleListRequestClose = this.closeList;
    setLabel = (label: Label) => this.label = label;

    render() {
        const labelDimensions = this.label ? this.label.getDimensions() : {};
        const listChildren = this.renderListChildren();
        const centeredChildIndex = this.getCenteredChildIndex(listChildren);

        return (
            <div className={selectStyles.select}>
                <Label
                    ref={this.setLabel}
                    icon={this.props.icon}
                    onClick={this.handleLabelClick}>
                    {this.labelText}
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

    renderListChildren(): React.Children {
        return React.Children.map(this.props.children, (child) => {
            if (child.type === Option) {
                child = React.cloneElement(child, {
                    onClick: this.handleOptionClick,
                    selected: child.props.value === this.props.value && !child.props.disabled,
                });
            }
            return child;
        });
    }

    getCenteredChildIndex(children: React.Children): number {
        return React.Children.toArray(children).reduce(
            (centeredChild, child, index) => child.props.selected ? index : centeredChild, 0
        );
    }
}
