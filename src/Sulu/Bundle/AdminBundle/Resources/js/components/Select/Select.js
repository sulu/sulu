// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import classnames from 'classnames';
import selectStyles from './select.scss';
import Divider from './Divider';
import OverlayList from './OverlayList';
import Option from './Option';
import Label from './Label';

@observer
export default class Select extends React.PureComponent {
    props: {
        value?: string,
        className: string,
        onChange?: (s: string) => void,
        children: Array<Option | Divider>,
        icon?: string,
    };

    static defaultProps = {
        children: [],
        className: '',
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
    setLabel = (l: Label) => this.label = l;

    render() {
        const labelDimensions = this.label ? this.label.getDimensions() : {};
        const {listChildren, centeredChildIndex} = this.renderListChildren();
        const classNames = classnames({
            [selectStyles.select]: true,
            [this.props.className]: !!this.props.className,
        });

        return (
            <div className={classNames}>
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

    renderListChildren() {
        let centeredChildIndex = 0;
        let listChildren = React.Children.map(this.props.children, (child, index) => {
            if (child.type === Option) {
                child = React.cloneElement(child, {
                    onClick: this.handleOptionClick,
                    selected: child.props.value === this.props.value && !child.props.disabled,
                });
                centeredChildIndex = child.props.selected ? index : centeredChildIndex;
            }
            return child;
        });

        return {listChildren, centeredChildIndex};
    }
}
