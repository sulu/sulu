// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import ArrowMenu from '../ArrowMenu';
import Button from '../Button';

type Props = {|
    children: ChildrenArray<Element<typeof ArrowMenu.Action> | false>,
    className?: string,
    icon?: string,
    label?: string,
    skin: 'primary' | 'secondary' | 'link' | 'icon',
|};

@observer
class DropdownButton extends React.Component<Props> {
    static defaultProps = {
        skin: 'secondary',
    };

    static Item = ArrowMenu.Action;

    @observable open: boolean = false;

    @action handleButtonClick = () => {
        this.open = true;
    };

    @action handleArrowMenuClose = () => {
        this.open = false;
    };

    render() {
        const {children, className, icon, label, skin} = this.props;

        const button = (
            <Button
                className={className}
                icon={icon}
                onClick={this.handleButtonClick}
                showDropdownIcon={true}
                skin={skin}
            >
                {label}
            </Button>
        );

        return (
            <ArrowMenu anchorElement={button} onClose={this.handleArrowMenuClose} open={this.open} refProp="buttonRef">
                <ArrowMenu.Section>
                    {children}
                </ArrowMenu.Section>
            </ArrowMenu>
        );
    }
}

export default DropdownButton;
